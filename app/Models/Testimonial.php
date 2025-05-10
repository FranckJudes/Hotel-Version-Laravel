<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'author',
        'rating',
        'content',
        'date',
        'approved',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'approved' => 'boolean',
        'date' => 'datetime',
    ];

    /**
     * Get the reservation associated with the testimonial.
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Scope a query to only include approved testimonials.
     */
    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }
}
