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
     */
    public function index(): JsonResponse
    {
        $testimonials = Testimonial::all();

        // Transformer les données pour correspondre à la structure des fake data
        $testimonialsData = $testimonials->map(function($testimonial) {
            return [
                'id' => $testimonial->id,
                'author' => $testimonial->author,
                'rating' => $testimonial->rating,
                'content' => $testimonial->content,
                'date' => $testimonial->date,
                'approved' => $testimonial->approved,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $testimonialsData
        ]);
    }
      /**
     * @OA\Get(
     *     path="/api/v1/testimonials/approuved",
     *     summary="Afficher tous les témoignages approuvés (endpoint public)",
     *     description="Récupère la liste des témoignages approuvés avec possibilité de filtrage",
     *     operationId="getPublicTestimonials",
     *     tags={"Témoignages (Public)"},
     *     @OA\Parameter(
     *         name="min_rating",
     *         in="query",
     *         description="Note minimale",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=5)
     *     ),
     *     @OA\Parameter(
     *         name="highlighted_only",
     *         in="query",
     *         description="Récupérer uniquement les témoignages mis en avant",
     *         required=false,
     *         @OA\Schema(type="boolean")
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
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/testimonials",
     *     summary="Afficher les témoignages de l'utilisateur connecté",
     *     description="Récupère la liste des témoignages de l'utilisateur connecté",
     *     operationId="getClientTestimonials",
     *     tags={"Témoignages (Client)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des témoignages récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Testimonial")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/testimonials",
     *     summary="Créer un nouveau témoignage (côté client)",
     *     description="Crée un nouveau témoignage pour l'utilisateur connecté",
     *     operationId="createClientTestimonial",
     *     tags={"Témoignages (Client)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informations du témoignage",
     *         @OA\JsonContent(
     *             required={"title", "content", "rating"},
     *             @OA\Property(property="title", type="string", example="Excellent séjour"),
     *             @OA\Property(property="content", type="string", example="J'ai passé un excellent séjour, tout était parfait."),
     *             @OA\Property(property="rating", type="integer", example=5, minimum=1, maximum=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Témoignage créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Témoignage soumis avec succès. Il sera visible après modération."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Testimonial"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Vous avez déjà soumis un témoignage ces 30 derniers jours"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/testimonials/{id}",
     *     summary="Mettre à jour un témoignage (côté client)",
     *     description="Met à jour un témoignage spécifique pour l'utilisateur connecté",
     *     operationId="updateClientTestimonial",
     *     tags={"Témoignages (Client)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du témoignage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informations du témoignage à mettre à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Excellent séjour"),
     *             @OA\Property(property="content", type="string", example="J'ai passé un excellent séjour, tout était parfait."),
     *             @OA\Property(property="rating", type="integer", example=5, minimum=1, maximum=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Témoignage mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Témoignage mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Testimonial"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Témoignage non trouvé ou vous n'êtes pas autorisé à le modifier"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Impossible de modifier un témoignage déjà approuvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/v1/testimonials/{id}",
     *     summary="Supprimer un témoignage (côté client)",
     *     description="Supprime un témoignage spécifique pour l'utilisateur connecté",
     *     operationId="deleteClientTestimonial",
     *     tags={"Témoignages (Client)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du témoignage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Témoignage supprimé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Témoignage supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Témoignage non trouvé ou vous n'êtes pas autorisé à le supprimer"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/admin/testimonials/{id}",
     *     summary="Afficher un témoignage spécifique",
     *     description="Récupère les détails d'un témoignage spécifique",
     *     operationId="getTestimonial",
     *     tags={"Témoignages (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du témoignage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du témoignage récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Testimonial"
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
     *         description="Témoignage non trouvé"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/admin/testimonials/{id}",
     *     summary="Mettre à jour un témoignage (modération)",
     *     description="Met à jour un témoignage spécifique (modération)",
     *     operationId="updateTestimonial",
     *     tags={"Témoignages (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du témoignage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informations du témoignage à mettre à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved"),
     *             @OA\Property(property="admin_notes", type="string", example="Notes de modération"),
     *             @OA\Property(property="highlighted", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Témoignage mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Témoignage mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Testimonial"
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
     *         description="Témoignage non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides"
     *     )
     * )
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
            'author' => 'sometimes|string|max:255',
            'rating' => 'sometimes|integer|min:1|max:5',
            'content' => 'sometimes|string',
            'date' => 'sometimes|date',
            'approved' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $testimonial->fill($request->only([
            'author',
            'rating',
            'content',
            'date',
            'approved',
        ]));

        $testimonial->save();

        return response()->json([
            'success' => true,
            'message' => 'Témoignage mis à jour avec succès',
            'data' => [
                'id' => $testimonial->id,
                'author' => $testimonial->author,
                'rating' => $testimonial->rating,
                'content' => $testimonial->content,
                'date' => $testimonial->date,
                'approved' => $testimonial->approved,
            ]
        ]);
    }
       /**
     * @OA\Put(
     *     path="/api/v1/admin/testimonials/{id}/status",
     *     summary="Changer le statut d'un témoignage",
     *     description="Change le statut d'un témoignage spécifique",
     *     operationId="changeTestimonialStatus",
     *     tags={"Témoignages (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du témoignage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Nouveau statut du témoignage",
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved"),
     *             @OA\Property(property="admin_notes", type="string", example="Notes de modération")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut du témoignage mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statut du témoignage mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Testimonial"
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
     *         description="Témoignage non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/admin/testimonials/{id}/highlight",
     *     summary="Mettre en avant un témoignage",
     *     description="Met en avant un témoignage spécifique",
     *     operationId="highlightTestimonial",
     *     tags={"Témoignages (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du témoignage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Témoignage mis en avant avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Témoignage mis en avant avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Testimonial"
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
     *         description="Témoignage non trouvé"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Seuls les témoignages approuvés peuvent être mis en avant"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/admin/testimonials/{id}/unhighlight",
     *     summary="Retirer la mise en avant d'un témoignage",
     *     description="Retire la mise en avant d'un témoignage spécifique",
     *     operationId="unhighlightTestimonial",
     *     tags={"Témoignages (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du témoignage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mise en avant du témoignage retirée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mise en avant du témoignage retirée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Testimonial"
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
     *         description="Témoignage non trouvé"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/v1/admin/testimonials/{id}",
     *     summary="Supprimer un témoignage",
     *     description="Supprime un témoignage spécifique",
     *     operationId="deleteTestimonial",
     *     tags={"Témoignages (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du témoignage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Témoignage supprimé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Témoignage supprimé avec succès")
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
     *         description="Témoignage non trouvé"
     *     )
     * )
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

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'author' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string',
            'date' => 'sometimes|date',
            'approved' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $testimonial = Testimonial::create([
            'author' => $request->author,
            'rating' => $request->rating,
            'content' => $request->content,
            'date' => $request->date ?? now(),
            'approved' => $request->boolean('approved', false), // Par défaut, les témoignages ne sont pas approuvés
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Témoignage créé avec succès',
            'data' => [
                'id' => $testimonial->id,
                'author' => $testimonial->author,
                'rating' => $testimonial->rating,
                'content' => $testimonial->content,
                'date' => $testimonial->date,
                'approved' => $testimonial->approved,
            ]
        ], 201);
    }
}
