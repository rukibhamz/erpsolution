<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Event;
use App\Models\Lease;
use App\Services\QueryOptimizationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * PERFORMANCE FIX: Display the dashboard with optimized queries
     */
    public function index(): View
    {
        $optimizationService = new QueryOptimizationService();
        
        // Get dashboard statistics with optimized queries
        $stats = $optimizationService->getDashboardStats();

        // Get recent transactions with optimized query
        $recent_transactions = Transaction::with(['account:id,account_name', 'createdBy:id,name'])
            ->select('id', 'transaction_reference', 'account_id', 'amount', 'transaction_type', 'description', 'transaction_date', 'created_by')
            ->latest()
            ->limit(10)
            ->get();

        // Get recent activities with optimized query
        $recent_activities = $optimizationService->getRecentActivities(10);

        // Get monthly revenue data with optimized query
        $monthly_revenue = $optimizationService->getMonthlyRevenue();

        // Get property status distribution with optimized query
        $property_status = $optimizationService->getPropertyStatusDistribution();

        return view('admin.dashboard', compact(
            'stats',
            'recent_transactions',
            'recent_activities',
            'monthly_revenue',
            'property_status'
        ));
    }
}