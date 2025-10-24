<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    // Auto-generate slug when creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    // Relationships will be added later
    // public function articles()
    // {
    //     return $this->belongsToMany(Article::class, 'article_tags');
    // }

    // public function videos()
    // {
    //     return $this->belongsToMany(Video::class, 'video_tags');
    // }

    // Helper method to get usage count (will implement later)
    public function getUsageCountAttribute(): int
    {
        // Will implement after Articles and Videos are created
        return 0;
    }
}