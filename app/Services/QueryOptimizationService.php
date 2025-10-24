<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Transaction;
use App\Models\Lease;
use App\Models\Event;
use App\Models\Booking;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryOptimizationService
{
    /**
     * Optimize property queries with eager loading
     */
    public function getOptimizedProperties($filters = [])
    {
        $query = Property::with([
            'propertyType:id,name,description',
            'leases' => function ($q) {
                $q->select('id', 'property_id', 'status', 'start_date', 'end_date', 'tenant_name')
                  ->where('status', 'active')
                  ->where('start_date', '<=', now())
                  ->where('end_date', '>=', now());
            }
        ]);

        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('property_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('address', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['property_type_id']) && $filters['property_type_id']) {
            $query->where('property_type_id', $filters['property_type_id']);
        }

        if (isset($filters['min_rent']) && $filters['min_rent']) {
            $query->where('rent_amount', '>=', $filters['min_rent']);
        }

        if (isset($filters['max_rent']) && $filters['max_rent']) {
            $query->where('rent_amount', '<=', $filters['max_rent']);
        }

        return $query->select([
            'id', 'name', 'property_code', 'property_type_id', 'address', 'city', 'state',
            'rent_amount', 'deposit_amount', 'bedrooms', 'bathrooms', 'status', 'is_active',
            'created_at', 'updated_at'
        ]);
    }

    /**
     * Optimize transaction queries with eager loading
     */
    public function getOptimizedTransactions($filters = [])
    {
        $query = Transaction::with([
            'account:id,account_name,account_type',
            'createdBy:id,name,email',
            'approvedBy:id,name,email'
        ]);

        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('transaction_reference', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('reference_number', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['transaction_type']) && $filters['transaction_type']) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['account_id']) && $filters['account_id']) {
            $query->where('account_id', $filters['account_id']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        return $query->select([
            'id', 'transaction_reference', 'account_id', 'transaction_type', 'amount',
            'description', 'transaction_date', 'status', 'created_by', 'approved_by',
            'approved_at', 'created_at', 'updated_at'
        ]);
    }

    /**
     * Optimize lease queries with eager loading
     */
    public function getOptimizedLeases($filters = [])
    {
        $query = Lease::with([
            'property:id,name,property_code,address,city,state,rent_amount',
            'payments' => function ($q) {
                $q->select('id', 'lease_id', 'amount', 'payment_date', 'status')
                  ->latest('payment_date');
            }
        ]);

        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('lease_reference', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('tenant_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('tenant_email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['property_id']) && $filters['property_id']) {
            $query->where('property_id', $filters['property_id']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('start_date', '<=', $filters['date_to']);
        }

        return $query->select([
            'id', 'lease_reference', 'property_id', 'tenant_name', 'tenant_email',
            'start_date', 'end_date', 'monthly_rent', 'security_deposit', 'status',
            'created_at', 'updated_at'
        ]);
    }

    /**
     * Optimize event queries with eager loading
     */
    public function getOptimizedEvents($filters = [])
    {
        $query = Event::with([
            'bookings' => function ($q) {
                $q->select('id', 'event_id', 'booking_status', 'payment_status', 'ticket_quantity')
                  ->where('booking_status', 'confirmed');
            }
        ]);

        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('event_reference', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('venue', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['city']) && $filters['city']) {
            $query->where('city', $filters['city']);
        }

        if (isset($filters['min_price']) && $filters['min_price']) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price']) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('start_date', '<=', $filters['date_to']);
        }

        return $query->select([
            'id', 'event_reference', 'title', 'start_date', 'end_date', 'venue',
            'city', 'price', 'capacity', 'booked_count', 'status', 'is_active',
            'created_at', 'updated_at'
        ]);
    }

    /**
     * Optimize booking queries with eager loading
     */
    public function getOptimizedBookings($filters = [])
    {
        $query = Booking::with([
            'event:id,title,start_date,venue,price'
        ]);

        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('booking_reference', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('customer_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('customer_email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['booking_status']) && $filters['booking_status']) {
            $query->where('booking_status', $filters['booking_status']);
        }

        if (isset($filters['payment_status']) && $filters['payment_status']) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['event_id']) && $filters['event_id']) {
            $query->where('event_id', $filters['event_id']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('booking_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('booking_date', '<=', $filters['date_to']);
        }

        return $query->select([
            'id', 'booking_reference', 'event_id', 'customer_name', 'customer_email',
            'ticket_quantity', 'total_amount', 'paid_amount', 'balance_amount',
            'payment_status', 'booking_status', 'booking_date', 'created_at', 'updated_at'
        ]);
    }

    /**
     * Get dashboard statistics with optimized queries
     */
    public function getDashboardStats()
    {
        return [
            'total_properties' => Property::count(),
            'available_properties' => Property::where('status', 'available')->where('is_active', true)->count(),
            'occupied_properties' => Property::where('status', 'occupied')->count(),
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_transactions' => Transaction::count(),
            'pending_transactions' => Transaction::where('status', 'pending')->count(),
            'total_events' => Event::count(),
            'upcoming_events' => Event::where('start_date', '>', now())->count(),
            'active_leases' => Lease::where('status', 'active')->count(),
        ];
    }

    /**
     * Get recent activities with optimized query
     */
    public function getRecentActivities($limit = 10)
    {
        return DB::table('activity_log')
            ->join('users', 'activity_log.causer_id', '=', 'users.id')
            ->select(
                'activity_log.id',
                'activity_log.description',
                'activity_log.subject_type',
                'activity_log.subject_id',
                'activity_log.created_at',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->orderBy('activity_log.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get monthly revenue data with optimized query
     */
    public function getMonthlyRevenue($year = null)
    {
        $year = $year ?? now()->year;

        return Transaction::where('transaction_type', 'income')
            ->where('status', 'approved')
            ->whereYear('transaction_date', $year)
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get property status distribution with optimized query
     */
    public function getPropertyStatusDistribution()
    {
        return Property::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }
}
