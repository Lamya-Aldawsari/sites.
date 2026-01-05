<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captain_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->enum('type', ['yacht', 'sailboat', 'speedboat', 'fishing_boat', 'catamaran', 'houseboat', 'other'])->default('other');
            $table->integer('capacity')->default(1); // Number of passengers
            $table->integer('length')->nullable(); // in feet
            $table->integer('year')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('weekly_rate', 10, 2)->nullable();
            $table->string('location')->nullable(); // Current location
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('amenities')->nullable(); // ['wifi', 'kitchen', 'bathroom', etc.]
            $table->json('images')->nullable(); // Array of image URLs
            $table->boolean('is_available')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boats');
    }
};

