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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('event_reference')->unique();
            $table->string('title');
            $table->text('description');
            $table->text('short_description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue');
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->decimal('price', 10, 2);
            $table->integer('capacity');
            $table->integer('booked_count')->default(0);
            $table->json('images')->nullable();
            $table->json('amenities')->nullable();
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_partial_payment')->default(false);
            $table->decimal('partial_payment_amount', 10, 2)->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes for performance
            $table->index(['event_reference']);
            $table->index(['status', 'is_active']);
            $table->index(['start_date']);
            $table->index(['city']);
            $table->index(['price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
