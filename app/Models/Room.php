<?php

namespace App\Models;

use App\Enums\RoomStatus;
use App\Enums\RoomType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'room_number',
        'type',
        'capacity',
        'price_per_night',
        'description',
        'status',
        'has_air_conditioning',
        'has_tv',
        'has_minibar',
        'has_safe',
        'has_wifi',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => RoomType::class,
        'status' => RoomStatus::class,
        'price_per_night' => 'decimal:2',
        'has_air_conditioning' => 'boolean',
        'has_tv' => 'boolean',
        'has_minibar' => 'boolean',
        'has_safe' => 'boolean',
        'has_wifi' => 'boolean',
    ];

    /**
     * Get all reservations for this room.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get all room images.
     */
    public function images()
    {
        return $this->hasMany(RoomImage::class);
    }

    /**
     * Check if room is available for the given dates.
     */
    public function isAvailable($checkInDate, $checkOutDate)
    {
        return !$this->reservations()
            ->where(function ($query) use ($checkInDate, $checkOutDate) {
                $query->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                    ->orWhereBetween('check_out_date', [$checkInDate, $checkOutDate])
                    ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->where('check_in_date', '<=', $checkInDate)
                            ->where('check_out_date', '>=', $checkOutDate);
                    });
            })
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'CHECKED_IN'])
            ->exists();
    }
}
