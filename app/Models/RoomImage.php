<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'room_id',
        'image_url',
    ];

    /**
     * Get the room that owns the image.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
