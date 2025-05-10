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
     * @OA\Get(
     *     path="/api/v1/blog",
     *     summary="Récupérer tous les articles publiés du blog (public)",
     *     description="Récupère la liste des articles publiés avec possibilité de filtrage",
     *     operationId="getPublicBlogPosts",
     *     tags={"Blog (Public)"},
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
     *         description="Recherche par titre, contenu ou extrait",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         description="Liste des articles publiés récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/BlogPost"
     *             ),
     *             @OA\Property(
     *                 property="popular_tags",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/BlogPostTag")
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/blog/{slug}",
     *     summary="Récupérer un article publié par son slug (public)",
     *     description="Récupère un article publié avec ses informations et des articles similaires",
     *     operationId="getPublicBlogPostBySlug",
     *     tags={"Blog (Public)"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Slug de l'article",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article récupéré avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="post",
     *                     ref="#/components/schemas/BlogPost"
     *                 ),
     *                 @OA\Property(
     *                     property="related_posts",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/BlogPost")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Article non trouvé ou non publié"
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/admin/blog",
     *     summary="Créer un nouvel article de blog",
     *     description="Création d'un nouvel article de blog par un administrateur",
     *     operationId="createBlogPost",
     *     tags={"Blog (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","content","status"},
     *             @OA\Property(property="title", type="string", example="Nouvel article"),
     *             @OA\Property(property="content", type="string", example="Contenu de l'article..."),
     *             @OA\Property(property="excerpt", type="string", example="Résumé de l'article", maxLength=500),
     *             @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="draft"),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string", example="nouveau")),
     *             @OA\Property(property="meta_title", type="string", example="Titre SEO", maxLength=255),
     *             @OA\Property(property="meta_description", type="string", example="Description SEO", maxLength=500),
     *             @OA\Property(property="published_at", type="string", format="date-time", example="2023-01-01 12:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Article créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Article de blog créé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/BlogPost"
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
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation échouée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'required|string|max:500',
            'author' => 'required|string|max:255',
            'date' => 'sometimes|date',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
            'published' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Traiter l'image si elle existe
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blog', 'public');
        }

        // Créer l'article
        $blogPost = BlogPost::create([
            'title' => $request->title,
            'content' => $request->content,
            'excerpt' => $request->excerpt,
            'author' => $request->author,
            'date' => $request->date ?? now(),
            'image' => $imagePath,
            'tags' => $request->tags ?? [],
            'published' => $request->boolean('published', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Article créé avec succès',
            'data' => [
                'id' => $blogPost->id,
                'title' => $blogPost->title,
                'content' => $blogPost->content,
                'excerpt' => $blogPost->excerpt,
                'author' => $blogPost->author,
                'date' => $blogPost->date,
                'image' => $blogPost->image ? asset('storage/' . $blogPost->image) : null,
                'tags' => $blogPost->tags,
                'published' => $blogPost->published,
            ]
        ], 201);
    }
     /**
     * @OA\Get(
     *     path="/api/v1/admin/blog/{id}",
     *     summary="Récupérer un article spécifique",
     *     description="Récupère un article de blog par son ID (accès admin)",
     *     operationId="getAdminBlogPostById",
     *     tags={"Blog (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de l'article",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article récupéré avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/BlogPost"
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
     *         description="Article non trouvé"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/admin/blog/{id}",
     *     summary="Mettre à jour un article de blog",
     *     description="Mise à jour d'un article de blog existant par un administrateur",
     *     operationId="updateBlogPost",
     *     tags={"Blog (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de l'article à mettre à jour",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Titre mis à jour"),
     *             @OA\Property(property="content", type="string", example="Contenu mis à jour..."),
     *             @OA\Property(property="excerpt", type="string", example="Résumé mis à jour", maxLength=500),
     *             @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published"),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string", example="misàjour")),
     *             @OA\Property(property="meta_title", type="string", example="Titre SEO mis à jour", maxLength=255),
     *             @OA\Property(property="meta_description", type="string", example="Description SEO mise à jour", maxLength=500),
     *             @OA\Property(property="published_at", type="string", format="date-time", example="2023-01-01 12:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Article de blog mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/BlogPost"
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
     *         description="Article non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation échouée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $blogPost = BlogPost::find($id);

        if (!$blogPost) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'excerpt' => 'sometimes|string|max:500',
            'author' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
            'published' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Traiter l'image si elle existe
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($blogPost->image) {
                Storage::disk('public')->delete($blogPost->image);
            }
            $imagePath = $request->file('image')->store('blog', 'public');
            $blogPost->image = $imagePath;
        }

        // Mettre à jour les autres champs
        $blogPost->fill($request->only([
            'title',
            'content',
            'excerpt',
            'author',
            'date',
            'tags',
            'published',
        ]));

        $blogPost->save();

        return response()->json([
            'success' => true,
            'message' => 'Article mis à jour avec succès',
            'data' => [
                'id' => $blogPost->id,
                'title' => $blogPost->title,
                'content' => $blogPost->content,
                'excerpt' => $blogPost->excerpt,
                'author' => $blogPost->author,
                'date' => $blogPost->date,
                'image' => $blogPost->image ? asset('storage/' . $blogPost->image) : null,
                'tags' => $blogPost->tags,
                'published' => $blogPost->published,
            ]
        ]);
    }
    /**
     * @OA\Delete(
     *     path="/api/v1/admin/blog/{id}",
     *     summary="Supprimer un article de blog",
     *     description="Suppression d'un article de blog par un administrateur",
     *     operationId="deleteBlogPost",
     *     tags={"Blog (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de l'article à supprimer",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article supprimé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Article de blog supprimé avec succès")
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
     *         description="Article non trouvé"
     *     )
     * )
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
     * @OA\Patch(
     *     path="/api/v1/admin/blog/{id}/status",
     *     summary="Changer le statut d'un article de blog",
     *     description="Modification du statut d'un article de blog (draft/published/archived)",
     *     operationId="changeBlogPostStatus",
     *     tags={"Blog (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de l'article",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statut de l'article mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/BlogPost"
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
     *         description="Article non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation échouée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
