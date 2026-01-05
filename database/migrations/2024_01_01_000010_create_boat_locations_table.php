<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boat_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boat_id')->constrained('boats')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('speed', 5, 2)->nullable(); // knots
            $table->decimal('heading', 5, 2)->nullable(); // degrees
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['boat_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boat_locations');
    }
};

