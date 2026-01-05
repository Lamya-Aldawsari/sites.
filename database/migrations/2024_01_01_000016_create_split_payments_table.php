<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('split_payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('paymentable'); // Can be booking or order
            $table->decimal('total_amount', 10, 2);
            $table->decimal('platform_fee', 10, 2);
            $table->decimal('vendor_amount', 10, 2);
            $table->decimal('captain_amount', 10, 2)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('platform_transfer_id')->nullable();
            $table->string('vendor_transfer_id')->nullable();
            $table->string('captain_transfer_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_payments');
    }
};

