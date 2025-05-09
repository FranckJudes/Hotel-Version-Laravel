<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogPostTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Blog (Admin)",
 *     description="API de gestion des articles de blog - Partie admin"
 * )
 */
class BlogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/blog",
     *     summary="Récupérer tous les articles de blog",
     *     description="Récupère la liste de tous les articles de blog avec possibilité de filtrage",
     *     operationId="getAdminBlogPosts",
     *     tags={"Blog (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft", "published", "archived"})
     *     ),
     *     @OA\Parameter(
     *         name="author_id",
     *         in="query",
     *         description="Filtrer par auteur",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="tag",
     *         in="query",
     *         description="Filtrer par tag",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         @OA\Schema(type="string", enum={"created_at", "title", "published_at"})
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
     *         description="Liste des articles récupérée avec succès",
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
     *                     @OA\Items(ref="#/components/schemas/BlogPost")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=15)
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
     * Afficher tous les articles du blog
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = BlogPost::with(['author', 'tags']);

        // Filtrer par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par auteur
        if ($request->has('author_id')) {
            $query->where('author_id', $request->author_id);
        }

        // Filtrer par tag
        if ($request->has('tag')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('name', $request->tag);
            });
        }

        // Recherche par titre ou contenu
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

        $blogPosts = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $blogPosts
        ]);
    }

    /**
     * Afficher tous les articles publiés du blog (endpoint public)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $query = BlogPost::with(['author:id,username,first_name,last_name', 'tags'])
            ->where('status', 'published')
            ->where('published_at', '<=', now());

        // Filtrer par tag
        if ($request->has('tag')) {
            $tag = $request->tag;
            $query->whereHas('tags', function($q) use ($tag) {
                $q->where('name', $tag);
            });
        }

        // Recherche par titre ou contenu
        if ($request->has('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('content', 'like', $search)
                  ->orWhere('excerpt', 'like', $search);
            });
        }

        // Tri par date de publication
        $query->orderBy('published_at', 'desc');

        $blogPosts = $query->paginate($request->per_page ?? 6);

        // Récupérer les tags populaires
        $popularTags = BlogPostTag::withCount('blogPosts')
            ->orderBy('blog_posts_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $blogPosts,
            'popular_tags' => $popularTags
        ]);
    }

    /**
     * Afficher un article spécifique par son slug (endpoint public)
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function publicShow(string $slug): JsonResponse
    {
        $blogPost = BlogPost::with(['author:id,username,first_name,last_name', 'tags'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->first();

        if (!$blogPost) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé'
            ], 404);
        }

        // Récupérer les articles liés (mêmes tags)
        $relatedPosts = BlogPost::with(['author:id,username,first_name,last_name'])
            ->where('id', '!=', $blogPost->id)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->whereHas('tags', function($query) use ($blogPost) {
                $query->whereIn('name', $blogPost->tags->pluck('name'));
            })
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'post' => $blogPost,
                'related_posts' => $relatedPosts
            ]
        ]);
    }

    /**
     * Créer un nouvel article de blog
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'sometimes|string|max:500',
            'featured_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|string|in:draft,published,archived',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'published_at' => 'sometimes|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Générer un slug à partir du titre
        $slug = Str::slug($request->title);

        // Vérifier si le slug existe déjà
        $slugExists = BlogPost::where('slug', $slug)->exists();
        if ($slugExists) {
            $slug = $slug . '-' . time();
        }

        // Traiter l'image mise en avant
        $featuredImagePath = null;
        if ($request->hasFile('featured_image')) {
            $featuredImagePath = $request->file('featured_image')->store('blog', 'public');
        }

        // Créer l'article
        $blogPost = BlogPost::create([
            'title' => $request->title,
            'slug' => $slug,
            'content' => $request->content,
            'excerpt' => $request->excerpt ?? Str::limit(strip_tags($request->content), 200),
            'featured_image' => $featuredImagePath,
            'status' => $request->status,
            'author_id' => Auth::id(),
            'meta_title' => $request->meta_title ?? $request->title,
            'meta_description' => $request->meta_description ?? Str::limit(strip_tags($request->content), 160),
            'published_at' => $request->published_at ?? ($request->status === 'published' ? now() : null)
        ]);

        // Ajouter les tags
        if ($request->has('tags') && is_array($request->tags)) {
            foreach ($request->tags as $tagName) {
                // Nettoyer et normaliser le tag
                $tagName = trim(strtolower($tagName));
                if (empty($tagName)) {
                    continue;
                }

                // Créer ou récupérer le tag
                $tag = BlogPostTag::firstOrCreate(['name' => $tagName]);

                // Associer le tag à l'article
                $blogPost->tags()->attach($tag->id);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Article de blog créé avec succès',
            'data' => $blogPost->load(['author', 'tags'])
        ], 201);
    }

    /**
     * Afficher un article de blog spécifique
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $blogPost = BlogPost::with(['author', 'tags'])->find($id);

        if (!$blogPost) {
            return response()->json([
                'success' => false,
                'message' => 'Article de blog non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $blogPost
        ]);
    }

    /**
     * Mettre à jour un article de blog
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $blogPost = BlogPost::find($id);

        if (!$blogPost) {
            return response()->json([
                'success' => false,
                'message' => 'Article de blog non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'excerpt' => 'sometimes|string|max:500',
            'featured_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'sometimes|string|in:draft,published,archived',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'published_at' => 'sometimes|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mettre à jour le slug si le titre change
        if ($request->has('title') && $request->title !== $blogPost->title) {
            $slug = Str::slug($request->title);

            // Vérifier si le slug existe déjà
            $slugExists = BlogPost::where('slug', $slug)->where('id', '!=', $id)->exists();
            if ($slugExists) {
                $slug = $slug . '-' . time();
            }

            $request->merge(['slug' => $slug]);
        }

        // Traiter l'image mise en avant
        if ($request->hasFile('featured_image')) {
            // Supprimer l'ancienne image si elle existe
            if ($blogPost->featured_image) {
                Storage::disk('public')->delete($blogPost->featured_image);
            }

            $featuredImagePath = $request->file('featured_image')->store('blog', 'public');
            $request->merge(['featured_image' => $featuredImagePath]);
        }

        // Mettre à jour le champ published_at si le statut passe à "published"
        if ($request->has('status') && $request->status === 'published' && $blogPost->status !== 'published') {
            $request->merge(['published_at' => now()]);
        }

        // Mettre à jour l'article
        $blogPost->update($request->all());

        // Mettre à jour les tags si fournis
        if ($request->has('tags') && is_array($request->tags)) {
            // Détacher tous les tags existants
            $blogPost->tags()->detach();

            // Ajouter les nouveaux tags
            foreach ($request->tags as $tagName) {
                // Nettoyer et normaliser le tag
                $tagName = trim(strtolower($tagName));
                if (empty($tagName)) {
                    continue;
                }

                // Créer ou récupérer le tag
                $tag = BlogPostTag::firstOrCreate(['name' => $tagName]);

                // Associer le tag à l'article
                $blogPost->tags()->attach($tag->id);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Article de blog mis à jour avec succès',
            'data' => $blogPost->fresh(['author', 'tags'])
        ]);
    }

    /**
     * Supprimer un article de blog
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $blogPost = BlogPost::find($id);

        if (!$blogPost) {
            return response()->json([
                'success' => false,
                'message' => 'Article de blog non trouvé'
            ], 404);
        }

        // Supprimer l'image mise en avant si elle existe
        if ($blogPost->featured_image) {
            Storage::disk('public')->delete($blogPost->featured_image);
        }

        // Détacher tous les tags associés
        $blogPost->tags()->detach();

        // Supprimer l'article
        $blogPost->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article de blog supprimé avec succès'
        ]);
    }

    /**
     * Changer le statut d'un article de blog
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $blogPost = BlogPost::find($id);

        if (!$blogPost) {
            return response()->json([
                'success' => false,
                'message' => 'Article de blog non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:draft,published,archived'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $blogPost->status;
        $newStatus = $request->status;

        $blogPost->status = $newStatus;

        // Si l'article est publié pour la première fois, définir la date de publication
        if ($oldStatus !== 'published' && $newStatus === 'published' && !$blogPost->published_at) {
            $blogPost->published_at = now();
        }

        $blogPost->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut de l\'article mis à jour avec succès',
            'data' => $blogPost->fresh(['author', 'tags'])
        ]);
    }
}
