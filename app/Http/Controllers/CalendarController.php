<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    protected $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function getAvailability(Boat $boat, Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', now()));
        $endDate = Carbon::parse($request->get('end_date', now()->addMonths(3)));

        $calendar = $this->calendarService->getAvailabilityCalendar($boat, $startDate, $endDate);

        return response()->json([
            'boat_id' => $boat->id,
            'calendar' => $calendar,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);
    }

    public function checkAvailability(Boat $boat, Request $request)
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);

        $isAvailable = $this->calendarService->isAvailable($boat, $startTime, $endTime);

        return response()->json([
            'available' => $isAvailable,
            'start_time' => $startTime->toIso8601String(),
            'end_time' => $endTime->toIso8601String(),
        ]);
    }

    public function blockDates(Boat $boat, Request $request)
    {
        if ($boat->captain_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'dates' => 'required|array',
            'dates.*' => 'date',
            'reason' => 'nullable|string|max:255',
        ]);

        $this->calendarService->blockDates($boat, $validated['dates'], $validated['reason'] ?? null);

        return response()->json(['message' => 'Dates blocked successfully']);
    }
}

