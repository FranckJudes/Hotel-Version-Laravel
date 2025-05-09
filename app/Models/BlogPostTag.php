<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPostTag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'blog_post_id',
        'tag',
    ];

    /**
     * Get the blog post that owns the tag.
     */
    public function blogPost()
    {
        return $this->belongsTo(BlogPost::class);
    }
}
