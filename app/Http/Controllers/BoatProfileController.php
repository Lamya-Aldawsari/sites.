<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use Illuminate\Http\Request;

class BoatProfileController extends Controller
{
    public function show(Boat $boat)
    {
        // Load all transparency data
        $boat->load([
            'captain' => function ($query) {
                $query->with(['verificationDocuments', 'reviews']);
            },
            'reviews.user',
            'verificationDocuments',
        ]);

        // Calculate captain experience
        $captain = $boat->captain;
        $captainData = [
            'id' => $captain->id,
            'name' => $captain->name,
            'rating' => $captain->captain_rating ?? 0,
            'total_reviews' => $captain->captain_total_reviews ?? 0,
            'years_experience' => $captain->years_experience ?? 0,
            'license_verified' => $captain->license_verified ?? false,
            'license_number' => $captain->license_number,
            'license_expiry_date' => $captain->license_expiry_date,
            'certifications' => $captain->certifications ?? [],
            'verified_photos' => $boat->verified_photos ?? [],
            'safety_certificate_verified' => $boat->safety_certificate_verified ?? false,
            'safety_certificate_expiry' => $boat->safety_certificate_expiry,
            'last_safety_inspection' => $boat->last_safety_inspection,
            'safety_rating' => $boat->safety_rating ?? 0,
        ];

        return response()->json([
            'boat' => $boat,
            'captain' => $captainData,
            'reviews' => $boat->reviews,
        ]);
    }
}

