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
     * @OA\Get(
     *     path="/api/v1/reservations",
     *     summary="Afficher toutes les réservations de l'utilisateur connecté",
     *     description="Récupère la liste des réservations de l'utilisateur connecté avec possibilité de filtrage",
     *     operationId="getClientReservations",
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
     *         name="upcoming",
     *         in="query",
     *         description="Filtrer les réservations à venir",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="past",
     *         in="query",
     *         description="Filtrer les réservations passées",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des réservations récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Reservation")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $query = Reservation::with(['room', 'room.images'])
            ->where('customer_email', Auth::user()->email);

        // Filtrer par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par date
        if ($request->has('upcoming') && $request->boolean('upcoming')) {
            $query->where('check_in', '>=', now());
        } elseif ($request->has('past') && $request->boolean('past')) {
            $query->where('check_out', '<', now());
        }

        // Tri par date d'arrivée
        $query->orderBy('check_in', $request->has('past') ? 'desc' : 'asc');

        $reservations = $query->get();

        // Transformer les données pour correspondre à la structure des fake data
        $bookingsData = $reservations->map(function($reservation) {
            return [
                'id' => $reservation->id,
                'roomId' => $reservation->room_id,
                'customerName' => $reservation->customer_name,
                'customerEmail' => $reservation->customer_email,
                'customerPhone' => $reservation->customer_phone,
                'checkIn' => $reservation->check_in,
                'checkOut' => $reservation->check_out,
                'status' => $reservation->status->value,
                'totalPrice' => $reservation->total_price,
                'paymentMethod' => $reservation->payment_method,
                'paymentStatus' => $reservation->payment_status,
                'createdAt' => $reservation->created_at,
                'guests' => $reservation->guests,
                'room' => [
                    'id' => $reservation->room->id,
                    'name' => $reservation->room->name,
                    'type' => $reservation->room->type->value,
                    'images' => $reservation->room->images->pluck('image_path')->toArray(),
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $bookingsData
        ]);
    }
     /**
     * @OA\Post(
     *     path="/api/v1/reservations",
     *     summary="Créer une nouvelle réservation",
     *     description="Crée une nouvelle réservation pour l'utilisateur connecté",
     *     operationId="createClientReservation",
     *     tags={"Réservations (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"room_id", "check_in", "check_out", "guests", "customer_name", "customer_email", "customer_phone", "payment_method"},
     *             @OA\Property(property="room_id", type="integer", example=1),
     *             @OA\Property(property="check_in", type="string", format="date", example="2023-12-01"),
     *             @OA\Property(property="check_out", type="string", format="date", example="2023-12-10"),
     *             @OA\Property(property="guests", type="integer", example=2),
     *             @OA\Property(property="customer_name", type="string", maxLength=255, example="John Doe"),
     *             @OA\Property(property="customer_email", type="string", format="email", maxLength=255, example="john@example.com"),
     *             @OA\Property(property="customer_phone", type="string", maxLength=20, example="+2250000000"),
     *             @OA\Property(property="payment_method", type="string", enum={"orange_money", "mtn_mobile_money", "credit_card", "cash"}, example="orange_money")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Réservation créée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Réservation créée avec succès. Veuillez procéder au paiement pour confirmer."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Reservation"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="La chambre n'est pas disponible pour les dates sélectionnées"
     *     )
     * )
     */
    public function clientStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'payment_method' => 'required|string|in:orange_money,mtn_mobile_money,credit_card,cash',
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
            $request->check_in,
            $request->check_out
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
        $checkIn = new \DateTime($request->check_in);
        $checkOut = new \DateTime($request->check_out);
        $nights = $checkIn->diff($checkOut)->days;

        // Calculer le montant total
        $totalPrice = $room->price * $nights;

        $reservation = Reservation::create([
            'room_id' => $request->room_id,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'status' => 'pending',
            'total_price' => $totalPrice,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'guests' => $request->guests,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Réservation créée avec succès. Veuillez procéder au paiement pour confirmer.',
            'data' => [
                'id' => $reservation->id,
                'roomId' => $reservation->room_id,
                'customerName' => $reservation->customer_name,
                'customerEmail' => $reservation->customer_email,
                'customerPhone' => $reservation->customer_phone,
                'checkIn' => $reservation->check_in,
                'checkOut' => $reservation->check_out,
                'status' => $reservation->status->value,
                'totalPrice' => $reservation->total_price,
                'paymentMethod' => $reservation->payment_method,
                'paymentStatus' => $reservation->payment_status,
                'guests' => $reservation->guests,
            ]
        ], 201);
    }
       /**
     * @OA\Get(
     *     path="/api/v1/reservations/{id}",
     *     summary="Afficher une réservation spécifique",
     *     description="Récupère les détails d'une réservation spécifique pour l'utilisateur connecté",
     *     operationId="getClientReservation",
     *     tags={"Réservations (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la réservation",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la réservation récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Reservation"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Réservation non trouvée ou vous n'êtes pas autorisé à la consulter"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/reservations/{id}/cancel",
     *     summary="Annuler une réservation",
     *     description="Annule une réservation spécifique pour l'utilisateur connecté",
     *     operationId="cancelClientReservation",
     *     tags={"Réservations (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la réservation",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Réservation annulée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Réservation annulée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Reservation"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Réservation non trouvée ou vous n'êtes pas autorisé à la modifier"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Les annulations doivent être effectuées au moins 2 jours avant la date d'arrivée"
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/admin/reservations",
     *     summary="Créer une nouvelle réservation",
     *     description="Crée une nouvelle réservation",
     *     operationId="createReservation",
     *     tags={"Réservations (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "room_id", "check_in_date", "check_out_date", "adults"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="room_id", type="integer", example=1),
     *             @OA\Property(property="check_in_date", type="string", format="date", example="2023-12-01"),
     *             @OA\Property(property="check_out_date", type="string", format="date", example="2023-12-10"),
     *             @OA\Property(property="adults", type="integer", example=2),
     *             @OA\Property(property="children", type="integer", example=0),
     *             @OA\Property(property="special_requests", type="string", example="Lit bébé requis")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Réservation créée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Réservation créée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Reservation"
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
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="La chambre n'est pas disponible pour les dates sélectionnées"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'status' => 'required|string|in:pending,confirmed,checked_in,checked_out,cancelled,completed',
            'total_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:credit_card,cash,bank_transfer,orange_money,mtn_mobile_money',
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
            'guests' => 'required|integer|min:1',
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
            $request->check_in,
            $request->check_out
        );

        if (!$isRoomAvailable) {
            return response()->json([
                'success' => false,
                'message' => 'La chambre n\'est pas disponible pour les dates sélectionnées'
            ], 400);
        }

        // Créer la réservation
        $reservation = Reservation::create([
            'room_id' => $request->room_id,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'status' => $request->status,
            'total_price' => $request->total_price,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'guests' => $request->guests,
        ]);

        // Récupérer la réservation avec les informations de la chambre
        $reservation->load('room', 'room.images');

        return response()->json([
            'success' => true,
            'message' => 'Réservation créée avec succès',
            'data' => [
                'id' => $reservation->id,
                'roomId' => $reservation->room_id,
                'customerName' => $reservation->customer_name,
                'customerEmail' => $reservation->customer_email,
                'customerPhone' => $reservation->customer_phone,
                'checkIn' => $reservation->check_in,
                'checkOut' => $reservation->check_out,
                'status' => $reservation->status->value,
                'totalPrice' => $reservation->total_price,
                'paymentMethod' => $reservation->payment_method,
                'paymentStatus' => $reservation->payment_status,
                'createdAt' => $reservation->created_at,
                'guests' => $reservation->guests,
                'room' => [
                    'id' => $reservation->room->id,
                    'name' => $reservation->room->name,
                    'type' => $reservation->room->type->value,
                    'images' => $reservation->room->images->pluck('image_path')->toArray(),
                ]
            ]
        ], 201);
    }
      /**
     * @OA\Get(
     *     path="/api/v1/admin/reservations/{id}",
     *     summary="Afficher une réservation spécifique",
     *     description="Récupère les détails d'une réservation spécifique",
     *     operationId="getReservation",
     *     tags={"Réservations (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la réservation",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la réservation récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Reservation"
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Réservation non trouvée"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/admin/reservations/{id}",
     *     summary="Mettre à jour une réservation",
     *     description="Met à jour une réservation spécifique",
     *     operationId="updateReservation",
     *     tags={"Réservations (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la réservation",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="room_id", type="integer", example=1),
     *             @OA\Property(property="check_in_date", type="string", format="date", example="2023-12-01"),
     *             @OA\Property(property="check_out_date", type="string", format="date", example="2023-12-10"),
     *             @OA\Property(property="adults", type="integer", example=2),
     *             @OA\Property(property="children", type="integer", example=0),
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "checked_in", "checked_out", "cancelled", "completed"}, example="confirmed"),
     *             @OA\Property(property="special_requests", type="string", example="Lit bébé requis")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Réservation mise à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Réservation mise à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Reservation"
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Réservation non trouvée"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="La chambre n'est pas disponible pour les dates sélectionnées"
     *     )
     * )
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
            'room_id' => 'sometimes|exists:rooms,id',
            'customer_name' => 'sometimes|string|max:255',
            'customer_email' => 'sometimes|email|max:255',
            'customer_phone' => 'sometimes|string|max:20',
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after:check_in',
            'status' => 'sometimes|string|in:pending,confirmed,checked_in,checked_out,cancelled,completed',
            'total_price' => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|string|in:credit_card,cash,bank_transfer,orange_money,mtn_mobile_money',
            'payment_status' => 'sometimes|string|in:pending,paid,failed,refunded',
            'guests' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si la chambre a changé et si la nouvelle chambre est disponible
        if ($request->has('room_id') && $request->room_id != $reservation->room_id) {
            $isRoomAvailable = $this->checkRoomAvailability(
                $request->room_id,
                $request->check_in ?? $reservation->check_in,
                $request->check_out ?? $reservation->check_out,
                $reservation->id
            );

            if (!$isRoomAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => 'La chambre n\'est pas disponible pour les dates sélectionnées'
                ], 400);
            }
        }

        // Vérifier si les dates ont changé et si la chambre est disponible pour les nouvelles dates
        if (($request->has('check_in') || $request->has('check_out')) && !$request->has('room_id')) {
            $isRoomAvailable = $this->checkRoomAvailability(
                $reservation->room_id,
                $request->check_in ?? $reservation->check_in,
                $request->check_out ?? $reservation->check_out,
                $reservation->id
            );

            if (!$isRoomAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => 'La chambre n\'est pas disponible pour les dates sélectionnées'
                ], 400);
            }
        }

        // Mettre à jour les champs modifiés
        $reservation->fill($request->only([
            'room_id',
            'customer_name',
            'customer_email',
            'customer_phone',
            'check_in',
            'check_out',
            'status',
            'total_price',
            'payment_method',
            'payment_status',
            'guests',
        ]));

        $reservation->save();

        // Récupérer la réservation mise à jour avec les informations de la chambre
        $reservation->load('room', 'room.images');

        return response()->json([
            'success' => true,
            'message' => 'Réservation mise à jour avec succès',
            'data' => [
                'id' => $reservation->id,
                'roomId' => $reservation->room_id,
                'customerName' => $reservation->customer_name,
                'customerEmail' => $reservation->customer_email,
                'customerPhone' => $reservation->customer_phone,
                'checkIn' => $reservation->check_in,
                'checkOut' => $reservation->check_out,
                'status' => $reservation->status->value,
                'totalPrice' => $reservation->total_price,
                'paymentMethod' => $reservation->payment_method,
                'paymentStatus' => $reservation->payment_status,
                'createdAt' => $reservation->created_at,
                'guests' => $reservation->guests,
                'room' => [
                    'id' => $reservation->room->id,
                    'name' => $reservation->room->name,
                    'type' => $reservation->room->type->value,
                    'images' => $reservation->room->images->pluck('image_path')->toArray(),
                ]
            ]
        ]);
    }
    /**
     * @OA\Delete(
     *     path="/api/v1/admin/reservations/{id}",
     *     summary="Supprimer une réservation",
     *     description="Supprime une réservation spécifique",
     *     operationId="deleteReservation",
     *     tags={"Réservations (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la réservation",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Réservation supprimée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Réservation supprimée avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit - Droits administrateur requis"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Réservation non trouvée"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Impossible de supprimer une réservation avec des paiements. Annulez-la plutôt."
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/admin/reservations/{id}/status",
     *     summary="Changer le statut d'une réservation",
     *     description="Change le statut d'une réservation spécifique",
     *     operationId="changeReservationStatus",
     *     tags={"Réservations (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la réservation",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "checked_in", "checked_out", "cancelled", "completed"}, example="confirmed"),
     *             @OA\Property(property="notes", type="string", example="Notes supplémentaires")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut de la réservation mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statut de la réservation mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Reservation"
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Réservation non trouvée"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
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
            'status' => 'required|string|in:pending,confirmed,checked_in,checked_out,cancelled,completed',
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
        $room = Room::findOrFail($roomId);

        if (!$room->available) {
            return false;
        }

        $query = Reservation::where('room_id', $roomId)
            ->where(function ($query) use ($checkInDate, $checkOutDate) {
                $query->whereBetween('check_in', [$checkInDate, $checkOutDate])
                    ->orWhereBetween('check_out', [$checkInDate, $checkOutDate])
                    ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->where('check_in', '<=', $checkInDate)
                            ->where('check_out', '>=', $checkOutDate);
                    });
            })
            ->whereIn('status', ['pending', 'confirmed', 'checked_in']);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return !$query->exists();
    }
}
