<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Event;
use App\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        // Get dashboard statistics
        $stats = [
            'total_properties' => Property::count(),
            'available_properties' => Property::available()->count(),
            'occupied_properties' => Property::occupied()->count(),
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'total_transactions' => Transaction::count(),
            'pending_transactions' => Transaction::pending()->count(),
            'total_events' => Event::count(),
            'upcoming_events' => Event::where('start_date', '>', now())->count(),
            'active_leases' => Lease::where('status', 'active')->count(),
        ];

        // Get recent transactions
        $recent_transactions = Transaction::with(['account', 'createdBy'])
            ->latest()
            ->limit(10)
            ->get();

        // Get recent activities
        $recent_activities = DB::table('activity_log')
            ->join('users', 'activity_log.causer_id', '=', 'users.id')
            ->select('activity_log.*', 'users.name as user_name')
            ->latest('activity_log.created_at')
            ->limit(10)
            ->get();

        // Get monthly revenue data
        $monthly_revenue = Transaction::income()
            ->approved()
            ->whereYear('transaction_date', now()->year)
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Get property status distribution
        $property_status = Property::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recent_transactions',
            'recent_activities',
            'monthly_revenue',
            'property_status'
        ));
    }
}