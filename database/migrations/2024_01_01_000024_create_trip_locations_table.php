<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_log_id')->constrained('trip_logs')->onDelete('cascade');
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('speed_knots', 5, 2)->nullable();
            $table->decimal('heading_degrees', 5, 2)->nullable();
            $table->decimal('altitude_meters', 8, 2)->nullable();
            $table->integer('accuracy_meters')->nullable();
            $table->decimal('distance_from_start_nm', 10, 2)->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['trip_log_id', 'recorded_at']);
            $table->index(['booking_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_locations');
    }
};

