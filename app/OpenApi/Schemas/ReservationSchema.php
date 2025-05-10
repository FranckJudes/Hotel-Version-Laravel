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
     * @OA\Property(property="room_id", type="integer", example=1)
     */
    public $room_id;

    /**
     * @OA\Property(property="customer_name", type="string", example="John Doe")
     */
    public $customer_name;

    /**
     * @OA\Property(property="customer_email", type="string", example="john.doe@example.com")
     */
    public $customer_email;

    /**
     * @OA\Property(property="customer_phone", type="string", example="+2250101010101")
     */
    public $customer_phone;

    /**
     * @OA\Property(property="check_in", type="string", format="date-time", example="2023-12-24T14:00:00Z")
     */
    public $check_in;

    /**
     * @OA\Property(property="check_out", type="string", format="date-time", example="2023-12-28T12:00:00Z")
     */
    public $check_out;

    /**
     * @OA\Property(property="status", type="string", enum={"pending", "confirmed", "checked_in", "checked_out", "cancelled", "completed"}, example="confirmed")
     */
    public $status;

    /**
     * @OA\Property(property="total_price", type="number", format="float", example=399.96)
     */
    public $total_price;

    /**
     * @OA\Property(property="payment_method", type="string", enum={"credit_card", "cash", "bank_transfer", "orange_money", "mtn_mobile_money"}, example="credit_card")
     */
    public $payment_method;

    /**
     * @OA\Property(property="payment_status", type="string", enum={"pending", "paid", "failed", "refunded"}, example="paid")
     */
    public $payment_status;

    /**
     * @OA\Property(property="guests", type="integer", example=2)
     */
    public $guests;

    /**
     * @OA\Property(property="created_at", type="string", format="date-time")
     */
    public $created_at;

    /**
     * @OA\Property(property="updated_at", type="string", format="date-time")
     */
    public $updated_at;

    /**
     * @OA\Property(property="room", ref="#/components/schemas/Room")
     */
    public $room;
}
