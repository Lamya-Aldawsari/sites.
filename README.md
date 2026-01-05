# iBoat - Marine Transport & Rental Platform

A comprehensive platform connecting boat owners (Captains), marine equipment vendors, and customers - an Uber/Airbnb hybrid for boats.

## Features

- **Multi-role Authentication**: Captains, Customers, Vendors, and Admins
- **Boat Management**: List, search, and manage boats with availability calendar
- **Booking System**: Real-time booking with payment processing
- **Equipment Rental**: Marine equipment marketplace
- **Location Tracking**: Real-time boat location tracking
- **Rating & Reviews**: Review system for boats, captains, and equipment
- **Payment Processing**: Integrated Stripe payment gateway
- **Notifications**: Real-time notifications for bookings and updates

## Tech Stack

- **Backend**: Laravel 10
- **Frontend**: React 18 with Inertia.js
- **Database**: MySQL
- **Authentication**: Laravel Sanctum & Passport
- **Payments**: Stripe
- **Real-time**: Laravel Broadcasting (Pusher/WebSockets)

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Copy `.env.example` to `.env` and configure
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations:
   ```bash
   php artisan migrate
   ```
6. Seed database:
   ```bash
   php artisan db:seed
   ```
7. Start development server:
   ```bash
   php artisan serve
   npm run dev
   ```

## Project Structure

```
app/
├── Models/          # Eloquent models
├── Http/
│   ├── Controllers/ # API & Web controllers
│   ├── Middleware/  # Custom middleware
│   └── Requests/    # Form requests
├── Services/        # Business logic services
└── Events/          # Event classes

database/
├── migrations/      # Database migrations
└── seeders/         # Database seeders
```

## Roles

- **Captain**: Boat owners who can list and manage their boats
- **Customer**: Users who can book boats and rent equipment
- **Vendor**: Marine equipment suppliers
- **Admin**: Platform administrators

## API Documentation

API endpoints are available at `/api/documentation` (when implemented)

## License

MIT

