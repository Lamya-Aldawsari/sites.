<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Boat;
use App\Models\Booking;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\SplitPayment;
use App\Models\PaymentHold;
use App\Models\VerificationDocument;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isCaptain() || $user->isOwner()) {
            return $this->captainDashboard($user);
        } elseif ($user->isVendor()) {
            return $this->vendorDashboard($user);
        }

        return response()->json(['message' => 'Dashboard not available for this role'], 403);
    }

    protected function captainDashboard(User $user)
    {
        // Boats
        $boats = Boat::where('captain_id', $user->id)
            ->with(['reviews', 'availability'])
            ->get();

        // Bookings
        $bookings = Booking::where('captain_id', $user->id)
            ->with(['customer', 'boat'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Statistics
        $stats = [
            'total_boats' => $boats->count(),
            'active_bookings' => Booking::where('captain_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->count(),
            'pending_bookings' => Booking::where('captain_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'total_earnings' => SplitPayment::whereHas('paymentable', function ($query) use ($user) {
                $query->where('captain_id', $user->id);
            })
            ->where('status', 'completed')
            ->sum('captain_amount'),
            'pending_earnings' => PaymentHold::whereHas('booking', function ($query) use ($user) {
                $query->where('captain_id', $user->id);
            })
            ->where('status', 'held')
            ->sum('amount'),
        ];

        // Recent earnings
        $recentEarnings = SplitPayment::whereHas('paymentable', function ($query) use ($user) {
            $query->where('captain_id', $user->id);
        })
        ->where('status', 'completed')
        ->orderBy('processed_at', 'desc')
        ->limit(5)
        ->get();

        // Verification status
        $verificationStatus = $this->getVerificationStatus($user);

        // Unread messages
        $unreadMessages = Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'role' => 'captain',
            'stats' => $stats,
            'boats' => $boats,
            'recent_bookings' => $bookings,
            'recent_earnings' => $recentEarnings,
            'verification_status' => $verificationStatus,
            'unread_messages' => $unreadMessages,
        ]);
    }

    protected function vendorDashboard(User $user)
    {
        // Equipment
        $equipment = Equipment::where('vendor_id', $user->id)
            ->with('reviews')
            ->get();

        // Orders
        $orders = Order::whereHas('items', function ($query) use ($user) {
            $query->where('vendor_id', $user->id);
        })
        ->with(['items.equipment', 'customer'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        // Statistics
        $stats = [
            'total_equipment' => $equipment->count(),
            'available_quantity' => $equipment->sum('quantity_available'),
            'pending_orders' => Order::whereHas('items', function ($query) use ($user) {
                $query->where('vendor_id', $user->id);
            })
            ->where('status', 'processing')
            ->count(),
            'total_earnings' => SplitPayment::whereHas('paymentable', function ($query) use ($user) {
                $query->whereHas('items', function ($q) use ($user) {
                    $q->where('vendor_id', $user->id);
                });
            })
            ->where('status', 'completed')
            ->sum('vendor_amount'),
            'total_orders' => Order::whereHas('items', function ($query) use ($user) {
                $query->where('vendor_id', $user->id);
            })
            ->count(),
        ];

        // Recent earnings
        $recentEarnings = SplitPayment::whereHas('paymentable', function ($query) use ($user) {
            $query->whereHas('items', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            });
        })
        ->where('status', 'completed')
        ->orderBy('processed_at', 'desc')
        ->limit(5)
        ->get();

        // Verification status
        $verificationStatus = $this->getVerificationStatus($user);

        // Unread messages
        $unreadMessages = Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'role' => 'vendor',
            'stats' => $stats,
            'equipment' => $equipment,
            'recent_orders' => $orders,
            'recent_earnings' => $recentEarnings,
            'verification_status' => $verificationStatus,
            'unread_messages' => $unreadMessages,
        ]);
    }

    protected function getVerificationStatus(User $user): array
    {
        $requiredDocs = $user->isCaptain() 
            ? ['marine_license', 'boat_insurance', 'commercial_registration']
            : ['commercial_registration'];

        $documents = VerificationDocument::where('user_id', $user->id)->get();
        
        $status = [
            'is_verified' => $user->is_verified,
            'required_documents' => $requiredDocs,
            'uploaded_documents' => [],
            'pending_count' => 0,
            'approved_count' => 0,
            'rejected_count' => 0,
        ];

        foreach ($requiredDocs as $docType) {
            $doc = $documents->where('document_type', $docType)->first();
            $status['uploaded_documents'][$docType] = [
                'uploaded' => $doc !== null,
                'status' => $doc ? $doc->status : 'not_uploaded',
                'reviewed_at' => $doc ? $doc->reviewed_at : null,
            ];

            if ($doc) {
                if ($doc->status === 'pending') $status['pending_count']++;
                elseif ($doc->status === 'approved') $status['approved_count']++;
                elseif ($doc->status === 'rejected') $status['rejected_count']++;
            }
        }

        return $status;
    }

    public function getBookings(Request $request)
    {
        $user = $request->user();
        $query = Booking::where('captain_id', $user->id)
            ->with(['customer', 'boat', 'paymentHold']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('booking_mode')) {
            $query->where('booking_mode', $request->booking_mode);
        }

        if ($request->has('date_from')) {
            $query->where('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('end_time', '<=', $request->date_to);
        }

        $bookings = $query->orderBy('start_time', 'desc')->paginate(20);

        return response()->json($bookings);
    }

    public function getOrders(Request $request)
    {
        $user = $request->user();
        
        $query = Order::whereHas('items', function ($q) use ($user) {
            $q->where('vendor_id', $user->id);
        })
        ->with(['items.equipment', 'customer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($orders);
    }

    public function getEarnings(Request $request)
    {
        $user = $request->user();
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        if ($user->isCaptain() || $user->isOwner()) {
            $earnings = SplitPayment::whereHas('paymentable', function ($query) use ($user) {
                $query->where('captain_id', $user->id);
            })
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->get();

            $total = $earnings->sum('captain_amount');
        } else {
            $earnings = SplitPayment::whereHas('paymentable', function ($query) use ($user) {
                $query->whereHas('items', function ($q) use ($user) {
                    $q->where('vendor_id', $user->id);
                });
            })
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->get();

            $total = $earnings->sum('vendor_amount');
        }

        return response()->json([
            'earnings' => $earnings,
            'total' => $total,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }
}

