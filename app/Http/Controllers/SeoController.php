<?php

namespace App\Http\Controllers;

use App\Models\SeoSettings;
use App\Services\SeoService;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    protected $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    public function getMetaTags(Request $request)
    {
        $pageType = $request->get('page_type', 'home');
        $identifier = $request->get('identifier');
        $model = null;

        // Load model if identifier provided
        if ($identifier) {
            switch ($pageType) {
                case 'boat_detail':
                    $model = \App\Models\Boat::where('id', $identifier)
                        ->orWhere('slug', $identifier)
                        ->first();
                    break;
                case 'equipment_detail':
                    $model = \App\Models\Equipment::where('id', $identifier)
                        ->orWhere('slug', $identifier)
                        ->first();
                    break;
            }
        }

        $metaTags = $this->seoService->getMetaTags($pageType, $identifier, $model);

        return response()->json($metaTags);
    }

    public function updateSeoSettings(Request $request)
    {
        // Only admins can update SEO settings
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'page_type' => 'required|string',
            'page_identifier' => 'nullable|string',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|array',
            'og_title' => 'nullable|string|max:60',
            'og_description' => 'nullable|string|max:160',
            'og_image' => 'nullable|url',
            'canonical_url' => 'nullable|url',
            'robots' => 'nullable|string',
            'structured_data' => 'nullable|array',
        ]);

        $seoSettings = SeoSettings::updateOrCreate(
            [
                'page_type' => $validated['page_type'],
                'page_identifier' => $validated['page_identifier'] ?? null,
            ],
            $validated
        );

        return response()->json($seoSettings);
    }
}

