<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="RoomImage",
 *     title="RoomImage",
 *     description="Modèle d'image associée à une chambre"
 * )
 */
class RoomImageSchema
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     */
    public $id;

    /**
     * @OA\Property(property="room_id", type="integer", example=1)
     */
    public $room_id;

    /**
     * @OA\Property(property="image_path", type="string", example="rooms/room1.jpg")
     */
    public $image_path;

    /**
     * @OA\Property(property="is_primary", type="boolean", example=true)
     */
    public $is_primary;
}