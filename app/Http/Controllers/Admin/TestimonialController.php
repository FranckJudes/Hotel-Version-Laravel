<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Témoignages (Admin)",
 *     description="API de gestion des témoignages clients - Partie admin"
 * )
 */
class TestimonialController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/testimonials",
     *     summary="Récupérer tous les témoignages",
     *     description="Récupère la liste de tous les témoignages avec possibilité de filtrage",
     *     operationId="getAdminTestimonials",
     *     tags={"Témoignages (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "approved", "rejected"})
     *     ),
     *     @OA\Parameter(
     *         name="min_rating",
     *         in="query",
     *         description="Note minimale",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=5)
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filtrer par utilisateur",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titre ou contenu",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Champ pour le tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created_at", "rating", "status"})
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
     *         description="Liste des témoignages récupérée avec succès",
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
     *                     @OA\Items(ref="#/components/schemas/Testimonial")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=30)
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
     * Afficher tous les témoignages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Testimonial::with('user');

        // Filtrer par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par note minimale
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Filtrer par utilisateur
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Recherche par contenu
        if ($request->has('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('content', 'like', $search);
            });
        }

        // Tri
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $testimonials = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $testimonials
        ]);
    }

    /**
     * Afficher tous les témoignages approuvés (endpoint public)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $query = Testimonial::with(['user:id,username,first_name,last_name'])
            ->where('status', 'approved');

        // Filtrer par note minimale
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Mettre en priorité les témoignages mis en avant
        $query->orderBy('highlighted', 'desc');

        // Tri par notation et date
        $query->orderBy('rating', 'desc')
              ->orderBy('created_at', 'desc');

        $perPage = $request->per_page ?? 6;

        // Si demandé, récupérer uniquement les témoignages mis en avant
        if ($request->boolean('highlighted_only')) {
            $query->where('highlighted', true);
            $perPage = $request->per_page ?? 3;
        }

        $testimonials = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $testimonials
        ]);
    }

    /**
     * Afficher les témoignages de l'utilisateur connecté
     *
     * @return JsonResponse
     */
    public function clientIndex(): JsonResponse
    {
        $testimonials = Testimonial::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $testimonials
        ]);
    }

    /**
     * Créer un nouveau témoignage (côté client)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clientStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'rating' => 'required|integer|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'utilisateur a déjà soumis un témoignage récemment
        $recentTestimonial = Testimonial::where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();

        if ($recentTestimonial) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà soumis un témoignage ces 30 derniers jours'
            ], 400);
        }

        $testimonial = Testimonial::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'rating' => $request->rating,
            'status' => 'pending',
            'highlighted' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Témoignage soumis avec succès. Il sera visible après modération.',
            'data' => $testimonial
        ], 201);
    }

    /**
     * Mettre à jour un témoignage (côté client)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function clientUpdate(Request $request, int $id): JsonResponse
    {
        $testimonial = Testimonial::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$testimonial) {
            return response()->json([
                'success' => false,
                'message' => 'Témoignage non trouvé ou vous n\'êtes pas autorisé à le modifier'
            ], 404);
        }

        // Vérifier si le témoignage est déjà approuvé
        if ($testimonial->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier un témoignage déjà approuvé'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'rating' => 'sometimes|integer|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $testimonial->update($request->all());

        // Remettre le témoignage en attente s'il a été rejeté
        if ($testimonial->status === 'rejected') {
            $testimonial->status = 'pending';
            $testimonial->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Témoignage mis à jour avec succès',
            'data' => $testimonial
        ]);
    }

    /**
     * Supprimer un témoignage (côté client)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function clientDestroy(int $id): JsonResponse
    {
        $testimonial = Testimonial::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$testimonial) {
            return response()->json([
                'success' => false,
                'message' => 'Témoignage non trouvé ou vous n\'êtes pas autorisé à le supprimer'
            ], 404);
        }

        $testimonial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Témoignage supprimé avec succès'
        ]);
    }

    /**
     * Afficher un témoignage spécifique
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $testimonial = Testimonial::with('user')->find($id);

        if (!$testimonial) {
            return response()->json([
                'success' => false,
                'message' => 'Témoignage non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $testimonial
        ]);
    }

    /**
     * Mettre à jour un témoignage (modération)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $testimonial = Testimonial::find($id);

        if (!$testimonial) {
            return response()->json([
                'success' => false,
                'message' => 'Témoignage non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,approved,rejected',
            'admin_notes' => 'sometimes|string',
            'highlighted' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $testimonial->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Témoignage mis à jour avec succès',
            'data' => $testimonial->fresh('user')
        ]);
    }

    /**
     * Changer le statut d'un témoignage
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $testimonial = Testimonial::find($id);

        if (!$testimonial) {
            return response()->json([
                'success' => false,
                'message' => 'Témoignage non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,approved,rejected',
            'admin_notes' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $testimonial->status = $request->status;

        if ($request->has('admin_notes')) {
            $testimonial->admin_notes = $request->admin_notes;
        }

        $testimonial->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut du témoignage mis à jour avec succès',
            'data' => $testimonial->fresh('user')
        ]);
    }

    /**
     * Mettre en avant un témoignage
     *
     * @param int $id
     * @return JsonResponse
     */
    public function highlight(int $id): JsonResponse
    {
        $testimonial = Testimonial::find($id);

        if (!$testimonial) {
            return response()->json([
                'success' => false,
                'message' => 'Témoignage non trouvé'
            ], 404);
        }

        // Vérifier si le témoignage est approuvé
        if ($testimonial->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les témoignages approuvés peuvent être mis en avant'
            ], 400);
        }

        $testimonial->highlighted = true;
        $testimonial->save();

        return response()->json([
            'success' => true,
            'message' => 'Témoignage mis en avant avec succès',
            'data' => $testimonial->fresh('user')
        ]);
    }

    /**
     * Retirer la mise en avant d'un témoignage
     *
     * @param int $id
     * @return JsonResponse
     */
    public function unhighlight(int $id): JsonResponse
    {
        $testimonial = Testimonial::find($id);

        if (!$testimonial) {
            return response()->json([
                'success' => false,
                'message' => 'Témoignage non trouvé'
            ], 404);
        }

        $testimonial->highlighted = false;
        $testimonial->save();

        return response()->json([
            'success' => true,
            'message' => 'Mise en avant du témoignage retirée avec succès',
            'data' => $testimonial->fresh('user')
        ]);
    }

    /**
     * Supprimer un témoignage
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $testimonial = Testimonial::find($id);

        if (!$testimonial) {
            return response()->json([
                'success' => false,
                'message' => 'Témoignage non trouvé'
            ], 404);
        }

        $testimonial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Témoignage supprimé avec succès'
        ]);
    }
}
