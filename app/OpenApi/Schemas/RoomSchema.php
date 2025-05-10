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
     * @OA\Property(property="name", type="string", example="101")
     */
    public $name;

    /**
     * @OA\Property(property="description", type="string", example="Chambre standard avec vue sur le jardin")
     */
    public $description;

    /**
     * @OA\Property(property="price", type="number", format="float", example=99.99)
     */
    public $price;

    /**
     * @OA\Property(property="capacity", type="integer", example=2)
     */
    public $capacity;

    /**
     * @OA\Property(property="type", type="string", enum={"standard", "deluxe", "suite", "family", "presidential"}, example="standard")
     */
    public $type;

    /**
     * @OA\Property(property="amenities", type="array", @OA\Items(type="string"), example={"wifi", "climatisation"})
     */
    public $amenities;

    /**
     * @OA\Property(property="available", type="boolean", example=true)
     */
    public $available;

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

    /**
     * @OA\Property(property="created_at", type="string", format="date-time")
     */
    public $created_at;

    /**
     * @OA\Property(property="updated_at", type="string", format="date-time")
     */
    public $updated_at;
}
