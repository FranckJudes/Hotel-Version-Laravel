<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Statistic",
 *     title="Statistic",
 *     description="Modèle de statistiques de l'hôtel"
 * )
 */
class StatisticSchema
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     */
    public $id;

    /**
     * @OA\Property(property="date", type="string", format="date", example="2023-12-15")
     */
    public $date;

    /**
     * @OA\Property(property="occupancy_rate", type="number", format="float", example=85.5)
     */
    public $occupancy_rate;

    /**
     * @OA\Property(property="daily_revenue", type="number", format="float", example=2500.75)
     */
    public $daily_revenue;

    /**
     * @OA\Property(property="monthly_revenue", type="number", format="float", example=75000.25)
     */
    public $monthly_revenue;

    /**
     * @OA\Property(property="rooms_available", type="integer", example=12)
     */
    public $rooms_available;

    /**
     * @OA\Property(property="rooms_booked", type="integer", example=48)
     */
    public $rooms_booked;

    /**
     * @OA\Property(property="total_rooms", type="integer", example=60)
     */
    public $total_rooms;

    /**
     * @OA\Property(property="reservations_created", type="integer", example=15)
     */
    public $reservations_created;

    /**
     * @OA\Property(property="users_registered", type="integer", example=8)
     */
    public $users_registered;

    /**
     * @OA\Property(property="created_at", type="string", format="date-time")
     */
    public $created_at;

    /**
     * @OA\Property(property="updated_at", type="string", format="date-time")
     */
    public $updated_at;
}
