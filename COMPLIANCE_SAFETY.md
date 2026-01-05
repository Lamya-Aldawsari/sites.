# Compliance and Safety Module

## Overview

Comprehensive safety and compliance system ensuring all boats meet safety standards and providing real-time tracking and emergency response capabilities.

## Features Implemented

### 1. Live Geo-Tracking

**Database Schema:**
- `trip_logs` - Main trip tracking table
- `trip_locations` - Real-time GPS location updates

**Features:**
- Real-time GPS tracking during active trips
- Location updates every 5 seconds
- Speed and heading tracking
- Distance calculation from start point
- Route history storage
- WebSocket broadcasting for live updates

**API Endpoints:**
- `POST /api/bookings/{id}/start-trip` - Start trip tracking
- `POST /api/trips/{id}/update-location` - Update GPS location
- `POST /api/trips/{id}/end-trip` - End trip tracking
- `GET /api/trips/{id}/current-location` - Get current location
- `GET /api/trips/{id}/route` - Get trip route
- `GET /api/trips/active` - Get all active trips

**React Component:**
- `LiveTrackingMap` - Google Maps integration with real-time marker updates

### 2. Emergency SOS System

**Features:**
- In-app SOS button with GPS location capture
- Low-bandwidth support (SMS fallback)
- Offline mode support (stores SOS locally, sends when online)
- Automatic notification to:
  - Emergency contacts
  - Platform admins
  - Emergency services (Coast Guard)
- SMS notifications for low-bandwidth scenarios

**Database:**
- `sos_alerts` - SOS alert records
- `emergency_contacts` - User emergency contacts

**React Component:**
- `SosButton` - Emergency SOS button with offline support

**Low-Bandwidth Optimizations:**
- SMS as primary notification method
- Minimal data payload
- Offline queue system
- Automatic retry when online

### 3. License Enforcement

**Backend Guard:**
- `EnsureBoatIsVerified` middleware
- Boat scope: `verified()` - Only shows boats with:
  - Verified captain license
  - Verified safety certificate
  - Active verification status

**Database Fields:**
- Users: `license_verified`, `license_number`, `license_expiry_date`
- Boats: `safety_certificate_verified`, `safety_certificate_number`, `safety_certificate_expiry`

**Enforcement:**
- Boats filtered in search results
- Only verified boats appear in listings
- Boat owners can see their own boats (even if unverified)
- Admins can see all boats

### 4. Operator Transparency

**Captain Profile Display:**
- Captain rating and total reviews
- Years of experience
- License verification status
- License expiry date
- Certifications array
- Verified photos with timestamps

**Boat Profile Display:**
- Safety certificate status
- Safety certificate expiry
- Last safety inspection date
- Safety rating (0-100)
- Verified photos with timestamps
- Captain transparency data

**API Endpoint:**
- `GET /api/boats/{id}` - Returns full transparency data

## Database Migrations

1. `create_trip_logs_table` - Trip tracking
2. `create_trip_locations_table` - GPS location history
3. `add_safety_fields_to_users` - Captain safety fields
4. `add_safety_fields_to_boats` - Boat safety fields
5. `create_emergency_contacts_table` - Emergency contacts

## Services

### GeoTrackingService
- Start/end trip tracking
- Update location in real-time
- Calculate distance and speed
- Broadcast location updates via WebSocket

### SosService
- Create SOS alerts
- Notify emergency contacts
- Notify platform admins
- Contact emergency services
- Low-bandwidth SMS support

## WebSocket Events

### TripLocationUpdated
- Broadcasts when trip location is updated
- Channels: `trip.{tripLogId}`, `booking.{bookingId}`
- Includes: lat, lng, speed, heading, distance

### SosAlertCreated
- Broadcasts when SOS is triggered
- Channels: `user.{captainId}`, `user.{customerId}`, `admin.sos`
- Includes: location, message, timestamp

## React Components

### SosButton
- Emergency SOS button
- GPS location capture
- Offline mode support
- Confirmation dialog
- Auto-retry when online

### LiveTrackingMap
- Google Maps integration
- Real-time marker updates
- Route polyline display
- Speed and heading display
- Location statistics

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Configure Pusher
```env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
```

### 3. Configure Google Maps
```env
GOOGLE_MAPS_API_KEY=your_google_maps_api_key
```

### 4. Configure Emergency Services
```env
EMERGENCY_SMS_NUMBER=+1234567890
EMERGENCY_API_URL=https://api.emergency-service.com/alerts
```

### 5. Install Frontend Dependencies
```bash
npm install laravel-echo pusher-js react-calendar date-fns
```

### 6. Configure Broadcasting
```bash
php artisan vendor:publish --provider="Laravel\Broadcasting\BroadcastServiceProvider"
```

## Usage Examples

### Start Trip Tracking
```javascript
// Captain starts trip
POST /api/bookings/1/start-trip
```

### Update Location (Every 5 seconds)
```javascript
POST /api/trips/1/update-location
{
  "latitude": 25.7907,
  "longitude": -80.1300,
  "speed_knots": 15.5,
  "heading_degrees": 180
}
```

### Trigger SOS
```javascript
POST /api/sos
{
  "booking_id": 1,
  "latitude": 25.7907,
  "longitude": -80.1300,
  "message": "Emergency situation"
}
```

### Listen for Location Updates
```javascript
const { echo } = usePusher();
const channel = echo.private(`trip.${tripLogId}`);
channel.listen('.location.updated', (data) => {
  // Update map marker
});
```

## Safety Compliance Checklist

- [x] License verification system
- [x] Safety certificate verification
- [x] Real-time GPS tracking
- [x] Emergency SOS system
- [x] Low-bandwidth SOS support
- [x] Emergency contact notifications
- [x] Operator transparency display
- [x] Verified photos system
- [x] Safety rating system
- [x] WebSocket real-time updates

## Future Enhancements

1. **Automated Safety Checks**: Periodic safety inspections
2. **Weather Integration**: Weather alerts during trips
3. **Geofencing**: Alert if boat goes outside allowed area
4. **Speed Monitoring**: Alert on excessive speed
5. **Emergency Response Integration**: Direct API to Coast Guard
6. **Trip Reports**: Automated trip completion reports
7. **Safety Analytics**: Track safety metrics over time

