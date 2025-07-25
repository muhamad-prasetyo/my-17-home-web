<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'banner_path',
    ];

    // Append banner_url attribute for full banner image URL
    protected $appends = ['banner_url'];

    /**
     * Get the full URL for the banner image
     *
     * @return string|null
     */
    public function getBannerUrlAttribute()
    {
        return $this->banner_path
            ? asset('storage/' . $this->banner_path)
            : null;
    }
}
