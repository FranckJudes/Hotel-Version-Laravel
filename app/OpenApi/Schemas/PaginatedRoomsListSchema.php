<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="PaginatedRoomsList",
 *     title="Liste paginée de chambres",
 *     description="Liste paginée de chambres avec métadonnées de pagination"
 * )
 */
class PaginatedRoomsListSchema
{
    /**
     * @OA\Property(property="current_page", type="integer", example=1)
     */
    public $current_page;

    /**
     * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Room"))
     */
    public $data;

    /**
     * @OA\Property(property="first_page_url", type="string", example="http://localhost:8000/api/v1/rooms?page=1")
     */
    public $first_page_url;

    /**
     * @OA\Property(property="from", type="integer", example=1)
     */
    public $from;

    /**
     * @OA\Property(property="last_page", type="integer", example=5)
     */
    public $last_page;

    /**
     * @OA\Property(property="last_page_url", type="string", example="http://localhost:8000/api/v1/rooms?page=5")
     */
    public $last_page_url;

    /**
     * @OA\Property(property="links", type="array",
     *     @OA\Items(
     *         type="object",
     *         @OA\Property(property="url", type="string", nullable=true, example="http://localhost:8000/api/v1/rooms?page=1"),
     *         @OA\Property(property="label", type="string", example="&laquo; Previous"),
     *         @OA\Property(property="active", type="boolean", example=false)
     *     )
     * )
     */
    public $links;

    /**
     * @OA\Property(property="next_page_url", type="string", nullable=true, example="http://localhost:8000/api/v1/rooms?page=2")
     */
    public $next_page_url;

    /**
     * @OA\Property(property="path", type="string", example="http://localhost:8000/api/v1/rooms")
     */
    public $path;

    /**
     * @OA\Property(property="per_page", type="integer", example=10)
     */
    public $per_page;

    /**
     * @OA\Property(property="prev_page_url", type="string", nullable=true, example=null)
     */
    public $prev_page_url;

    /**
     * @OA\Property(property="to", type="integer", example=10)
     */
    public $to;

    /**
     * @OA\Property(property="total", type="integer", example=50)
     */
    public $total;
}
