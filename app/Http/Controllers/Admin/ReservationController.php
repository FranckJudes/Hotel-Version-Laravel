<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Réservations (Admin)",
 *     description="API de gestion des réservations - Partie admin"
 * )
 */
class ReservationController extends Controller
{
    /**
     * Afficher toutes les réservations de l'utilisateur connecté (côté client)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $query = Reservation::with(['room', 'room.images'])
            ->where('user_id', Auth::id());

        // Filtrer par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par date
        if ($request->has('upcoming') && $request->boolean('upcoming')) {
            $query->where('check_in_date', '>=', now());
        } elseif ($request->has('past') && $request->boolean('past')) {
            $query->where('check_out_date', '<', now());
        }

        // Tri par date d'arrivée
        $query->orderBy('check_in_date', $request->has('past') ? 'desc' : 'asc');

        $reservations = $query->get();

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }

    /**
     * Créer une nouvelle réservation (côté client)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clientStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'adults' => 'required|integer|min:1',
            'children' => 'sometimes|integer|min:0',
            'special_requests' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si la chambre est disponible pour les dates demandées
        $isRoomAvailable = $this->checkRoomAvailability(
            $request->room_id,
            $request->check_in_date,
            $request->check_out_date
        );

        if (!$isRoomAvailable) {
            return response()->json([
                'success' => false,
                'message' => 'La chambre n\'est pas disponible pour les dates sélectionnées'
            ], 400);
        }

        // Récupérer le prix de la chambre
        $room = Room::findOrFail($request->room_id);

        // Calculer le nombre de nuits
        $checkIn = new \DateTime($request->check_in_date);
        $checkOut = new \DateTime($request->check_out_date);
        $nights = $checkIn->diff($checkOut)->days;

        // Calculer le montant total
        $totalAmount = $room->price_per_night * $nights;

        $reservation = Reservation::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'adults' => $request->adults,
            'children' => $request->children ?? 0,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'special_requests' => $request->special_requests
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Réservation créée avec succès. Veuillez procéder au paiement pour confirmer.',
            'data' => $reservation->load(['room', 'room.images'])
        ], 201);
    }

    /**
     * Afficher une réservation spécifique (côté client)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function clientShow(int $id): JsonResponse
    {
        $reservation = Reservation::with(['room', 'room.images', 'payments'])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation non trouvée ou vous n\'êtes pas autorisé à la consulter'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $reservation
        ]);
    }

    /**
     * Annuler une réservation (côté client)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function clientCancel(int $id): JsonResponse
    {
        $reservation = Reservation::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation non trouvée ou vous n\'êtes pas autorisé à la modifier'
            ], 404);
        }

        // Vérifier si la réservation peut être annulée
        $checkInDate = new \DateTime($reservation->check_in_date);
        $today = new \DateTime();
        $daysUntilCheckIn = $today->diff($checkInDate)->days;

        // Politique d'annulation : au moins 2 jours avant le check-in
        if ($checkInDate <= $today || $daysUntilCheckIn < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Les annulations doivent être effectuées au moins 2 jours avant la date d\'arrivée'
            ], 400);
        }

        // Vérifier si la réservation n'est pas déjà annulée ou terminée
        if (in_array($reservation->status, ['cancelled', 'checked_out'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne peut pas être annulée car elle est déjà ' .
                    ($reservation->status === 'cancelled' ? 'annulée' : 'terminée')
            ], 400);
        }

        $reservation->status = 'cancelled';
        $reservation->save();

        // Ici, vous pourriez ajouter une logique pour rembourser les paiements

        return response()->json([
            'success' => true,
            'message' => 'Réservation annulée avec succès',
            'data' => $reservation->fresh(['room'])
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/reservations",
     *     summary="Récupérer toutes les réservations",
     *     description="Récupère la liste de toutes les réservations avec possibilité de filtrage",
     *     operationId="getAdminReservations",
     *     tags={"Réservations (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "confirmed", "checked_in", "checked_out", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Date de début pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Date de fin pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filtrer par utilisateur",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="room_id",
     *         in="query",
     *         description="Filtrer par chambre",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Champ pour le tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created_at", "check_in_date", "check_out_date", "total_amount"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des réservations récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Reservation")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit - Droits administrateur requis"
     *     )
     * )
     *
     * Afficher toutes les réservations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::with(['user', 'room']);

        // Filtrage par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrage par période
        if ($request->has('from_date')) {
            $query->where('check_in_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('check_out_date', '<=', $request->to_date);
        }

        // Filtrage par utilisateur
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtrage par chambre
        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Tri
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $reservations = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }

    /**
     * Créer une nouvelle réservation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'adults' => 'required|integer|min:1',
            'children' => 'sometimes|integer|min:0',
            'special_requests' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si la chambre est disponible pour les dates demandées
        $isRoomAvailable = $this->checkRoomAvailability(
            $request->room_id,
            $request->check_in_date,
            $request->check_out_date
        );

        if (!$isRoomAvailable) {
            return response()->json([
                'success' => false,
                'message' => 'La chambre n\'est pas disponible pour les dates sélectionnées'
            ], 400);
        }

        // Récupérer le prix de la chambre
        $room = Room::findOrFail($request->room_id);

        // Calculer le nombre de nuits
        $checkIn = new \DateTime($request->check_in_date);
        $checkOut = new \DateTime($request->check_out_date);
        $nights = $checkIn->diff($checkOut)->days;

        // Calculer le montant total
        $totalAmount = $room->price_per_night * $nights;

        $reservation = Reservation::create([
            'user_id' => $request->user_id,
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'adults' => $request->adults,
            'children' => $request->children ?? 0,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'special_requests' => $request->special_requests
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Réservation créée avec succès',
            'data' => $reservation->load(['user', 'room'])
        ], 201);
    }

    /**
     * Afficher une réservation spécifique
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $reservation = Reservation::with(['user', 'room', 'payments'])->find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $reservation
        ]);
    }

    /**
     * Mettre à jour une réservation
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'room_id' => 'sometimes|exists:rooms,id',
            'check_in_date' => 'sometimes|date',
            'check_out_date' => 'sometimes|date|after:check_in_date',
            'adults' => 'sometimes|integer|min:1',
            'children' => 'sometimes|integer|min:0',
            'status' => 'sometimes|string|in:pending,confirmed,checked_in,checked_out,cancelled',
            'special_requests' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si les dates ou la chambre ont changé
        $roomChanged = $request->has('room_id') && $request->room_id != $reservation->room_id;
        $datesChanged = ($request->has('check_in_date') && $request->check_in_date != $reservation->check_in_date) ||
                        ($request->has('check_out_date') && $request->check_out_date != $reservation->check_out_date);

        if (($roomChanged || $datesChanged) && $request->status !== 'cancelled') {
            $roomId = $request->room_id ?? $reservation->room_id;
            $checkInDate = $request->check_in_date ?? $reservation->check_in_date;
            $checkOutDate = $request->check_out_date ?? $reservation->check_out_date;

            // Vérifier la disponibilité de la chambre
            $isRoomAvailable = $this->checkRoomAvailability(
                $roomId,
                $checkInDate,
                $checkOutDate,
                $id
            );

            if (!$isRoomAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => 'La chambre n\'est pas disponible pour les dates sélectionnées'
                ], 400);
            }

            // Si la chambre ou les dates ont changé, recalculer le montant total
            if ($roomChanged || $datesChanged) {
                $room = Room::findOrFail($roomId);

                $checkIn = new \DateTime($checkInDate);
                $checkOut = new \DateTime($checkOutDate);
                $nights = $checkIn->diff($checkOut)->days;

                $totalAmount = $room->price_per_night * $nights;
                $request->merge(['total_amount' => $totalAmount]);
            }
        }

        $reservation->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Réservation mise à jour avec succès',
            'data' => $reservation->fresh(['user', 'room'])
        ]);
    }

    /**
     * Supprimer une réservation
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation non trouvée'
            ], 404);
        }

        // Vérifier s'il y a des paiements associés
        if ($reservation->payments()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une réservation avec des paiements. Annulez-la plutôt.'
            ], 400);
        }

        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Réservation supprimée avec succès'
        ]);
    }

    /**
     * Changer le statut d'une réservation
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,confirmed,checked_in,checked_out,cancelled',
            'notes' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $reservation->status;
        $newStatus = $request->status;

        $reservation->status = $newStatus;

        if ($request->has('notes')) {
            $reservation->notes = $request->notes;
        }

        $reservation->save();

        // Mettre à jour le statut de la chambre si nécessaire
        if ($newStatus === 'checked_in') {
            $room = Room::find($reservation->room_id);
            $room->status = 'occupied';
            $room->save();
        } elseif ($oldStatus === 'checked_in' && $newStatus === 'checked_out') {
            $room = Room::find($reservation->room_id);
            $room->status = 'available';
            $room->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut de la réservation mis à jour avec succès',
            'data' => $reservation->fresh(['user', 'room'])
        ]);
    }

    /**
     * Vérifier la disponibilité d'une chambre pour des dates spécifiques
     *
     * @param int $roomId
     * @param string $checkInDate
     * @param string $checkOutDate
     * @param int|null $excludeReservationId
     * @return bool
     */
    private function checkRoomAvailability(int $roomId, string $checkInDate, string $checkOutDate, ?int $excludeReservationId = null): bool
    {
        $room = Room::find($roomId);

        if (!$room || $room->status === 'maintenance') {
            return false;
        }

        $query = Reservation::where('room_id', $roomId)
            ->where('status', '!=', 'cancelled')
            ->where(function($query) use ($checkInDate, $checkOutDate) {
                // Nouvelle réservation : check-in avant le check-out existant ET check-out après le check-in existant
                $query->where(function($q) use ($checkInDate, $checkOutDate) {
                    $q->where('check_in_date', '<', $checkOutDate)
                      ->where('check_out_date', '>', $checkInDate);
                });
            });

        // Exclure la réservation en cours de mise à jour
        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        $conflictingReservations = $query->count();

        return $conflictingReservations === 0;
    }
}
