# Database Migrations & Models - Complete Reference

## Migration Files

All migrations are located in `database/migrations/` and should be run in order:

1. `2024_01_01_000001_create_users_table.php` - Base users table
2. `2024_01_01_000002_create_boats_table.php` - Boat listings
3. `2024_01_01_000003_create_bookings_table.php` - Booking system
4. `2024_01_01_000004_create_equipment_table.php` - Equipment marketplace
5. `2024_01_01_000005_create_equipment_rentals_table.php` - Equipment rentals
6. `2024_01_01_000006_create_reviews_table.php` - Review system
7. `2024_01_01_000007_create_transactions_table.php` - Payment transactions
8. `2024_01_01_000008_create_notifications_table.php` - Notifications
9. `2024_01_01_000009_create_boat_availability_table.php` - Availability calendar
10. `2024_01_01_000010_create_boat_locations_table.php` - GPS tracking
11. `2024_01_01_000011_add_booking_mode_to_bookings.php` - On-demand support
12. `2024_01_01_000012_create_carts_table.php` - Shopping cart
13. `2024_01_01_000013_create_orders_table.php` - E-commerce orders
14. `2024_01_01_000014_create_verification_documents_table.php` - Verification system
15. `2024_01_01_000015_create_payment_holds_table.php` - Payment holds
16. `2024_01_01_000016_create_split_payments_table.php` - Split payments
17. `2024_01_01_000017_create_sos_alerts_table.php` - SOS alerts
18. `2024_01_01_000018_add_seo_fields_to_boats.php` - SEO for boats
19. `2024_01_01_000019_add_seo_fields_to_equipment.php` - SEO for equipment
20. `2024_01_01_000020_create_seo_settings_table.php` - SEO settings
21. `2024_01_01_000021_update_users_role_enum.php` - Add owner role

## Core Models

### User Model (`app/Models/User.php`)
**Roles Supported**: customer, captain, owner, vendor, admin

**Key Methods**:
- `isCustomer()` - Check if user is customer
- `isCaptain()` - Check if user is captain or owner
- `isOwner()` - Check if user is owner or captain
- `isVendor()` - Check if user is vendor
- `isAdmin()` - Check if user is admin

**Relationships**:
- `boats()` - Boats owned by captain/owner
- `bookings()` - Bookings as customer
- `captainBookings()` - Bookings as captain
- `equipment()` - Equipment as vendor
- `verificationDocuments()` - Uploaded documents

### Boat Model (`app/Models/Boat.php`)
**SEO Fields**: slug, meta_title, meta_description, meta_keywords, og_image

**Key Methods**:
- `scopeAvailable()` - Filter available boats
- `scopeNearby()` - Filter by location (Haversine)
- `scopeSearch()` - Search boats

**Relationships**:
- `captain()` - Owner/captain (User)
- `bookings()` - All bookings
- `reviews()` - Reviews (polymorphic)
- `availability()` - Calendar availability
- `locations()` - GPS tracking history

### Booking Model (`app/Models/Booking.php`)
**Booking Modes**: on_demand, scheduled

**Key Methods**:
- `isOnDemand()` - Check if on-demand booking
- `isScheduled()` - Check if scheduled booking
- `scopeOnDemand()` - Filter on-demand bookings
- `scopeScheduled()` - Filter scheduled bookings

**Relationships**:
- `customer()` - Customer (User)
- `boat()` - Booked boat
- `captain()` - Captain (User)
- `paymentHold()` - Payment hold
- `splitPayments()` - Split payment records

### Equipment Model (`app/Models/Equipment.php`)
**SEO Fields**: slug, meta_title, meta_description, meta_keywords, og_image

**Key Methods**:
- `scopeAvailable()` - Filter available equipment
- `scopeByCategory()` - Filter by category

**Relationships**:
- `vendor()` - Vendor (User)
- `rentals()` - Equipment rentals
- `reviews()` - Reviews (polymorphic)

## Running Migrations

### Fresh Migration (Development)
```bash
php artisan migrate:fresh --seed
```

### Production Migration
```bash
php artisan migrate
```

### Rollback
```bash
php artisan migrate:rollback
```

## Model Relationships Verification

### User → Boats
```php
$user->boats; // Collection of boats owned by user
$boat->captain; // User who owns the boat
```

### User → Bookings
```php
$user->bookings; // Bookings as customer
$user->captainBookings; // Bookings as captain
$booking->customer; // Customer user
$booking->captain; // Captain user
```

### Boat → Bookings
```php
$boat->bookings; // All bookings for boat
$booking->boat; // Booked boat
```

### Vendor → Equipment
```php
$user->equipment; // Equipment owned by vendor
$equipment->vendor; // Vendor user
```

## Indexes & Performance

### Key Indexes
- `users.email` - Unique index
- `users.role` - Index for role queries
- `boats.captain_id` - Index for captain's boats
- `boats.slug` - Unique index for SEO
- `bookings.customer_id` - Index for customer bookings
- `bookings.boat_id` - Index for boat bookings
- `bookings.status` - Index for status filtering

### Query Optimization
- Use eager loading: `Boat::with('captain', 'reviews')->get()`
- Use scopes: `Boat::available()->nearby($lat, $lng)->get()`
- Cache frequently accessed data: `Cache::remember('boats', 3600, fn() => Boat::all())`

## Data Integrity

### Foreign Key Constraints
- All foreign keys cascade on delete
- Soft deletes preserve referential integrity
- Unique constraints prevent duplicates

### Validation Rules
- Email uniqueness enforced at database level
- Slug uniqueness enforced at database level
- Role enum values enforced at database level

## Testing Relationships

```php
// Test User-Boat relationship
$captain = User::where('role', 'captain')->first();
$boat = Boat::create([...]);
$captain->boats()->save($boat);
assert($boat->captain_id === $captain->id);

// Test Booking relationships
$booking = Booking::create([...]);
assert($booking->customer instanceof User);
assert($booking->boat instanceof Boat);
assert($booking->captain instanceof User);

// Test Equipment-Vendor relationship
$vendor = User::where('role', 'vendor')->first();
$equipment = Equipment::create([...]);
$vendor->equipment()->save($equipment);
assert($equipment->vendor_id === $vendor->id);
```

