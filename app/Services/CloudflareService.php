<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected $zoneId;
    protected $apiToken;
    protected $apiEmail;
    protected $apiKey;

    public function __construct()
    {
        $this->zoneId = config('services.cloudflare.zone_id');
        $this->apiToken = config('services.cloudflare.api_token');
        $this->apiEmail = config('services.cloudflare.api_email');
        $this->apiKey = config('services.cloudflare.api_key');
    }

    /**
     * Purge cache for specific URLs
     */
    public function purgeCache(array $urls): bool
    {
        if (!$this->zoneId || !$this->apiToken) {
            Log::warning('Cloudflare credentials not configured');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/purge_cache", [
                'files' => $urls,
            ]);

            if ($response->successful()) {
                Log::info('Cloudflare cache purged', ['urls' => $urls]);
                return true;
            }

            Log::error('Cloudflare cache purge failed', ['response' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Cloudflare cache purge exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Purge entire cache
     */
    public function purgeAllCache(): bool
    {
        if (!$this->zoneId || !$this->apiToken) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/purge_cache", [
                'purge_everything' => true,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Cloudflare full cache purge exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get cache analytics
     */
    public function getCacheAnalytics(string $startDate, string $endDate): array
    {
        if (!$this->zoneId || !$this->apiToken) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/analytics/dashboard", [
                'since' => $startDate,
                'until' => $endDate,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Cloudflare analytics exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Create page rule for caching
     */
    public function createPageRule(string $url, array $settings): bool
    {
        if (!$this->zoneId || !$this->apiToken) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/pagerules", [
                'targets' => [
                    [
                        'target' => 'url',
                        'constraint' => [
                            'operator' => 'matches',
                            'value' => $url,
                        ],
                    ],
                ],
                'actions' => $settings,
                'status' => 'active',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Cloudflare page rule creation exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

