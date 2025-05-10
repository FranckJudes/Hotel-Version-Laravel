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
     *                     @OA\Property(property="name", type="string", example="101"),
     *                     @OA\Property(property="type", type="string", example="standard"),
     *                     @OA\Property(property="price", type="number", format="float", example=99.99),
     *                     @OA\Property(property="capacity", type="integer", example=2),
     *                     @OA\Property(property="description", type="string", example="Chambre standard avec vue sur le jardin"),
     *                     @OA\Property(property="amenities", type="array", @OA\Items(type="string"), example={"wifi", "climatisation"}),
     *                     @OA\Property(property="available", type="boolean", example=true),
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
     */
    public function index(): JsonResponse
    {
        $rooms = Room::with('images')->get();

        // Transformer les données pour correspondre à la structure des fake data
        $roomsData = $rooms->map(function($room) {
            return [
                'id' => $room->id,
                'name' => $room->name,
                'description' => $room->description,
                'price' => $room->price,
                'capacity' => $room->capacity,
                'type' => $room->type->value,
                'images' => $room->images->pluck('image_path')->toArray(),
                'amenities' => $room->amenities,
                'available' => $room->available,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $roomsData
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
     *         name="amenities",
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
     *         @OA\Schema(type="string", enum={"price", "capacity"})
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
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $query = Room::with('images')
            ->where('available', true);

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
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filtrer par fonctionnalités
        if ($request->has('amenities')) {
            $amenities = explode(',', $request->amenities);
            foreach ($amenities as $amenity) {
                $query->whereJsonContains('amenities', trim($amenity));
            }
        }

        // Tri
        $sortBy = $request->get('sort_by', 'price');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $rooms = $query->paginate($perPage);

        // Transformer les données pour correspondre à la structure des fake data
        $roomsData = $rooms->getCollection()->map(function($room) {
            return [
                'id' => $room->id,
                'name' => $room->name,
                'description' => $room->description,
                'price' => $room->price,
                'capacity' => $room->capacity,
                'type' => $room->type->value,
                'images' => $room->images->pluck('image_path')->toArray(),
                'amenities' => $room->amenities,
                'available' => $room->available,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'rooms' => $roomsData,
                'pagination' => [
                    'current_page' => $rooms->currentPage(),
                    'per_page' => $rooms->perPage(),
                    'total' => $rooms->total(),
                    'last_page' => $rooms->lastPage(),
                ]
            ]
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
     *             required={"name", "description", "price", "capacity", "type", "amenities", "available"},
     *             @OA\Property(property="name", type="string", example="101"),
     *             @OA\Property(property="description", type="string", example="Chambre standard avec vue sur le jardin"),
     *             @OA\Property(property="price", type="number", format="float", example=99.99),
     *             @OA\Property(property="capacity", type="integer", example=2),
     *             @OA\Property(property="type", type="string", example="standard"),
     *             @OA\Property(property="amenities", type="array", @OA\Items(type="string"), example={"wifi", "climatisation"}),
     *             @OA\Property(property="available", type="boolean", example=true),
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
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|integer|min:0',
            'capacity' => 'required|integer|min:1',
            'type' => 'required|string|in:standard,vip,suite,luxe,duplex',
            'amenities' => 'required|array',
            'available' => 'required|boolean',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Créer la chambre
        $room = Room::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'capacity' => $request->capacity,
            'type' => $request->type,
            'amenities' => $request->amenities,
            'available' => $request->available,
        ]);

        // Ajouter les images si fournies
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('rooms', 'public');

                RoomImage::create([
                    'room_id' => $room->id,
                    'image_path' => $path,
                ]);
            }
        }

        // Récupérer la chambre avec les images
        $room = Room::with('images')->find($room->id);

        return response()->json([
            'success' => true,
            'message' => 'Chambre créée avec succès',
            'data' => [
                'id' => $room->id,
                'name' => $room->name,
                'description' => $room->description,
                'price' => $room->price,
                'capacity' => $room->capacity,
                'type' => $room->type->value,
                'images' => $room->images->pluck('image_path')->toArray(),
                'amenities' => $room->amenities,
                'available' => $room->available,
            ]
        ], 201);
    }
     /**
     * @OA\Get(
     *     path="/api/v1/admin/rooms/{id}",
     *     summary="Afficher une chambre spécifique",
     *     description="Récupère les détails d'une chambre spécifique",
     *     operationId="getRoom",
     *     tags={"Chambres (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la chambre",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la chambre récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
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
     *         response=404,
     *         description="Chambre non trouvée"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/rooms/{id}",
     *     summary="Afficher une chambre spécifique (endpoint public)",
     *     description="Récupère les détails d'une chambre spécifique disponible",
     *     operationId="getPublicRoom",
     *     tags={"Chambres (Public)"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la chambre",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la chambre récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="room",
     *                     ref="#/components/schemas/Room"
     *                 ),
     *                 @OA\Property(
     *                     property="availability",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="date", type="string", format="date", example="2023-12-01"),
     *                         @OA\Property(property="available", type="boolean", example=true)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chambre non trouvée ou non disponible"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/admin/rooms/{id}",
     *     summary="Mettre à jour une chambre",
     *     description="Met à jour les informations d'une chambre spécifique",
     *     operationId="updateRoom",
     *     tags={"Chambres (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la chambre",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informations de la chambre à mettre à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="101"),
     *             @OA\Property(property="description", type="string", example="Chambre standard avec vue sur le jardin"),
     *             @OA\Property(property="price", type="number", format="float", example=99.99),
     *             @OA\Property(property="capacity", type="integer", example=2),
     *             @OA\Property(property="type", type="string", example="standard"),
     *             @OA\Property(property="amenities", type="array", @OA\Items(type="string"), example={"wifi", "climatisation"}),
     *             @OA\Property(property="available", type="boolean", example=true),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chambre mise à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Chambre mise à jour avec succès"),
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
     *         response=404,
     *         description="Chambre non trouvée"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides"
     *     )
     * )
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
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|integer|min:0',
            'capacity' => 'sometimes|integer|min:1',
            'type' => 'sometimes|string|in:standard,vip,suite,luxe,duplex',
            'amenities' => 'sometimes|array',
            'available' => 'sometimes|boolean',
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
     * @OA\Delete(
     *     path="/api/v1/admin/rooms/{id}",
     *     summary="Supprimer une chambre",
     *     description="Supprime une chambre spécifique",
     *     operationId="deleteRoom",
     *     tags={"Chambres (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la chambre",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chambre supprimée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Chambre supprimée avec succès")
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
     *         description="Chambre non trouvée"
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/admin/rooms/{id}/images",
     *     summary="Ajouter des images à une chambre",
     *     description="Ajoute des images à une chambre spécifique",
     *     operationId="addRoomImages",
     *     tags={"Chambres (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la chambre",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Images à ajouter",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"images"},
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 ),
     *                 @OA\Property(
     *                     property="primary_index",
     *                     type="integer",
     *                     description="Index de l'image principale dans le tableau des images",
     *                     example=0
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Images ajoutées avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Images ajoutées avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/RoomImage")
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
     *         description="Chambre non trouvée"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/v1/admin/rooms/{roomId}/images/{imageId}",
     *     summary="Supprimer une image de chambre",
     *     description="Supprime une image spécifique d'une chambre",
     *     operationId="deleteRoomImage",
     *     tags={"Chambres (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="roomId",
     *         in="path",
     *         description="ID de la chambre",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="imageId",
     *         in="path",
     *         description="ID de l'image",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image supprimée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Image supprimée avec succès")
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
     *         description="Image non trouvée"
     *     )
     * )
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
