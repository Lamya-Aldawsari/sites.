<?php

namespace App\Events;

use App\Models\SosAlert;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SosAlertCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sosAlert;

    public function __construct(SosAlert $sosAlert)
    {
        $this->sosAlert = $sosAlert->load(['booking', 'user']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->sosAlert->booking->captain_id),
            new PrivateChannel('user.' . $this->sosAlert->booking->customer_id),
            new Channel('admin.sos'), // Admin channel for all SOS alerts
        ];
    }

    public function broadcastAs(): string
    {
        return 'sos.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->sosAlert->id,
            'booking_id' => $this->sosAlert->booking_id,
            'user' => [
                'id' => $this->sosAlert->user->id,
                'name' => $this->sosAlert->user->name,
            ],
            'latitude' => (float) $this->sosAlert->latitude,
            'longitude' => (float) $this->sosAlert->longitude,
            'message' => $this->sosAlert->message,
            'created_at' => $this->sosAlert->created_at->toIso8601String(),
        ];
    }
}

