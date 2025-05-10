<?php

namespace App\OpenApi\Schemas;
/**
 * @OA\Schema(
 *     schema="BlogPostTag",
 *     title="BlogPostTag",
 *     description="Modèle de tag associé aux articles de blog"
 * )
 */
class BlogPostTag
{
    /**
     * @OA\Property(
     *     property="id",
     *     type="integer",
     *     description="ID du tag"
     * )
     *
     * @var int
     */
    public $id;

    /**
     * @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Nom du tag"
     * )
     *
     * @var string
     */
    public $name;

    /**
     * @OA\Property(
     *     property="created_at",
     *     type="string",
     *     format="date-time",
     *     description="Date de création du tag"
     * )
     *
     * @var \DateTime
     */
    public $created_at;

    /**
     * @OA\Property(
     *     property="updated_at",
     *     type="string",
     *     format="date-time",
     *     description="Date de mise à jour du tag"
     * )
     *
     * @var \DateTime
     */
    public $updated_at;
}