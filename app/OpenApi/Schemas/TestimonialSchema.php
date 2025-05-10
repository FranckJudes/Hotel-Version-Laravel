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
     * @OA\Property(property="author", type="string", example="John Doe")
     */
    public $author;

    /**
     * @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5)
     */
    public $rating;

    /**
     * @OA\Property(property="content", type="string", example="Nous avons passé un séjour merveilleux dans cet hôtel. Le personnel est très attentionné...")
     */
    public $content;

    /**
     * @OA\Property(property="date", type="string", format="date-time", example="2023-11-15T10:00:00Z")
     */
    public $date;

    /**
     * @OA\Property(property="approved", type="boolean", example=true)
     */
    public $approved;

    /**
     * @OA\Property(property="created_at", type="string", format="date-time")
     */
    public $created_at;

    /**
     * @OA\Property(property="updated_at", type="string", format="date-time")
     */
    public $updated_at;

    /**
     * @OA\Property(property="reservation", ref="#/components/schemas/Reservation")
     */
    public $reservation;
}
