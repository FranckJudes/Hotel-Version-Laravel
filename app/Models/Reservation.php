<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reservation_number',
        'user_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'total_price',
        'status',
        'special_requests',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'total_price' => 'decimal:2',
        'status' => ReservationStatus::class,
    ];

    /**
     * Get the user that owns the reservation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the room that is reserved.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the payments for this reservation.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the testimonial for this reservation.
     */
    public function testimonial()
    {
        return $this->hasOne(Testimonial::class);
    }

    /**
     * Calculate duration of stay in days.
     */
    public function getDurationAttribute()
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    /**
     * Generate a unique reservation number.
     */
    public static function generateReservationNumber()
    {
        return 'RES-' . strtoupper(uniqid());
    }
}
