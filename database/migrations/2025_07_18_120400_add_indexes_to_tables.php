<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->index(['category', 'status', 'published_at'], 'listings_category_status_published_idx');
            $table->index(['status', 'published_at', 'expires_at'], 'listings_status_published_expires_idx');
            $table->index(['price', 'status'], 'listings_price_status_idx');
            $table->index(['seller_profile_id', 'status', 'published_at'], 'listings_seller_status_published_idx');

            if (config('database.default') === 'mysql') {
                $table->fullText(['title', 'description'], 'listings_search_idx');
            }
        });

        Schema::table('images', function (Blueprint $table) {
            $table->index(['imageable_type', 'imageable_id', 'type', 'is_active'], 'images_morph_type_active_idx');
            $table->index(['imageable_type', 'imageable_id', 'is_primary', 'is_active'], 'images_morph_primary_active_idx');
            $table->index(['type', 'is_active', 'created_at'], 'images_type_active_created_idx');
            $table->index(['uploaded_by', 'created_at'], 'images_uploader_created_idx');
        });

        Schema::table('seller_appointments', function (Blueprint $table) {
            $table->index(['seller_profile_id', 'status', 'appointment_datetime'], 'appointments_seller_status_datetime_idx');
            $table->index(['buyer_id', 'status', 'appointment_datetime'], 'appointments_buyer_status_datetime_idx');
            $table->index(['appointment_datetime', 'status'], 'appointments_datetime_status_idx');
        });

        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->index(['is_active', 'is_verified'], 'seller_profiles_active_verified_idx');
            $table->index(['city', 'country'], 'seller_profiles_location_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role_id', 'email_verified_at'], 'users_role_verified_idx');
            $table->index(['created_at', 'role_id'], 'users_created_role_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex('listings_category_status_published_idx');
            $table->dropIndex('listings_status_published_expires_idx');
            $table->dropIndex('listings_price_status_idx');
            $table->dropIndex('listings_seller_status_published_idx');

            if (config('database.default') === 'mysql') {
                $table->dropFullText('listings_search_idx');
            }
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex('images_morph_type_active_idx');
            $table->dropIndex('images_morph_primary_active_idx');
            $table->dropIndex('images_type_active_created_idx');
            $table->dropIndex('images_uploader_created_idx');
        });

        Schema::table('seller_appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_seller_status_datetime_idx');
            $table->dropIndex('appointments_buyer_status_datetime_idx');
            $table->dropIndex('appointments_datetime_status_idx');
        });

        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropIndex('seller_profiles_active_verified_idx');
            $table->dropIndex('seller_profiles_location_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_verified_idx');
            $table->dropIndex('users_created_role_idx');
        });
    }
};