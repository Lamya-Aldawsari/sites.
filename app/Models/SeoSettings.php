<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSettings extends Model
{
    use HasFactory;

    protected $table = 'seo_settings';

    protected $fillable = [
        'page_type',
        'page_identifier',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'robots',
        'structured_data',
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'structured_data' => 'array',
    ];

    // Scopes
    public function scopeForPage($query, string $pageType, ?string $identifier = null)
    {
        return $query->where('page_type', $pageType)
                     ->where('page_identifier', $identifier);
    }
}

