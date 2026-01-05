<?php

namespace App\Jobs;

use App\Models\SosAlert;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSosEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $sosAlert;
    public $booking;

    public function __construct(string $email, SosAlert $sosAlert, Booking $booking)
    {
        $this->email = $email;
        $this->sosAlert = $sosAlert;
        $this->booking = $booking;
    }

    public function handle(): void
    {
        // Send SOS email notification
        // Implementation depends on your mail service
        Mail::raw($this->getEmailContent(), function ($message) {
            $message->to($this->email)
                    ->subject('SOS Alert - Emergency Assistance Required');
        });
    }

    protected function getEmailContent(): string
    {
        return sprintf(
            "SOS ALERT - Emergency Assistance Required\n\n" .
            "A SOS alert has been activated.\n\n" .
            "Location: %.6f, %.6f\n" .
            "Time: %s\n" .
            "Booking ID: %d\n" .
            "User: %s\n\n" .
            "Please respond immediately.\n\n" .
            "View details: %s/dashboard/sos/%d",
            $this->sosAlert->latitude,
            $this->sosAlert->longitude,
            $this->sosAlert->created_at->format('Y-m-d H:i:s'),
            $this->booking->id,
            $this->sosAlert->user->name,
            config('app.url'),
            $this->sosAlert->id
        );
    }
}

