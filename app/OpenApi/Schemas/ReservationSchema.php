<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Reservation",
 *     title="Reservation",
 *     description="Modèle de réservation de chambre d'hôtel"
 * )
 */
class ReservationSchema
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
     * @OA\Property(property="room_id", type="integer", example=1)
     */
    public $room_id;

    /**
     * @OA\Property(property="check_in_date", type="string", format="date", example="2023-12-24")
     */
    public $check_in_date;

    /**
     * @OA\Property(property="check_out_date", type="string", format="date", example="2023-12-28")
     */
    public $check_out_date;

    /**
     * @OA\Property(property="adults", type="integer", example=2)
     */
    public $adults;

    /**
     * @OA\Property(property="children", type="integer", example=1)
     */
    public $children;

    /**
     * @OA\Property(property="total_amount", type="number", format="float", example=399.96)
     */
    public $total_amount;

    /**
     * @OA\Property(property="status", type="string", enum={"pending", "confirmed", "checked_in", "checked_out", "cancelled"}, example="confirmed")
     */
    public $status;

    /**
     * @OA\Property(property="special_requests", type="string", nullable=true, example="Chambre aux étages supérieurs avec vue sur la ville")
     */
    public $special_requests;

    /**
     * @OA\Property(property="notes", type="string", nullable=true)
     */
    public $notes;

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

    /**
     * @OA\Property(property="room", ref="#/components/schemas/Room")
     */
    public $room;
}
