<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('boat_id')->constrained('boats')->onDelete('cascade');
            $table->foreignId('captain_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->datetime('trip_started_at');
            $table->datetime('trip_ended_at')->nullable();
            $table->decimal('start_latitude', 10, 8);
            $table->decimal('start_longitude', 11, 8);
            $table->decimal('end_latitude', 10, 8)->nullable();
            $table->decimal('end_longitude', 11, 8)->nullable();
            $table->decimal('total_distance_nm', 10, 2)->default(0)->comment('Total distance in nautical miles');
            $table->integer('max_speed_knots')->nullable();
            $table->integer('average_speed_knots')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled', 'emergency'])->default('active');
            $table->json('route_data')->nullable()->comment('Array of GPS coordinates for route tracking');
            $table->json('safety_checkpoints')->nullable()->comment('Safety checkpoints during trip');
            $table->timestamps();
            
            $table->index(['booking_id', 'status']);
            $table->index(['boat_id', 'status']);
            $table->index(['captain_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_logs');
    }
};

