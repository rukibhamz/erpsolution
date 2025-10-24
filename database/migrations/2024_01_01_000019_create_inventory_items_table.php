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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->decimal('unit_price', 10, 2);
            $table->integer('current_stock')->default(0);
            $table->integer('initial_stock')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->string('supplier')->nullable();
            $table->string('supplier_contact')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes for performance
            $table->index(['item_code']);
            $table->index(['category_id']);
            $table->index(['status']);
            $table->index(['current_stock']);
            $table->index(['supplier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
