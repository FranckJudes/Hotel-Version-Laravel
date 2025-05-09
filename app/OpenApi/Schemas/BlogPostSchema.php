<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="BlogPost",
 *     title="BlogPost",
 *     description="Modèle d'article de blog"
 * )
 */
class BlogPostSchema
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     */
    public $id;

    /**
     * @OA\Property(property="title", type="string", example="Les meilleures activités à faire dans notre région")
     */
    public $title;

    /**
     * @OA\Property(property="slug", type="string", example="les-meilleures-activites-a-faire-dans-notre-region")
     */
    public $slug;

    /**
     * @OA\Property(property="content", type="string", example="<p>Contenu détaillé de l'article de blog...</p>")
     */
    public $content;

    /**
     * @OA\Property(property="excerpt", type="string", example="Découvrez notre sélection des meilleures activités à faire lors de votre séjour dans notre région.")
     */
    public $excerpt;

    /**
     * @OA\Property(property="featured_image", type="string", nullable=true, example="blog/image1.jpg")
     */
    public $featured_image;

    /**
     * @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published")
     */
    public $status;

    /**
     * @OA\Property(property="author_id", type="integer", example=1)
     */
    public $author_id;

    /**
     * @OA\Property(property="meta_title", type="string", nullable=true, example="Activités touristiques - Hôtel Paradise")
     */
    public $meta_title;

    /**
     * @OA\Property(property="meta_description", type="string", nullable=true, example="Découvrez les meilleures activités touristiques pour profiter de votre séjour à l'Hôtel Paradise.")
     */
    public $meta_description;

    /**
     * @OA\Property(property="published_at", type="string", format="date-time", nullable=true)
     */
    public $published_at;

    /**
     * @OA\Property(property="created_at", type="string", format="date-time")
     */
    public $created_at;

    /**
     * @OA\Property(property="updated_at", type="string", format="date-time")
     */
    public $updated_at;

    /**
     * @OA\Property(
     *     property="author",
     *     ref="#/components/schemas/User"
     * )
     */
    public $author;

    /**
     * @OA\Property(
     *     property="tags",
     *     type="array",
     *     @OA\Items(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="activités")
     *     )
     * )
     */
    public $tags;
}
