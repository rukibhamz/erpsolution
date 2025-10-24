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
        // Add indexes for properties table
        Schema::table('properties', function (Blueprint $table) {
            $table->index(['status', 'is_active'], 'idx_properties_status_active');
            $table->index(['city', 'state'], 'idx_properties_location');
            $table->index(['rent_amount'], 'idx_properties_rent_amount');
            $table->index(['property_type_id', 'status'], 'idx_properties_type_status');
        });

        // Add indexes for transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['account_id', 'status'], 'idx_transactions_account_status');
            $table->index(['transaction_type', 'status'], 'idx_transactions_type_status');
            $table->index(['transaction_date', 'status'], 'idx_transactions_date_status');
            $table->index(['created_by', 'status'], 'idx_transactions_creator_status');
            $table->index(['approved_by'], 'idx_transactions_approver');
        });

        // Add indexes for leases table
        Schema::table('leases', function (Blueprint $table) {
            $table->index(['property_id', 'status'], 'idx_leases_property_status');
            $table->index(['status', 'start_date', 'end_date'], 'idx_leases_status_dates');
            $table->index(['tenant_email'], 'idx_leases_tenant_email');
            $table->index(['start_date'], 'idx_leases_start_date');
            $table->index(['end_date'], 'idx_leases_end_date');
        });

        // Add indexes for events table
        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'is_active'], 'idx_events_status_active');
            $table->index(['start_date', 'status'], 'idx_events_date_status');
            $table->index(['city', 'status'], 'idx_events_city_status');
            $table->index(['price'], 'idx_events_price');
        });

        // Add indexes for bookings table
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['event_id', 'booking_status'], 'idx_bookings_event_status');
            $table->index(['payment_status', 'booking_status'], 'idx_bookings_payment_booking_status');
            $table->index(['customer_email'], 'idx_bookings_customer_email');
            $table->index(['booking_date'], 'idx_bookings_booking_date');
        });

        // Add indexes for users table
        Schema::table('users', function (Blueprint $table) {
            $table->index(['is_active', 'email'], 'idx_users_active_email');
            $table->index(['last_login_at'], 'idx_users_last_login');
        });

        // Add indexes for accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->index(['account_type', 'is_active'], 'idx_accounts_type_active');
            $table->index(['parent_account_id'], 'idx_accounts_parent');
        });

        // Add indexes for inventory_items table
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->index(['category_id', 'status'], 'idx_inventory_category_status');
            $table->index(['current_stock'], 'idx_inventory_stock');
            $table->index(['status', 'current_stock'], 'idx_inventory_status_stock');
            $table->index(['supplier'], 'idx_inventory_supplier');
        });

        // Add indexes for lease_payments table
        Schema::table('lease_payments', function (Blueprint $table) {
            $table->index(['lease_id', 'status'], 'idx_lease_payments_lease_status');
            $table->index(['payment_date'], 'idx_lease_payments_date');
        });

        // Add indexes for journal_entries table
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index(['status', 'entry_date'], 'idx_journal_status_date');
            $table->index(['created_by', 'status'], 'idx_journal_creator_status');
        });

        // Add indexes for journal_entry_items table
        Schema::table('journal_entry_items', function (Blueprint $table) {
            $table->index(['journal_entry_id'], 'idx_journal_items_entry');
            $table->index(['account_id'], 'idx_journal_items_account');
        });

        // Add indexes for activity_log table
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['causer_id', 'created_at'], 'idx_activity_causer_date');
            $table->index(['subject_type', 'subject_id'], 'idx_activity_subject');
            $table->index(['log_name', 'created_at'], 'idx_activity_log_name_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from properties table
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('idx_properties_status_active');
            $table->dropIndex('idx_properties_location');
            $table->dropIndex('idx_properties_rent_amount');
            $table->dropIndex('idx_properties_type_status');
        });

        // Remove indexes from transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_account_status');
            $table->dropIndex('idx_transactions_type_status');
            $table->dropIndex('idx_transactions_date_status');
            $table->dropIndex('idx_transactions_creator_status');
            $table->dropIndex('idx_transactions_approver');
        });

        // Remove indexes from leases table
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex('idx_leases_property_status');
            $table->dropIndex('idx_leases_status_dates');
            $table->dropIndex('idx_leases_tenant_email');
            $table->dropIndex('idx_leases_start_date');
            $table->dropIndex('idx_leases_end_date');
        });

        // Remove indexes from events table
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_status_active');
            $table->dropIndex('idx_events_date_status');
            $table->dropIndex('idx_events_city_status');
            $table->dropIndex('idx_events_price');
        });

        // Remove indexes from bookings table
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_event_status');
            $table->dropIndex('idx_bookings_payment_booking_status');
            $table->dropIndex('idx_bookings_customer_email');
            $table->dropIndex('idx_bookings_booking_date');
        });

        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_active_email');
            $table->dropIndex('idx_users_last_login');
        });

        // Remove indexes from accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('idx_accounts_type_active');
            $table->dropIndex('idx_accounts_parent');
        });

        // Remove indexes from inventory_items table
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex('idx_inventory_category_status');
            $table->dropIndex('idx_inventory_stock');
            $table->dropIndex('idx_inventory_status_stock');
            $table->dropIndex('idx_inventory_supplier');
        });

        // Remove indexes from lease_payments table
        Schema::table('lease_payments', function (Blueprint $table) {
            $table->dropIndex('idx_lease_payments_lease_status');
            $table->dropIndex('idx_lease_payments_date');
        });

        // Remove indexes from journal_entries table
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('idx_journal_status_date');
            $table->dropIndex('idx_journal_creator_status');
        });

        // Remove indexes from journal_entry_items table
        Schema::table('journal_entry_items', function (Blueprint $table) {
            $table->dropIndex('idx_journal_items_entry');
            $table->dropIndex('idx_journal_items_account');
        });

        // Remove indexes from activity_log table
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('idx_activity_causer_date');
            $table->dropIndex('idx_activity_subject');
            $table->dropIndex('idx_activity_log_name_date');
        });
    }
};
