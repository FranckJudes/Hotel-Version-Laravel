<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardStats extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'total_bookings',
        'upcoming_bookings',
        'revenue',
        'occupancy_rate',
        'total_customers',
        'popular_rooms',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_bookings' => 'integer',
        'upcoming_bookings' => 'integer',
        'revenue' => 'array',
        'occupancy_rate' => 'float',
        'total_customers' => 'integer',
        'popular_rooms' => 'array',
    ];
}
