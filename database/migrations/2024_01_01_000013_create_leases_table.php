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
            $table->string('lease_reference')->unique();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('tenant_name');
            $table->string('tenant_email');
            $table->string('tenant_phone');
            $table->text('tenant_address');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('security_deposit', 10, 2);
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->integer('grace_period_days')->default(5);
            $table->enum('status', ['draft', 'active', 'expired', 'terminated', 'cancelled'])->default('draft');
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes for performance
            $table->index(['lease_reference']);
            $table->index(['property_id']);
            $table->index(['tenant_email']);
            $table->index(['status']);
            $table->index(['start_date', 'end_date']);
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
