<?php

namespace App\Events;

use App\Models\TripLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $location;
    public $tripLogId;
    public $bookingId;

    public function __construct(TripLocation $location)
    {
        $this->location = $location->load('tripLog');
        $this->tripLogId = $location->trip_log_id;
        $this->bookingId = $location->booking_id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('trip.' . $this->tripLogId),
            new PrivateChannel('booking.' . $this->bookingId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->location->id,
            'latitude' => (float) $this->location->latitude,
            'longitude' => (float) $this->location->longitude,
            'speed_knots' => $this->location->speed_knots ? (float) $this->location->speed_knots : null,
            'heading_degrees' => $this->location->heading_degrees ? (float) $this->location->heading_degrees : null,
            'distance_from_start_nm' => (float) $this->location->distance_from_start_nm,
            'recorded_at' => $this->location->recorded_at->toIso8601String(),
        ];
    }
}

