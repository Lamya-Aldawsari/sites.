# Database Structure Documentation

## Overview

This document outlines the complete database structure for the iBoat Marine Platform, supporting multi-vendor and multi-role architecture.

## Core Tables

### Users Table
**Purpose**: Stores all user accounts (Customers, Captains/Owners, Vendors, Admins)

**Key Fields**:
- `id` - Primary key
- `email` - Unique email address
- `role` - Enum: 'customer', 'captain', 'owner', 'vendor', 'admin'
- `is_verified` - Boolean for verification status
- `is_active` - Boolean for account status
- `verification_documents` - JSON field for document references

**Relationships**:
- Has many `boats` (as captain/owner)
- Has many `bookings` (as customer)
- Has many `captainBookings` (as captain)
- Has many `equipment` (as vendor)
- Has many `verificationDocuments`
- Has many `reviews`
- Has many `transactions`

### Boats Table
**Purpose**: Stores boat listings owned by captains/owners

**Key Fields**:
- `id` - Primary key
- `captain_id` - Foreign key to users (captain/owner)
- `name` - Boat name
- `slug` - SEO-friendly URL slug
- `description` - Full description
- `meta_title`, `meta_description`, `meta_keywords` - SEO fields
- `type` - Enum: yacht, sailboat, speedboat, fishing_boat, catamaran, houseboat, other
- `hourly_rate`, `daily_rate`, `weekly_rate` - Pricing
- `latitude`, `longitude` - GPS coordinates
- `is_available`, `is_verified` - Status flags
- `rating`, `total_reviews` - Review statistics

**Relationships**:
- Belongs to `captain` (User)
- Has many `bookings`
- Has many `reviews` (polymorphic)
- Has many `availability` entries
- Has many `locations` (GPS tracking)
- Has many `verificationDocuments`

### Bookings Table
**Purpose**: Stores boat rental bookings

**Key Fields**:
- `id` - Primary key
- `customer_id` - Foreign key to users (customer)
- `boat_id` - Foreign key to boats
- `captain_id` - Foreign key to users (captain)
- `booking_type` - Enum: hourly, daily, weekly
- `booking_mode` - Enum: on_demand, scheduled
- `requires_captain` - Boolean (always true)
- `start_time`, `end_time` - Booking period
- `total_amount` - Total booking cost
- `status` - Enum: pending, confirmed, in_progress, completed, cancelled
- `payment_status` - Enum: pending, paid, refunded, failed
- `payment_intent_id` - Stripe payment intent

**Relationships**:
- Belongs to `customer` (User)
- Belongs to `boat`
- Belongs to `captain` (User)
- Has one `review`
- Has one `paymentHold`
- Has many `splitPayments` (polymorphic)
- Has many `transactions` (polymorphic)
- Has many `sosAlerts`

### Equipment Table
**Purpose**: Stores marine equipment listings from vendors

**Key Fields**:
- `id` - Primary key
- `vendor_id` - Foreign key to users (vendor)
- `name` - Equipment name
- `slug` - SEO-friendly URL slug
- `description` - Full description
- `meta_title`, `meta_description`, `meta_keywords` - SEO fields
- `category` - Enum: safety, navigation, fishing, water_sports, maintenance, other
- `daily_rate`, `weekly_rate` - Pricing
- `quantity_available` - Stock quantity
- `is_available` - Availability flag

**Relationships**:
- Belongs to `vendor` (User)
- Has many `rentals`
- Has many `reviews` (polymorphic)

### Equipment Rentals Table
**Purpose**: Stores equipment rental transactions

**Key Fields**:
- `id` - Primary key
- `customer_id` - Foreign key to users
- `equipment_id` - Foreign key to equipment
- `vendor_id` - Foreign key to users (vendor)
- `quantity` - Rental quantity
- `rental_start_date`, `rental_end_date` - Rental period
- `total_amount` - Total rental cost
- `status` - Enum: pending, confirmed, active, completed, cancelled

**Relationships**:
- Belongs to `customer` (User)
- Belongs to `equipment`
- Belongs to `vendor` (User)
- Has one `review`

## Supporting Tables

### Verification Documents
**Purpose**: Stores uploaded verification documents for captains/vendors

**Key Fields**:
- `user_id` - Foreign key to users
- `boat_id` - Optional foreign key to boats
- `document_type` - Enum: marine_license, boat_insurance, commercial_registration, captain_license
- `file_path` - Document file path
- `status` - Enum: pending, approved, rejected
- `reviewed_by` - Foreign key to users (admin)

### Payment Holds
**Purpose**: Tracks payment holds for bookings

**Key Fields**:
- `booking_id` - Foreign key to bookings
- `stripe_payment_intent_id` - Stripe payment intent
- `amount` - Hold amount
- `status` - Enum: held, captured, released, expired
- `hold_expires_at` - Expiration timestamp

### Split Payments
**Purpose**: Tracks split payments between platform and vendors/captains

**Key Fields**:
- `paymentable_type`, `paymentable_id` - Polymorphic relation (booking/order)
- `total_amount` - Total payment amount
- `platform_fee` - Platform commission (15%)
- `vendor_amount` / `captain_amount` - Payout amounts
- `status` - Enum: pending, processing, completed, failed

### SOS Alerts
**Purpose**: Emergency SOS alerts during trips

**Key Fields**:
- `booking_id` - Foreign key to bookings
- `user_id` - Foreign key to users (who triggered)
- `latitude`, `longitude` - GPS coordinates
- `status` - Enum: active, acknowledged, resolved, false_alarm
- `responded_by` - Foreign key to users (admin)

### SEO Settings
**Purpose**: Stores SEO meta tags and settings for pages

**Key Fields**:
- `page_type` - Page identifier (home, boats, equipment, etc.)
- `page_identifier` - Optional specific page ID
- `meta_title`, `meta_description`, `meta_keywords` - SEO fields
- `og_title`, `og_description`, `og_image` - Open Graph tags
- `canonical_url` - Canonical URL
- `structured_data` - JSON-LD structured data

## Role-Based Access

### Customer Role
- Can create bookings
- Can rent equipment
- Can leave reviews
- Can trigger SOS alerts

### Captain/Owner Role
- Can create and manage boats
- Can view their bookings
- Must upload verification documents
- Receives split payments

### Vendor Role
- Can create and manage equipment
- Can manage inventory
- Must upload verification documents
- Receives split payments

### Admin Role
- Full system access
- Can review verification documents
- Can manage all bookings
- Can respond to SOS alerts
- Can manage SEO settings
- Can purge Cloudflare cache

## Indexes

### Performance Indexes
- `users.email` - Unique index
- `users.role` - Index for role-based queries
- `boats.captain_id` - Index for captain's boats
- `boats.slug` - Unique index for SEO
- `bookings.customer_id` - Index for customer bookings
- `bookings.boat_id` - Index for boat bookings
- `bookings.status` - Index for status filtering
- `equipment.vendor_id` - Index for vendor's equipment
- `equipment.slug` - Unique index for SEO

## Relationships Summary

```
Users (1) -> (Many) Boats (as captain)
Users (1) -> (Many) Bookings (as customer)
Users (1) -> (Many) Bookings (as captain)
Users (1) -> (Many) Equipment (as vendor)
Users (1) -> (Many) VerificationDocuments

Boats (1) -> (Many) Bookings
Boats (1) -> (Many) Reviews (polymorphic)
Boats (1) -> (Many) BoatAvailability
Boats (1) -> (Many) BoatLocations

Bookings (1) -> (1) PaymentHold
Bookings (1) -> (Many) SplitPayments (polymorphic)
Bookings (1) -> (Many) SosAlerts

Equipment (1) -> (Many) EquipmentRentals
Equipment (1) -> (Many) Reviews (polymorphic)
```

## Data Integrity

### Foreign Key Constraints
- All foreign keys use `onDelete('cascade')` for dependent records
- Soft deletes used for main entities (users, boats, bookings, equipment)
- Verification documents use `onDelete('set null')` for reviewers

### Unique Constraints
- User emails are unique
- Boat slugs are unique
- Equipment slugs are unique
- SEO settings have unique combination of page_type and page_identifier

## Migration Order

1. Users table (base for all relationships)
2. Boats table (depends on users)
3. Bookings table (depends on users and boats)
4. Equipment table (depends on users)
5. Supporting tables (verification, payments, etc.)
6. SEO fields (added to existing tables)

