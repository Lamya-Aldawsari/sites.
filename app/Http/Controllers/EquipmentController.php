<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EquipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $query = Equipment::available()->with('vendor');

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by location
        if ($request->has('latitude') && $request->has('longitude')) {
            $query->selectRaw(
                '*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                [$request->latitude, $request->longitude, $request->latitude]
            )
            ->having('distance', '<', $request->radius ?? 50)
            ->orderBy('distance');
        }

        $equipment = $query->paginate($request->per_page ?? 15);

        return response()->json($equipment);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isVendor()) {
            return response()->json(['message' => 'Only vendors can create equipment'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:safety,navigation,fishing,water_sports,maintenance,other',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'nullable|numeric|min:0',
            'quantity_available' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $validated['vendor_id'] = $user->id;

        // Handle image uploads
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('equipment', 'public');
                $images[] = Storage::url($path);
            }
            $validated['images'] = $images;
        }

        $equipment = Equipment::create($validated);

        return response()->json($equipment->load('vendor'), 201);
    }

    public function show(Equipment $equipment)
    {
        return response()->json($equipment->load(['vendor', 'reviews.user']));
    }

    public function update(Request $request, Equipment $equipment)
    {
        $user = $request->user();

        if ($equipment->vendor_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category' => 'sometimes|in:safety,navigation,fishing,water_sports,maintenance,other',
            'daily_rate' => 'sometimes|numeric|min:0',
            'weekly_rate' => 'nullable|numeric|min:0',
            'quantity_available' => 'sometimes|integer|min:0',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'is_available' => 'sometimes|boolean',
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('equipment', 'public');
                $images[] = Storage::url($path);
            }
            $validated['images'] = $images;
        }

        $equipment->update($validated);

        return response()->json($equipment->load('vendor'));
    }

    public function destroy(Equipment $equipment)
    {
        $user = request()->user();

        if ($equipment->vendor_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $equipment->delete();

        return response()->json(['message' => 'Equipment deleted successfully']);
    }
}

