# iBoat Marine Platform - Architecture Documentation

## Overview

iBoat is a comprehensive Marine Transport & Rental Platform that connects boat owners (Captains), marine equipment vendors, and customers. It functions as an Uber/Airbnb hybrid specifically designed for the marine industry.

## System Architecture

### Backend (Laravel 10)

#### Database Schema

**Core Entities:**
- **Users**: Multi-role system (Customer, Captain, Vendor, Admin)
- **Boats**: Boat listings with availability, pricing, and location
- **Bookings**: Boat rental reservations with payment tracking
- **Equipment**: Marine equipment inventory
- **Equipment Rentals**: Equipment rental transactions
- **Reviews**: Rating and review system (polymorphic)
- **Transactions**: Payment and refund tracking
- **Boat Availability**: Calendar-based availability management
- **Boat Locations**: Real-time location tracking history

#### Key Features

1. **Authentication & Authorization**
   - Laravel Sanctum for API authentication
   - Role-based access control (RBAC)
   - User verification system for Captains/Vendors

2. **Boat Management**
   - CRUD operations for boats
   - Location-based search (Haversine formula)
   - Availability calendar
   - Image upload handling
   - Amenities and specifications

3. **Booking System**
   - Real-time availability checking
   - Dynamic pricing (hourly/daily/weekly)
   - Payment integration (Stripe)
   - Booking status workflow
   - Cancellation with refunds

4. **Equipment Rental**
   - Equipment inventory management
   - Quantity tracking
   - Rental period management
   - Payment processing

5. **Payment Processing**
   - Stripe integration
   - Payment intents
   - Refund processing
   - Transaction history

6. **Review System**
   - Polymorphic reviews (boats, equipment, users)
   - Rating aggregation
   - Verified reviews (only after completed bookings/rentals)

7. **Location Tracking**
   - Real-time boat location updates
   - Location history
   - Speed and heading tracking

### Frontend (React 18)

#### Technology Stack
- React 18 with React Router
- Tailwind CSS for styling
- Axios for API calls
- Vite for build tooling

#### Key Pages
- **Home**: Landing page with hero section
- **Boats**: Boat listing with search/filter
- **Boat Detail**: Individual boat view with booking form
- **Equipment**: Equipment marketplace
- **Login/Register**: Authentication pages
- **Dashboard**: User dashboard (role-specific)

## API Endpoints

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `GET /api/me` - Get current user

### Boats
- `GET /api/boats` - List boats (with filters)
- `GET /api/boats/search` - Search boats
- `GET /api/boats/{id}` - Get boat details
- `POST /api/boats` - Create boat (Captain only)
- `PUT /api/boats/{id}` - Update boat
- `DELETE /api/boats/{id}` - Delete boat

### Bookings
- `GET /api/bookings` - List bookings (filtered by role)
- `POST /api/bookings` - Create booking
- `GET /api/bookings/{id}` - Get booking details
- `POST /api/bookings/{id}/confirm` - Confirm payment
- `POST /api/bookings/{id}/cancel` - Cancel booking

### Equipment
- `GET /api/equipment` - List equipment
- `GET /api/equipment/{id}` - Get equipment details
- `POST /api/equipment` - Create equipment (Vendor only)
- `PUT /api/equipment/{id}` - Update equipment
- `DELETE /api/equipment/{id}` - Delete equipment

### Equipment Rentals
- `GET /api/equipment-rentals` - List rentals
- `POST /api/equipment-rentals` - Create rental
- `GET /api/equipment-rentals/{id}` - Get rental details
- `POST /api/equipment-rentals/{id}/confirm` - Confirm payment
- `POST /api/equipment-rentals/{id}/cancel` - Cancel rental

### Reviews
- `POST /api/reviews` - Create review

### Boat Locations
- `POST /api/boats/{id}/location` - Update boat location
- `GET /api/boats/{id}/location` - Get current location
- `GET /api/boats/{id}/locations` - Get location history

## Business Logic

### Booking Flow

1. **Search & Select**: Customer searches for boats, filters by location/type/price
2. **Check Availability**: System verifies boat availability for selected time
3. **Calculate Pricing**: Dynamic pricing based on booking type and duration
4. **Create Booking**: Booking created with "pending" status
5. **Payment Intent**: Stripe payment intent created
6. **Payment Confirmation**: Customer confirms payment
7. **Booking Confirmed**: Status updated to "confirmed"
8. **Trip Execution**: Captain updates status to "in_progress"
9. **Completion**: Status updated to "completed"
10. **Review**: Customer can leave review

### Pricing Calculation

- **Hourly**: `hourly_rate × duration_hours`
- **Daily**: `daily_rate × days`
- **Weekly**: `weekly_rate × weeks`
- **Tax**: 10% of subtotal
- **Service Fee**: 5% of subtotal
- **Total**: Subtotal + Tax + Service Fee

### Availability Checking

- Checks boat `is_available` flag
- Checks boat `is_verified` status
- Queries for overlapping bookings
- Excludes cancelled bookings

## Security Considerations

1. **Authentication**: Sanctum tokens, password hashing
2. **Authorization**: Role-based middleware
3. **Data Validation**: Form requests and validation rules
4. **Payment Security**: Stripe handles PCI compliance
5. **File Uploads**: Image validation and storage
6. **SQL Injection**: Eloquent ORM protection
7. **XSS**: Blade templating escapes output

## Scalability Considerations

1. **Database Indexing**: Indexes on frequently queried fields
2. **Caching**: Can implement Redis for frequently accessed data
3. **Queue System**: Background jobs for emails/notifications
4. **CDN**: Image storage can be moved to S3/CDN
5. **Load Balancing**: Stateless API design supports horizontal scaling
6. **Database Sharding**: Can partition by geographic regions

## Future Enhancements

1. **Real-time Notifications**: WebSocket integration (Pusher/Laravel Echo)
2. **Advanced Search**: Elasticsearch integration
3. **Mobile Apps**: React Native or Flutter
4. **Analytics Dashboard**: Business intelligence for captains/vendors
5. **Insurance Integration**: Marine insurance providers
6. **Weather Integration**: Weather API for trip planning
7. **Chat System**: In-app messaging between users
8. **Multi-language Support**: i18n implementation
9. **Advanced Reporting**: Financial reports, analytics
10. **API Rate Limiting**: Protect against abuse

## Deployment

### Requirements
- PHP 8.1+
- MySQL 8.0+
- Node.js 18+
- Composer
- NPM/Yarn

### Environment Variables
- Database credentials
- Stripe keys (public, secret, webhook)
- Google Maps API key (for location services)
- Pusher credentials (for real-time features)

### Setup Steps
1. Clone repository
2. Run `composer install`
3. Run `npm install`
4. Copy `.env.example` to `.env`
5. Configure database and API keys
6. Run `php artisan key:generate`
7. Run `php artisan migrate`
8. Run `php artisan db:seed`
9. Run `npm run build` or `npm run dev`
10. Start server: `php artisan serve`

## Testing Strategy

- Unit tests for services and models
- Feature tests for API endpoints
- Integration tests for payment flows
- E2E tests for critical user journeys

