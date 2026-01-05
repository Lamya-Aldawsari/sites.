# Key Features Implementation

## Overview

This document outlines the implementation of key functional requirements for the iBoat Marine Platform.

## 1. Verification System

### Features
- **Document Upload**: Captains and vendors can upload verification documents
- **Document Types**:
  - Marine License
  - Boat Insurance
  - Commercial Registration
  - Captain License
- **Admin Review**: Administrators review and approve/reject documents
- **Automatic Verification**: Users are automatically verified when all required documents are approved

### API Endpoints
- `POST /api/verification/documents` - Upload verification document
- `GET /api/verification/documents` - Get user's documents
- `POST /api/verification/documents/{id}/review` - Review document (Admin only)

### Database Schema
- `verification_documents` table stores:
  - Document type, number, expiry date
  - File path
  - Status (pending/approved/rejected)
  - Review information

### Frontend
- Verification page with document upload form
- Document status display
- File upload with validation

## 2. Smart Booking Engine

### Calendar System
- **Availability Checking**: Prevents double bookings
- **Calendar View**: Shows available/unavailable dates
- **Date Blocking**: Captains can block specific dates
- **Real-time Updates**: Calendar updates in real-time

### Payment Hold Feature
- **Hold Instead of Charge**: Payment is held, not immediately charged
- **Expiration**: Holds expire after 7 days (configurable)
- **Capture on Trip Start**: Payment captured when trip starts
- **Automatic Release**: Hold released if booking cancelled

### API Endpoints
- `GET /api/boats/{id}/availability` - Get availability calendar
- `POST /api/boats/{id}/check-availability` - Check specific time slot
- `POST /api/boats/{id}/block-dates` - Block dates (Captain only)
- `POST /api/bookings/{id}/hold-payment` - Create payment hold
- `POST /api/bookings/{id}/capture-payment` - Capture held payment

### Database Schema
- `payment_holds` table tracks:
  - Stripe payment intent ID
  - Hold amount and status
  - Expiration date
  - Capture/release timestamps

## 3. Split Payment System

### Features
- **Platform Fee**: 15% platform fee on all transactions
- **Vendor/Captain Payout**: Remaining amount transferred to vendor/captain
- **Stripe Connect**: Uses Stripe Connect for transfers
- **Automatic Processing**: Split payments processed automatically on capture

### Payment Flow
1. Customer pays full amount
2. Payment held (not captured)
3. On trip completion, payment captured
4. Platform fee deducted (15%)
5. Remaining amount transferred to vendor/captain

### API Endpoints
- Split payments created automatically with bookings/orders
- `POST /api/bookings/{id}/capture-payment` - Triggers split payment processing

### Database Schema
- `split_payments` table stores:
  - Total amount
  - Platform fee
  - Vendor/Captain amount
  - Transfer IDs
  - Processing status

## 4. Safety Features

### Live GPS Tracking
- **Real-time Location**: Boat location updated in real-time during trips
- **Location History**: Track location history for completed trips
- **Speed & Heading**: Track boat speed and heading
- **Map Integration**: Display on map for customers and admins

### SOS Button
- **Emergency Alert**: In-app SOS button for emergencies
- **Location Included**: SOS alerts include current GPS location
- **Admin Notification**: Admins notified immediately
- **Status Tracking**: Track SOS alert status (active/acknowledged/resolved)

### API Endpoints
- `POST /api/sos` - Create SOS alert
- `GET /api/sos/active` - Get active SOS alerts
- `POST /api/sos/{id}/acknowledge` - Acknowledge SOS (Admin)
- `POST /api/sos/{id}/resolve` - Resolve SOS alert

### Database Schema
- `sos_alerts` table stores:
  - Booking ID
  - User ID
  - GPS coordinates
  - Message
  - Status and timestamps

### Frontend Components
- `SosButton` component for emergency alerts
- Real-time GPS tracking display
- Map integration for location visualization

## 5. Localization (Bilingual Support)

### Languages Supported
- **English** (en) - Default
- **Modern Standard Arabic** (ar) - Full RTL support

### Implementation
- **Translation Files**: Laravel translation files in `lang/en` and `lang/ar`
- **Middleware**: `SetLocale` middleware detects and sets locale
- **Session Storage**: Locale stored in session
- **API Header**: Accept-Language header support

### Translation Files
- `lang/en/messages.php` - English translations
- `lang/ar/messages.php` - Arabic translations

### Frontend
- Language switcher component
- RTL CSS support for Arabic
- Dynamic text replacement

### API Endpoints
- `POST /api/locale` - Set user locale
- Locale automatically detected from Accept-Language header

## Usage Examples

### Upload Verification Document
```bash
POST /api/verification/documents
Content-Type: multipart/form-data

document_type: marine_license
document_number: ML-12345
expiry_date: 2025-12-31
file: [PDF file]
```

### Check Availability
```bash
POST /api/boats/1/check-availability
{
  "start_time": "2024-12-25 10:00:00",
  "end_time": "2024-12-25 18:00:00"
}
```

### Create SOS Alert
```bash
POST /api/sos
{
  "booking_id": 1,
  "latitude": 25.7907,
  "longitude": -80.1300,
  "message": "Emergency situation"
}
```

### Set Locale
```bash
POST /api/locale
{
  "locale": "ar"
}
```

## Security Considerations

### Verification Documents
- File validation (PDF, JPG, PNG only)
- File size limit (5MB)
- Secure file storage
- Admin-only review access

### Payment Holds
- Secure Stripe integration
- Automatic expiration
- Proper cancellation handling

### SOS Alerts
- Only active during bookings
- Location verification
- Admin-only resolution

## Future Enhancements

1. **Document OCR**: Automatic document information extraction
2. **Expiry Notifications**: Alert users before document expiry
3. **Advanced Calendar**: Recurring availability patterns
4. **Payment Plans**: Installment payment options
5. **Emergency Contacts**: Integration with emergency services
6. **Offline SOS**: SMS fallback for SOS alerts
7. **More Languages**: Additional language support
8. **Translation Management**: Admin interface for translations

