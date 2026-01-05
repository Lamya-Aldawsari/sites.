<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Cache boat listings with Redis
     */
    public function cacheBoats(array $filters, $boats, int $ttl = 3600): void
    {
        $key = 'boats:' . md5(serialize($filters));
        Cache::store('redis')->put($key, $boats, $ttl);
    }

    /**
     * Get cached boats
     */
    public function getCachedBoats(array $filters)
    {
        $key = 'boats:' . md5(serialize($filters));
        return Cache::store('redis')->get($key);
    }

    /**
     * Cache user data
     */
    public function cacheUser(int $userId, $userData, int $ttl = 1800): void
    {
        $key = "user:{$userId}";
        Cache::store('redis')->put($key, $userData, $ttl);
    }

    /**
     * Get cached user
     */
    public function getUser(int $userId)
    {
        $key = "user:{$userId}";
        return Cache::store('redis')->get($key);
    }

    /**
     * Invalidate boat cache
     */
    public function invalidateBoatCache(int $boatId): void
    {
        // Invalidate all boat-related cache
        $pattern = "boats:*";
        $keys = Redis::keys($pattern);
        if (!empty($keys)) {
            Redis::del($keys);
        }
        
        // Also invalidate specific boat cache
        Cache::store('redis')->forget("boat:{$boatId}");
    }

    /**
     * Cache booking availability
     */
    public function cacheAvailability(int $boatId, string $date, bool $available, int $ttl = 3600): void
    {
        $key = "boat:{$boatId}:availability:{$date}";
        Cache::store('redis')->put($key, $available, $ttl);
    }

    /**
     * Get cached availability
     */
    public function getCachedAvailability(int $boatId, string $date)
    {
        $key = "boat:{$boatId}:availability:{$date}";
        return Cache::store('redis')->get($key);
    }

    /**
     * Rate limiting using Redis
     */
    public function rateLimit(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $current = Redis::incr($key);
        
        if ($current === 1) {
            Redis::expire($key, $decaySeconds);
        }
        
        return $current <= $maxAttempts;
    }
}

