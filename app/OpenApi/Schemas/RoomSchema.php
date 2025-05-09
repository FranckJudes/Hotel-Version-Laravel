<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Room",
 *     title="Room",
 *     description="Modèle de chambre d'hôtel"
 * )
 */
class RoomSchema
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     */
    public $id;

    /**
     * @OA\Property(property="number", type="string", example="101")
     */
    public $number;

    /**
     * @OA\Property(property="type", type="string", example="standard")
     */
    public $type;

    /**
     * @OA\Property(property="price_per_night", type="number", format="float", example=99.99)
     */
    public $price_per_night;

    /**
     * @OA\Property(property="capacity", type="integer", example=2)
     */
    public $capacity;

    /**
     * @OA\Property(property="description", type="string", example="Chambre standard avec vue sur le jardin")
     */
    public $description;

    /**
     * @OA\Property(property="features", type="array", @OA\Items(type="string"), example={"wifi", "climatisation"})
     */
    public $features;

    /**
     * @OA\Property(property="status", type="string", enum={"available", "occupied", "maintenance"}, example="available")
     */
    public $status;

    /**
     * @OA\Property(
     *     property="images",
     *     type="array",
     *     @OA\Items(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="room_id", type="integer", example=1),
     *         @OA\Property(property="image_path", type="string", example="rooms/room1.jpg"),
     *         @OA\Property(property="is_primary", type="boolean", example=true)
     *     )
     * )
     */
    public $images;
}
