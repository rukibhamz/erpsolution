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
            $table->string('title');
            $table->text('description');
            $table->foreignId('category_id')->constrained('event_categories');
            $table->string('venue');
            $table->text('venue_address');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->integer('max_attendees');
            $table->decimal('price_per_person', 15, 2);
            $table->decimal('deposit_amount', 15, 2)->default(0);
            $table->integer('deposit_percentage')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->json('images')->nullable();
            $table->json('amenities')->nullable();
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->boolean('is_public')->default(true);
            $table->boolean('allow_partial_payment')->default(true);
            $table->timestamps();
            $table->softDeletes();
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
