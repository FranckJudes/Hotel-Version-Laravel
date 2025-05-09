<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Chambres (Admin)",
 *     description="API de gestion des chambres - Partie admin"
 * )
 */
class RoomController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/rooms",
     *     summary="Récupérer toutes les chambres",
     *     description="Récupère la liste de toutes les chambres disponibles dans l'hôtel",
     *     operationId="getRooms",
     *     tags={"Chambres (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des chambres récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="number", type="string", example="101"),
     *                     @OA\Property(property="type", type="string", example="standard"),
     *                     @OA\Property(property="price_per_night", type="number", format="float", example=99.99),
     *                     @OA\Property(property="capacity", type="integer", example=2),
     *                     @OA\Property(property="description", type="string", example="Chambre standard avec vue sur le jardin"),
     *                     @OA\Property(property="features", type="array", @OA\Items(type="string"), example={"wifi", "climatisation"}),
     *                     @OA\Property(property="status", type="string", enum={"available", "occupied", "maintenance"}, example="available"),
     *                     @OA\Property(
     *                         property="images",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="room_id", type="integer", example=1),
     *                             @OA\Property(property="image_path", type="string", example="rooms/room1.jpg"),
     *                             @OA\Property(property="is_primary", type="boolean", example=true)
     *                         )
     *                     )
     *                 )
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
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $rooms = Room::with('images')->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rooms",
     *     summary="Récupérer les chambres disponibles (public)",
     *     description="Récupère la liste des chambres disponibles avec filtres",
     *     operationId="getPublicRooms",
     *     tags={"Chambres (Public)"},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de chambre",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="capacity",
     *         in="query",
     *         description="Capacité minimale",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Prix minimum par nuit",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Prix maximum par nuit",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="features",
     *         in="query",
     *         description="Fonctionnalités séparées par des virgules",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"price_per_night", "capacity"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer")
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
     *         description="Liste des chambres disponibles récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/PaginatedRoomsList"
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $query = Room::with('images')
            ->where('status', 'available');

        // Filtrer par type de chambre
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filtrer par capacité
        if ($request->has('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        // Filtrer par prix
        if ($request->has('min_price')) {
            $query->where('price_per_night', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        // Filtrer par fonctionnalités
        if ($request->has('features')) {
            $features = explode(',', $request->features);
            foreach ($features as $feature) {
                $query->whereJsonContains('features', trim($feature));
            }
        }

        // Tri
        $sortBy = $request->sort_by ?? 'price_per_night';
        $sortOrder = $request->sort_order ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $rooms = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/rooms",
     *     summary="Créer une nouvelle chambre",
     *     description="Crée une nouvelle chambre avec les informations fournies",
     *     operationId="createRoom",
     *     tags={"Chambres (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informations de la chambre",
     *         @OA\JsonContent(
     *             required={"number", "type", "price_per_night", "capacity", "description", "status"},
     *             @OA\Property(property="number", type="string", example="101"),
     *             @OA\Property(property="type", type="string", example="standard"),
     *             @OA\Property(property="price_per_night", type="number", format="float", example=99.99),
     *             @OA\Property(property="capacity", type="integer", example=2),
     *             @OA\Property(property="description", type="string", example="Chambre standard avec vue sur le jardin"),
     *             @OA\Property(property="features", type="array", @OA\Items(type="string"), example={"wifi", "climatisation"}),
     *             @OA\Property(property="status", type="string", enum={"available", "occupied", "maintenance"}, example="available"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Chambre créée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Chambre créée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Room"
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
     *         description="Données invalides"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|unique:rooms,number',
            'type' => 'required|string',
            'price_per_night' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'description' => 'required|string',
            'features' => 'sometimes|array',
            'status' => 'required|string|in:available,occupied,maintenance',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $room = Room::create([
            'number' => $request->number,
            'type' => $request->type,
            'price_per_night' => $request->price_per_night,
            'capacity' => $request->capacity,
            'description' => $request->description,
            'features' => $request->features ?? [],
            'status' => $request->status
        ]);

        // Traitement des images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('rooms', 'public');

                RoomImage::create([
                    'room_id' => $room->id,
                    'image_path' => $path,
                    'is_primary' => false
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Chambre créée avec succès',
            'data' => $room->load('images')
        ], 201);
    }

    /**
     * Afficher une chambre spécifique
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $room = Room::with('images')->find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Chambre non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }

    /**
     * Afficher une chambre spécifique (endpoint public)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function publicShow(int $id): JsonResponse
    {
        $room = Room::with('images')->find($id);

        if (!$room || $room->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Chambre non trouvée ou non disponible'
            ], 404);
        }

        // Récupérer la disponibilité pour les 30 prochains jours
        $today = now();
        $availability = [];

        for ($i = 0; $i < 30; $i++) {
            $date = $today->copy()->addDays($i);
            $isAvailable = true;

            // Ici vous implémenteriez la logique réelle pour vérifier si la chambre est déjà réservée pour cette date

            $availability[] = [
                'date' => $date->format('Y-m-d'),
                'available' => $isAvailable
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'room' => $room,
                'availability' => $availability
            ]
        ]);
    }

    /**
     * Mettre à jour une chambre
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Chambre non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'number' => 'sometimes|string|unique:rooms,number,' . $id,
            'type' => 'sometimes|string',
            'price_per_night' => 'sometimes|numeric|min:0',
            'capacity' => 'sometimes|integer|min:1',
            'description' => 'sometimes|string',
            'features' => 'sometimes|array',
            'status' => 'sometimes|string|in:available,occupied,maintenance'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $room->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Chambre mise à jour avec succès',
            'data' => $room->fresh(['images'])
        ]);
    }

    /**
     * Supprimer une chambre
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $room = Room::with('images')->find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Chambre non trouvée'
            ], 404);
        }

        // Supprimer les images associées
        foreach ($room->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chambre supprimée avec succès'
        ]);
    }

    /**
     * Ajouter des images à une chambre
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function addImages(Request $request, int $id): JsonResponse
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Chambre non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'primary_index' => 'sometimes|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $newImages = [];
        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('rooms', 'public');

            $isPrimary = ($request->has('primary_index') && $request->primary_index == $index);

            // Si une image est marquée comme primaire, enlever ce statut des autres images
            if ($isPrimary) {
                RoomImage::where('room_id', $room->id)->update(['is_primary' => false]);
            }

            $roomImage = RoomImage::create([
                'room_id' => $room->id,
                'image_path' => $path,
                'is_primary' => $isPrimary
            ]);

            $newImages[] = $roomImage;
        }

        return response()->json([
            'success' => true,
            'message' => 'Images ajoutées avec succès',
            'data' => $newImages
        ]);
    }

    /**
     * Supprimer une image de chambre
     *
     * @param int $roomId
     * @param int $imageId
     * @return JsonResponse
     */
    public function deleteImage(int $roomId, int $imageId): JsonResponse
    {
        $image = RoomImage::where('room_id', $roomId)->where('id', $imageId)->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvée'
            ], 404);
        }

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image supprimée avec succès'
        ]);
    }
}
