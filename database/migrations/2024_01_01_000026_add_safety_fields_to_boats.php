<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->string('safety_certificate_number')->nullable()->after('is_verified');
            $table->date('safety_certificate_expiry')->nullable()->after('safety_certificate_number');
            $table->boolean('safety_certificate_verified')->default(false)->after('safety_certificate_expiry');
            $table->json('verified_photos')->nullable()->after('safety_certificate_verified')->comment('Verified photos with timestamps');
            $table->date('last_safety_inspection')->nullable()->after('verified_photos');
            $table->integer('safety_rating')->default(0)->after('last_safety_inspection')->comment('Safety rating out of 100');
        });
    }

    public function down(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->dropColumn([
                'safety_certificate_number',
                'safety_certificate_expiry',
                'safety_certificate_verified',
                'verified_photos',
                'last_safety_inspection',
                'safety_rating',
            ]);
        });
    }
};

