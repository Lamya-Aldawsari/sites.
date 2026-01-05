<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('years_experience')->nullable()->after('bio');
            $table->string('license_number')->nullable()->after('years_experience');
            $table->date('license_expiry_date')->nullable()->after('license_number');
            $table->boolean('license_verified')->default(false)->after('license_expiry_date');
            $table->json('certifications')->nullable()->after('license_verified')->comment('Array of certifications');
            $table->json('emergency_contacts')->nullable()->after('certifications');
            $table->decimal('captain_rating', 3, 2)->default(0.00)->after('emergency_contacts');
            $table->integer('captain_total_reviews')->default(0)->after('captain_rating');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'years_experience',
                'license_number',
                'license_expiry_date',
                'license_verified',
                'certifications',
                'emergency_contacts',
                'captain_rating',
                'captain_total_reviews',
            ]);
        });
    }
};

