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
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->string('lease_number')->unique();
            $table->foreignId('property_id')->constrained();
            $table->foreignId('tenant_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 15, 2);
            $table->decimal('deposit_amount', 15, 2);
            $table->decimal('late_fee_amount', 15, 2)->default(0);
            $table->integer('late_fee_days')->default(5);
            $table->date('rent_due_date')->nullable(); // Day of month
            $table->text('terms_and_conditions')->nullable();
            $table->json('additional_charges')->nullable(); // Utilities, maintenance, etc.
            $table->enum('status', ['active', 'expired', 'terminated', 'renewed'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->boolean('auto_renewal')->default(false);
            $table->integer('renewal_notice_days')->default(30);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
