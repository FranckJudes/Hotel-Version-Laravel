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
     * @OA\Property(property="content", type="string", example="<p>Contenu détaillé de l'article de blog...</p>")
     */
    public $content;

    /**
     * @OA\Property(property="excerpt", type="string", example="Découvrez notre sélection des meilleures activités à faire lors de votre séjour dans notre région.")
     */
    public $excerpt;

    /**
     * @OA\Property(property="author", type="string", example="John Doe")
     */
    public $author;

    /**
     * @OA\Property(property="date", type="string", format="date-time", example="2023-11-15T10:00:00Z")
     */
    public $date;

    /**
     * @OA\Property(property="image", type="string", nullable=true, example="blog/image1.jpg")
     */
    public $image;

    /**
     * @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"hotel", "voyage", "activités"})
     */
    public $tags;

    /**
     * @OA\Property(property="published", type="boolean", example=true)
     */
    public $published;

    /**
     * @OA\Property(property="created_at", type="string", format="date-time")
     */
    public $created_at;

    /**
     * @OA\Property(property="updated_at", type="string", format="date-time")
     */
    public $updated_at;
}
