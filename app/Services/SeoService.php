<?php

namespace App\Services;

use App\Models\SeoSettings;
use App\Models\Boat;
use App\Models\Equipment;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Generate SEO meta tags for a page
     */
    public function getMetaTags(string $pageType, ?string $identifier = null, $model = null): array
    {
        // Get SEO settings from database
        $seoSettings = SeoSettings::forPage($pageType, $identifier)->first();

        // Default meta tags
        $defaults = $this->getDefaultMetaTags($pageType, $model);

        return [
            'title' => $seoSettings->meta_title ?? $defaults['title'] ?? config('app.name'),
            'description' => $seoSettings->meta_description ?? $defaults['description'] ?? '',
            'keywords' => $seoSettings->meta_keywords ?? $defaults['keywords'] ?? [],
            'og_title' => $seoSettings->og_title ?? $seoSettings->meta_title ?? $defaults['title'] ?? config('app.name'),
            'og_description' => $seoSettings->og_description ?? $seoSettings->meta_description ?? $defaults['description'] ?? '',
            'og_image' => $seoSettings->og_image ?? $defaults['og_image'] ?? null,
            'canonical_url' => $seoSettings->canonical_url ?? $defaults['canonical_url'] ?? null,
            'robots' => $seoSettings->robots ?? 'index, follow',
            'structured_data' => $seoSettings->structured_data ?? $this->generateStructuredData($pageType, $model),
        ];
    }

    /**
     * Get default meta tags based on page type
     */
    protected function getDefaultMetaTags(string $pageType, $model = null): array
    {
        $baseUrl = config('app.url');

        switch ($pageType) {
            case 'home':
                return [
                    'title' => 'iBoat - Marine Transport & Rental Platform',
                    'description' => 'Book boats, rent marine equipment, and experience the ocean. Your Uber/Airbnb for boats.',
                    'keywords' => ['boat rental', 'marine equipment', 'yacht charter', 'boat booking', 'marine transport'],
                    'canonical_url' => $baseUrl,
                ];

            case 'boats':
                return [
                    'title' => 'Browse Boats - iBoat',
                    'description' => 'Find and book boats for your next adventure. Hourly, daily, and weekly rentals available.',
                    'keywords' => ['boat rental', 'yacht charter', 'boat booking', 'marine transport'],
                    'canonical_url' => $baseUrl . '/boats',
                ];

            case 'boat_detail':
                if ($model instanceof Boat) {
                    return [
                        'title' => $model->meta_title ?? "{$model->name} - Boat Rental | iBoat",
                        'description' => $model->meta_description ?? Str::limit($model->description, 160),
                        'keywords' => $model->meta_keywords ?? $this->generateKeywordsForBoat($model),
                        'og_image' => $model->og_image ?? ($model->images[0] ?? null),
                        'canonical_url' => $baseUrl . '/boats/' . ($model->slug ?? $model->id),
                    ];
                }
                break;

            case 'equipment':
                return [
                    'title' => 'Marine Equipment - iBoat',
                    'description' => 'Shop for marine equipment including fishing gear, navigation tools, and safety equipment.',
                    'keywords' => ['marine equipment', 'fishing gear', 'boat accessories', 'marine supplies'],
                    'canonical_url' => $baseUrl . '/equipment',
                ];

            case 'equipment_detail':
                if ($model instanceof Equipment) {
                    return [
                        'title' => $model->meta_title ?? "{$model->name} - Marine Equipment | iBoat",
                        'description' => $model->meta_description ?? Str::limit($model->description, 160),
                        'keywords' => $model->meta_keywords ?? $this->generateKeywordsForEquipment($model),
                        'og_image' => $model->og_image ?? ($model->images[0] ?? null),
                        'canonical_url' => $baseUrl . '/equipment/' . ($model->slug ?? $model->id),
                    ];
                }
                break;
        }

        return [];
    }

    /**
     * Generate keywords for a boat
     */
    protected function generateKeywordsForBoat(Boat $boat): array
    {
        $keywords = [
            'boat rental',
            $boat->type,
            strtolower($boat->location ?? ''),
        ];

        if ($boat->make) {
            $keywords[] = strtolower($boat->make);
        }

        if ($boat->amenities) {
            $keywords = array_merge($keywords, array_map('strtolower', $boat->amenities));
        }

        return array_unique($keywords);
    }

    /**
     * Generate keywords for equipment
     */
    protected function generateKeywordsForEquipment(Equipment $equipment): array
    {
        return [
            'marine equipment',
            $equipment->category,
            strtolower($equipment->name),
        ];
    }

    /**
     * Generate structured data (JSON-LD)
     */
    protected function generateStructuredData(string $pageType, $model = null): array
    {
        $baseUrl = config('app.url');

        switch ($pageType) {
            case 'home':
                return [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => config('app.name'),
                    'url' => $baseUrl,
                ];

            case 'boat_detail':
                if ($model instanceof Boat) {
                    return [
                        '@context' => 'https://schema.org',
                        '@type' => 'Product',
                        'name' => $model->name,
                        'description' => $model->description,
                        'image' => $model->images ?? [],
                        'offers' => [
                            '@type' => 'Offer',
                            'price' => $model->hourly_rate,
                            'priceCurrency' => 'USD',
                            'availability' => $model->is_available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        ],
                    ];
                }
                break;

            case 'equipment_detail':
                if ($model instanceof Equipment) {
                    return [
                        '@context' => 'https://schema.org',
                        '@type' => 'Product',
                        'name' => $model->name,
                        'description' => $model->description,
                        'category' => $model->category,
                        'offers' => [
                            '@type' => 'Offer',
                            'price' => $model->daily_rate,
                            'priceCurrency' => 'USD',
                        ],
                    ];
                }
                break;
        }

        return [];
    }

    /**
     * Generate slug for a model
     */
    public function generateSlug(string $name, string $modelClass, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while ($modelClass::where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

