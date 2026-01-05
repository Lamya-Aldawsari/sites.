<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoatController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\OnDemandBookingController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentRentalController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VendorDashboardController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BoatLocationController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\CloudflareController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\BookingActionController;
use App\Http\Controllers\TripTrackingController;
use App\Http\Controllers\BoatProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Boat routes (public listing, protected management)
Route::get('/boats', [BoatController::class, 'index']);
Route::get('/boats/search', [BoatController::class, 'search']);
Route::get('/boats/{boat}', [BoatProfileController::class, 'show']);

// Equipment routes (public listing, protected management)
Route::get('/equipment', [EquipmentController::class, 'index']);
Route::get('/equipment/{equipment}', [EquipmentController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Locale
    Route::post('/locale', function (\Illuminate\Http\Request $request) {
        $request->validate(['locale' => 'required|in:en,ar']);
        session(['locale' => $request->locale]);
        return response()->json(['locale' => $request->locale]);
    });

    // Boat management
    Route::post('/boats', [BoatController::class, 'store']);
    Route::put('/boats/{boat}', [BoatController::class, 'update']);
    Route::delete('/boats/{boat}', [BoatController::class, 'destroy']);

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/hold-payment', [BookingController::class, 'holdPayment']);
    Route::post('/bookings/{booking}/capture-payment', [BookingController::class, 'capturePayment']);

    // Equipment management
    Route::post('/equipment', [EquipmentController::class, 'store']);
    Route::put('/equipment/{equipment}', [EquipmentController::class, 'update']);
    Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy']);

    // Equipment Rentals
    Route::get('/equipment-rentals', [EquipmentRentalController::class, 'index']);
    Route::post('/equipment-rentals', [EquipmentRentalController::class, 'store']);
    Route::get('/equipment-rentals/{equipmentRental}', [EquipmentRentalController::class, 'show']);
    Route::post('/equipment-rentals/{equipmentRental}/confirm', [EquipmentRentalController::class, 'confirm']);
    Route::post('/equipment-rentals/{equipmentRental}/cancel', [EquipmentRentalController::class, 'cancel']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Boat Locations
    Route::post('/boats/{boat}/location', [BoatLocationController::class, 'update']);
    Route::get('/boats/{boat}/location', [BoatLocationController::class, 'current']);
    Route::get('/boats/{boat}/locations', [BoatLocationController::class, 'show']);

    // On-Demand Bookings (Uber Model)
    Route::get('/on-demand/nearby-boats', [OnDemandBookingController::class, 'findNearbyBoats']);
    Route::post('/on-demand/bookings', [OnDemandBookingController::class, 'create']);

    // Shopping Cart (E-commerce)
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{cartItem}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Orders (E-commerce)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm']);

    // Vendor Dashboard
    Route::get('/vendor/dashboard', [VendorDashboardController::class, 'dashboard']);
    Route::get('/vendor/inventory', [VendorDashboardController::class, 'inventory']);
    Route::put('/vendor/inventory/{equipment}', [VendorDashboardController::class, 'updateInventory']);
    Route::get('/vendor/orders', [VendorDashboardController::class, 'orders']);
    Route::put('/vendor/orders/{order}/status', [VendorDashboardController::class, 'updateOrderStatus']);

    // Verification System
    Route::post('/verification/documents', [VerificationController::class, 'uploadDocument']);
    Route::get('/verification/documents', [VerificationController::class, 'getDocuments']);
    Route::post('/verification/documents/{document}/review', [VerificationController::class, 'reviewDocument']);

    // Calendar & Availability
    Route::get('/boats/{boat}/availability', [CalendarController::class, 'getAvailability']);
    Route::post('/boats/{boat}/check-availability', [CalendarController::class, 'checkAvailability']);
    Route::post('/boats/{boat}/block-dates', [CalendarController::class, 'blockDates']);

    // SOS Alerts
    Route::post('/sos', [SosController::class, 'createSos']);
    Route::get('/sos/active', [SosController::class, 'getActiveSos']);
    Route::post('/sos/{sosAlert}/acknowledge', [SosController::class, 'acknowledgeSos']);
    Route::post('/sos/{sosAlert}/resolve', [SosController::class, 'resolveSos']);

    // SEO
    Route::get('/seo/meta-tags', [SeoController::class, 'getMetaTags']);
    Route::post('/seo/settings', [SeoController::class, 'updateSeoSettings']);

    // Cloudflare
    Route::post('/cloudflare/purge-cache', [CloudflareController::class, 'purgeCache']);
    Route::post('/cloudflare/purge-all', [CloudflareController::class, 'purgeAllCache']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/bookings', [DashboardController::class, 'getBookings']);
    Route::get('/dashboard/orders', [DashboardController::class, 'getOrders']);
    Route::get('/dashboard/earnings', [DashboardController::class, 'getEarnings']);

    // Booking Actions
    Route::post('/bookings/{booking}/accept', [BookingActionController::class, 'accept']);
    Route::post('/bookings/{booking}/reject', [BookingActionController::class, 'reject']);

    // Messages/Chat
    Route::get('/messages/conversations', [MessageController::class, 'getConversations']);
    Route::get('/messages/{otherUser}', [MessageController::class, 'getMessages']);
    Route::post('/messages', [MessageController::class, 'sendMessage']);
    Route::put('/messages/{message}/read', [MessageController::class, 'markAsRead']);

    // Trip Tracking
    Route::post('/bookings/{booking}/start-trip', [TripTrackingController::class, 'startTrip']);
    Route::post('/trips/{tripLog}/update-location', [TripTrackingController::class, 'updateLocation']);
    Route::post('/trips/{tripLog}/end-trip', [TripTrackingController::class, 'endTrip']);
    Route::get('/trips/{tripLog}/current-location', [TripTrackingController::class, 'getCurrentLocation']);
    Route::get('/trips/{tripLog}/route', [TripTrackingController::class, 'getTripRoute']);
    Route::get('/trips/active', [TripTrackingController::class, 'getActiveTrips']);
});
