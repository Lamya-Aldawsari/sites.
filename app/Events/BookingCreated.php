<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking->load(['customer', 'boat']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->booking->captain_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->booking->id,
            'customer' => [
                'id' => $this->booking->customer->id,
                'name' => $this->booking->customer->name,
            ],
            'boat' => [
                'id' => $this->booking->boat->id,
                'name' => $this->booking->boat->name,
            ],
            'start_time' => $this->booking->start_time->toIso8601String(),
            'total_amount' => $this->booking->total_amount,
            'status' => $this->booking->status,
        ];
    }
}

