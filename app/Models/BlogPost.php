<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'author_id',
        'featured_image',
        'published',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Get the user that wrote the blog post.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the tags for the blog post.
     */
    public function tags()
    {
        return $this->hasMany(BlogPostTag::class);
    }

    /**
     * Get the tags as a collection of strings.
     */
    public function getTagsArrayAttribute()
    {
        return $this->tags()->pluck('tag')->toArray();
    }

    /**
     * Set tags for the blog post.
     */
    public function setTags(array $tags)
    {
        // Delete existing tags
        $this->tags()->delete();

        // Create new tags
        foreach ($tags as $tag) {
            $this->tags()->create(['tag' => $tag]);
        }
    }

    /**
     * Scope a query to only include published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }
}
