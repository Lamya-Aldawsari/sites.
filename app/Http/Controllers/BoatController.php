<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Http\Requests\StoreBoatRequest;
use App\Http\Requests\UpdateBoatRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BoatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'search']);
    }

    public function index(Request $request)
    {
        // Only show verified boats with verified captains
        $query = Boat::verified()->with(['captain', 'reviews']);

        // Filter by location
        if ($request->has('latitude') && $request->has('longitude')) {
            $query->nearby($request->latitude, $request->longitude, $request->radius ?? 50);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by capacity
        if ($request->has('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }

        // Filter by price range
        if ($request->has('max_hourly_rate')) {
            $query->where('hourly_rate', '<=', $request->max_hourly_rate);
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $boats = $query->paginate($request->per_page ?? 15);

        return response()->json($boats);
    }

    public function store(StoreBoatRequest $request)
    {
        $user = $request->user();

        if (!$user->isCaptain()) {
            return response()->json(['message' => 'Only captains can create boats'], 403);
        }

        $data = $request->validated();
        $data['captain_id'] = $user->id;

        // Handle image uploads
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('boats', 'public');
                $images[] = Storage::url($path);
            }
            $data['images'] = $images;
        }

        $boat = Boat::create($data);

        return response()->json($boat->load('captain'), 201);
    }

    public function show(Boat $boat)
    {
        return response()->json($boat->load(['captain', 'reviews.user', 'availability']));
    }

    public function update(UpdateBoatRequest $request, Boat $boat)
    {
        $user = $request->user();

        if ($boat->captain_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        // Handle image uploads
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('boats', 'public');
                $images[] = Storage::url($path);
            }
            $data['images'] = $images;
        }

        $boat->update($data);

        return response()->json($boat->load('captain'));
    }

    public function destroy(Boat $boat)
    {
        $user = request()->user();

        if ($boat->captain_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $boat->delete();

        return response()->json(['message' => 'Boat deleted successfully']);
    }

    public function search(Request $request)
    {
        // Only show verified boats with verified captains
        $query = Boat::verified();

        if ($request->has('q')) {
            $query->search($request->q);
        }

        if ($request->has('latitude') && $request->has('longitude')) {
            $query->nearby($request->latitude, $request->longitude, $request->radius ?? 50);
        }

        $boats = $query->with('captain')->paginate(15);

        return response()->json($boats);
    }
}

