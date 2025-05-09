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
        // ... existing code ...
    }
}
