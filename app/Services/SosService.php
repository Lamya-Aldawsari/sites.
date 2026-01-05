<?php

namespace App\Services;

use App\Models\SosAlert;
use App\Models\Booking;
use App\Models\EmergencyContact;
use App\Models\TripLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SosService
{
    /**
     * Create SOS alert with GPS location
     */
    public function createSosAlert(
        Booking $booking,
        int $userId,
        float $latitude,
        float $longitude,
        ?string $message = null
    ): SosAlert {
        DB::beginTransaction();
        try {
            $sosAlert = SosAlert::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'message' => $message ?? 'Emergency SOS alert activated',
                'status' => 'active',
            ]);

            // Update trip log status if exists
            $tripLog = TripLog::where('booking_id', $booking->id)->where('status', 'active')->first();
            if ($tripLog) {
                $tripLog->update(['status' => 'emergency']);
            }

            // Notify emergency contacts
            $this->notifyEmergencyContacts($booking, $sosAlert);

            // Notify platform admins
            $this->notifyAdmins($sosAlert);

            // Attempt to notify emergency services (low bandwidth - SMS/Email fallback)
            $this->notifyEmergencyServices($sosAlert);

            DB::commit();

            // Broadcast SOS alert
            broadcast(new \App\Events\SosAlertCreated($sosAlert))->toOthers();

            return $sosAlert;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create SOS alert: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Notify emergency contacts
     */
    protected function notifyEmergencyContacts(Booking $booking, SosAlert $sosAlert): void
    {
        $user = $booking->customer;
        if ($booking->customer_id === $sosAlert->user_id) {
            $user = $booking->captain;
        }

        $contacts = EmergencyContact::where('user_id', $user->id)
            ->where('notify_on_sos', true)
            ->get();

        foreach ($contacts as $contact) {
            // Send SMS (low bandwidth)
            $this->sendSms($contact->phone, $this->formatSosSms($sosAlert, $booking));
            
            // Send email if available
            if ($contact->email) {
                // Queue email notification (low priority, won't block)
                \App\Jobs\SendSosEmail::dispatch($contact->email, $sosAlert, $booking);
            }
        }
    }

    /**
     * Notify platform admins
     */
    protected function notifyAdmins(SosAlert $sosAlert): void
    {
        $admins = \App\Models\User::where('role', 'admin')->where('is_active', true)->get();

        foreach ($admins as $admin) {
            // Broadcast notification
            broadcast(new \App\Events\SosAlertCreated($sosAlert))->toOthers();
            
            // Send email notification
            \App\Jobs\SendSosEmail::dispatch($admin->email, $sosAlert, $sosAlert->booking)->onQueue('high');
        }
    }

    /**
     * Notify emergency services (Coast Guard, etc.)
     */
    protected function notifyEmergencyServices(SosAlert $sosAlert): void
    {
        // Format emergency message (low bandwidth - minimal data)
        $message = $this->formatEmergencyServiceMessage($sosAlert);

        // Try SMS first (most reliable with low bandwidth)
        $emergencyNumber = config('services.emergency.sms_number');
        if ($emergencyNumber) {
            $this->sendSms($emergencyNumber, $message);
        }

        // Try API if available
        $emergencyApiUrl = config('services.emergency.api_url');
        if ($emergencyApiUrl) {
            try {
                Http::timeout(5)->post($emergencyApiUrl, [
                    'type' => 'sos',
                    'latitude' => $sosAlert->latitude,
                    'longitude' => $sosAlert->longitude,
                    'timestamp' => $sosAlert->created_at->toIso8601String(),
                    'message' => $sosAlert->message,
                ]);
            } catch (\Exception $e) {
                Log::warning('Emergency service API unavailable, SMS sent instead');
            }
        }
    }

    /**
     * Format SOS SMS message (low bandwidth optimized)
     */
    protected function formatSosSms(SosAlert $sosAlert, Booking $booking): string
    {
        return sprintf(
            "SOS ALERT: %s\nLocation: %.6f,%.6f\nTime: %s\nBooking: #%d\nView: %s/trip/%d",
            $sosAlert->message,
            $sosAlert->latitude,
            $sosAlert->longitude,
            $sosAlert->created_at->format('Y-m-d H:i:s'),
            $booking->id,
            config('app.url'),
            $sosAlert->id
        );
    }

    /**
     * Format emergency service message
     */
    protected function formatEmergencyServiceMessage(SosAlert $sosAlert): string
    {
        return sprintf(
            "MARINE EMERGENCY\nLat: %.6f\nLng: %.6f\nTime: %s\nID: %d",
            $sosAlert->latitude,
            $sosAlert->longitude,
            $sosAlert->created_at->format('Y-m-d H:i:s'),
            $sosAlert->id
        );
    }

    /**
     * Send SMS (low bandwidth method)
     */
    protected function sendSms(string $phone, string $message): void
    {
        // Use SMS service (Twilio, etc.)
        // For now, log it (implement actual SMS service)
        Log::info('SOS SMS', [
            'to' => $phone,
            'message' => $message,
        ]);

        // TODO: Integrate with Twilio or similar SMS service
        // Twilio::message($phone, $message);
    }
}

