<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Boat;
use App\Models\Equipment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@iboat.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_verified' => true,
            'is_active' => true,
        ]);

        // Create Sample Captain
        $captain = User::create([
            'name' => 'Captain John',
            'email' => 'captain@iboat.com',
            'password' => Hash::make('password'),
            'role' => 'captain',
            'phone' => '+1234567890',
            'city' => 'Miami',
            'state' => 'FL',
            'country' => 'USA',
            'is_verified' => true,
            'is_active' => true,
            'bio' => 'Experienced captain with 15 years of sailing experience.',
        ]);

        // Create Sample Boats
        Boat::create([
            'captain_id' => $captain->id,
            'name' => 'Luxury Yacht - Ocean Breeze',
            'description' => 'A beautiful 50-foot luxury yacht perfect for parties and private charters. Features include full kitchen, multiple bedrooms, and top-of-the-line navigation equipment.',
            'type' => 'yacht',
            'capacity' => 12,
            'length' => 50,
            'year' => 2020,
            'make' => 'Sunseeker',
            'model' => 'Manhattan 52',
            'hourly_rate' => 500.00,
            'daily_rate' => 4000.00,
            'weekly_rate' => 25000.00,
            'location' => 'Miami Beach Marina',
            'latitude' => 25.7907,
            'longitude' => -80.1300,
            'amenities' => ['wifi', 'kitchen', 'bathroom', 'sound_system', 'diving_equipment'],
            'images' => [],
            'is_available' => true,
            'is_verified' => true,
        ]);

        // Create Sample Customer
        User::create([
            'name' => 'Customer Jane',
            'email' => 'customer@iboat.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'phone' => '+1234567891',
            'city' => 'Miami',
            'state' => 'FL',
            'country' => 'USA',
            'is_verified' => true,
            'is_active' => true,
        ]);

        // Create Sample Vendor
        $vendor = User::create([
            'name' => 'Marine Equipment Co.',
            'email' => 'vendor@iboat.com',
            'password' => Hash::make('password'),
            'role' => 'vendor',
            'phone' => '+1234567892',
            'city' => 'Miami',
            'state' => 'FL',
            'country' => 'USA',
            'is_verified' => true,
            'is_active' => true,
            'bio' => 'Your one-stop shop for all marine equipment needs.',
        ]);

        // Create Sample Equipment
        Equipment::create([
            'vendor_id' => $vendor->id,
            'name' => 'Professional Fishing Rod Set',
            'description' => 'Complete fishing rod set with reels, tackle box, and all necessary accessories. Perfect for deep sea fishing.',
            'category' => 'fishing',
            'daily_rate' => 50.00,
            'weekly_rate' => 300.00,
            'quantity_available' => 10,
            'location' => 'Miami Beach',
            'latitude' => 25.7907,
            'longitude' => -80.1300,
            'is_available' => true,
        ]);

        Equipment::create([
            'vendor_id' => $vendor->id,
            'name' => 'GPS Navigation System',
            'description' => 'High-end GPS navigation system with chartplotter and fish finder.',
            'category' => 'navigation',
            'daily_rate' => 75.00,
            'weekly_rate' => 450.00,
            'quantity_available' => 5,
            'location' => 'Miami Beach',
            'latitude' => 25.7907,
            'longitude' => -80.1300,
            'is_available' => true,
        ]);
    }
}

