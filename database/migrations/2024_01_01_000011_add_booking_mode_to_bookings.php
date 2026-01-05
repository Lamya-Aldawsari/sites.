<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('booking_mode', ['on_demand', 'scheduled'])->default('scheduled')->after('booking_type');
            $table->boolean('requires_captain')->default(true)->after('booking_mode');
            $table->integer('estimated_arrival_minutes')->nullable()->after('requires_captain');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_mode', 'requires_captain', 'estimated_arrival_minutes']);
        });
    }
};

