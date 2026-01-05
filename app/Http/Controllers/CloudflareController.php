<?php

namespace App\Http\Controllers;

use App\Services\CloudflareService;
use Illuminate\Http\Request;

class CloudflareController extends Controller
{
    protected $cloudflareService;

    public function __construct(CloudflareService $cloudflareService)
    {
        $this->middleware('auth:sanctum');
        $this->cloudflareService = $cloudflareService;
    }

    public function purgeCache(Request $request)
    {
        // Only admins can purge cache
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'urls' => 'required|array',
            'urls.*' => 'url',
        ]);

        $success = $this->cloudflareService->purgeCache($validated['urls']);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Cache purged successfully' : 'Failed to purge cache',
        ]);
    }

    public function purgeAllCache(Request $request)
    {
        // Only admins can purge all cache
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $success = $this->cloudflareService->purgeAllCache();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'All cache purged successfully' : 'Failed to purge cache',
        ]);
    }
}

