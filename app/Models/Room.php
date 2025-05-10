<?php

namespace App\Models;

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
        'id',
        'name',
        'description',
        'price',
        'capacity',
        'type',
        'amenities',
        'available'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => RoomType::class,
        'price' => 'integer',
        'capacity' => 'integer',
        'amenities' => 'array',
        'available' => 'boolean',
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
        if (!$this->available) {
            return false;
        }

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
