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
        'id',
        'room_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'check_in',
        'check_out',
        'status',
        'total_price',
        'payment_method',
        'payment_status',
        'created_at',
        'guests',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'created_at' => 'datetime',
        'total_price' => 'integer',
        'guests' => 'integer',
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
        return $this->check_in->diffInDays($this->check_out);
    }

    /**
     * Generate a unique reservation number.
     */
    public static function generateReservationNumber()
    {
        return 'RES-' . strtoupper(uniqid());
    }
}
