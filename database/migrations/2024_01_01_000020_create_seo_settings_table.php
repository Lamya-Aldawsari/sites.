<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_type'); // 'home', 'boats', 'equipment', 'about', etc.
            $table->string('page_identifier')->nullable(); // Specific page ID if needed
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->text('canonical_url')->nullable();
            $table->text('robots')->nullable(); // noindex, nofollow, etc.
            $table->text('structured_data')->nullable(); // JSON-LD structured data
            $table->timestamps();
            
            $table->unique(['page_type', 'page_identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};

