<?php

namespace App\Http\Controllers;

use App\Models\VerificationDocument;
use App\Models\User;
use App\Models\Boat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function uploadDocument(Request $request)
    {
        $user = $request->user();

        // Only captains and vendors can upload documents
        if (!$user->isCaptain() && !$user->isVendor()) {
            return response()->json(['message' => 'Only captains and vendors can upload verification documents'], 403);
        }

        $validated = $request->validate([
            'document_type' => 'required|in:marine_license,boat_insurance,commercial_registration,captain_license,other',
            'document_number' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'boat_id' => 'nullable|exists:boats,id',
        ]);

        // Verify boat ownership if boat_id provided
        if (isset($validated['boat_id'])) {
            $boat = Boat::findOrFail($validated['boat_id']);
            if ($boat->captain_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        // Store file
        $filePath = $request->file('file')->store('verification-documents', 'public');

        $document = VerificationDocument::create([
            'user_id' => $user->id,
            'boat_id' => $validated['boat_id'] ?? null,
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'file_path' => Storage::url($filePath),
            'status' => 'pending',
        ]);

        return response()->json($document, 201);
    }

    public function getDocuments(Request $request)
    {
        $user = $request->user();

        $query = VerificationDocument::where('user_id', $user->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        $documents = $query->with('reviewer')->orderBy('created_at', 'desc')->get();

        return response()->json($documents);
    }

    public function reviewDocument(Request $request, VerificationDocument $document)
    {
        // Only admins can review documents
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|max:1000',
        ]);

        $document->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // If approved, check if user should be verified
        if ($validated['status'] === 'approved') {
            $this->checkUserVerification($document->user);
        }

        return response()->json($document->load('reviewer'));
    }

    protected function checkUserVerification(User $user)
    {
        // Check if user has all required documents approved
        $requiredDocs = $user->isCaptain() 
            ? ['marine_license', 'boat_insurance', 'commercial_registration']
            : ['commercial_registration'];

        $approvedDocs = VerificationDocument::where('user_id', $user->id)
            ->where('status', 'approved')
            ->pluck('document_type')
            ->toArray();

        $hasAllDocs = count(array_intersect($requiredDocs, $approvedDocs)) === count($requiredDocs);

        if ($hasAllDocs) {
            $user->update(['is_verified' => true]);
        }
    }
}

