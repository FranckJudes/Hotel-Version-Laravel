# Documentation Swagger pour RoomController

Voici un exemple de la façon de documenter le RoomController avec Swagger :

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Chambres",
 *     description="API de gestion des chambres"
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
     *     tags={"Chambres"},
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
        // Code existant...
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/rooms",
     *     summary="Créer une nouvelle chambre",
     *     description="Crée une nouvelle chambre avec les informations fournies",
     *     operationId="createRoom",
     *     tags={"Chambres"},
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
     *             @OA\Property(property="data", type="object")
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
        // Code existant...
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/rooms/{id}",
     *     summary="Obtenir les détails d'une chambre",
     *     description="Récupère les informations détaillées d'une chambre spécifique",
     *     operationId="getRoom",
     *     tags={"Chambres"},
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
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chambre non trouvée"
     *     )
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Code existant...
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/rooms/{id}",
     *     summary="Mettre à jour une chambre",
     *     description="Met à jour les informations d'une chambre existante",
     *     operationId="updateRoom",
     *     tags={"Chambres"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la chambre à mettre à jour",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         description="Informations à mettre à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="number", type="string", example="102"),
     *             @OA\Property(property="type", type="string", example="deluxe"),
     *             @OA\Property(property="price_per_night", type="number", format="float", example=149.99),
     *             @OA\Property(property="capacity", type="integer", example=3),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="status", type="string", enum={"available", "occupied", "maintenance"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chambre mise à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Chambre mise à jour avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
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
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Code existant...
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/rooms/{id}",
     *     summary="Supprimer une chambre",
     *     description="Supprime une chambre existante et ses images associées",
     *     operationId="deleteRoom",
     *     tags={"Chambres"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la chambre à supprimer",
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
     *         response=404,
     *         description="Chambre non trouvée"
     *     )
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Code existant...
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/rooms/{id}/images",
     *     summary="Ajouter des images à une chambre",
     *     description="Télécharge et associe de nouvelles images à une chambre existante",
     *     operationId="addRoomImages",
     *     tags={"Chambres"},
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 ),
     *                 @OA\Property(
     *                     property="primary_index",
     *                     type="integer",
     *                     description="Index de l'image à définir comme principale"
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
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
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
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function addImages(Request $request, int $id): JsonResponse
    {
        // Code existant...
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/rooms/{roomId}/images/{imageId}",
     *     summary="Supprimer une image de chambre",
     *     description="Supprime une image spécifique associée à une chambre",
     *     operationId="deleteRoomImage",
     *     tags={"Chambres"},
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
     *         description="ID de l'image à supprimer",
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
     *         response=404,
     *         description="Image non trouvée"
     *     )
     * )
     *
     * @param int $roomId
     * @param int $imageId
     * @return JsonResponse
     */
    public function deleteImage(int $roomId, int $imageId): JsonResponse
    {
        // Code existant...
    }
} 
