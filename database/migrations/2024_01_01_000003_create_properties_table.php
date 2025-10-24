<?php

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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('property_code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('property_type_id')->constrained()->onDelete('restrict');
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('rent_amount', 15, 2);
            $table->decimal('deposit_amount', 15, 2)->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->decimal('area_sqft', 10, 2)->nullable();
            $table->json('amenities')->nullable();
            $table->json('images')->nullable();
            $table->enum('status', ['available', 'occupied', 'maintenance', 'unavailable'])->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes for performance
            $table->index(['status', 'is_active']);
            $table->index(['property_type_id']);
            $table->index(['city']);
            $table->index(['property_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
