<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Testimonial",
 *     title="Testimonial",
 *     description="Modèle de témoignage client"
 * )
 */
class TestimonialSchema
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     */
    public $id;

    /**
     * @OA\Property(property="user_id", type="integer", example=1)
     */
    public $user_id;

    /**
     * @OA\Property(property="title", type="string", example="Séjour exceptionnel")
     */
    public $title;

    /**
     * @OA\Property(property="content", type="string", example="Nous avons passé un séjour merveilleux dans cet hôtel. Le personnel est très attentionné...")
     */
    public $content;

    /**
     * @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5)
     */
    public $rating;

    /**
     * @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved")
     */
    public $status;

    /**
     * @OA\Property(property="highlighted", type="boolean", example=false)
     */
    public $highlighted;

    /**
     * @OA\Property(property="admin_notes", type="string", nullable=true, example="Témoignage très positif à mettre en avant sur la page d'accueil")
     */
    public $admin_notes;

    /**
     * @OA\Property(property="created_at", type="string", format="date-time")
     */
    public $created_at;

    /**
     * @OA\Property(property="updated_at", type="string", format="date-time")
     */
    public $updated_at;

    /**
     * @OA\Property(property="user", ref="#/components/schemas/User")
     */
    public $user;
}
