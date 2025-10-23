<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Lease;
use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        // Get dashboard statistics
        $stats = [
            'total_properties' => Property::count(),
            'available_properties' => Property::available()->count(),
            'occupied_properties' => Property::occupied()->count(),
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::withActiveLeases()->count(),
            'total_leases' => Lease::count(),
            'active_leases' => Lease::active()->count(),
            'expiring_leases' => Lease::expiring(30)->count(),
            'total_events' => Event::count(),
            'upcoming_events' => Event::where('start_date', '>', now())->count(),
            'total_bookings' => EventBooking::count(),
            'pending_bookings' => EventBooking::where('booking_status', 'pending')->count(),
        ];

        // Get recent activities
        $recentActivities = \Spatie\Activitylog\Models\Activity::with('causer')
            ->latest()
            ->limit(10)
            ->get();

        // Get expiring leases
        $expiringLeases = Lease::with(['property', 'tenant'])
            ->expiring(30)
            ->get();

        // Get upcoming events
        $upcomingEvents = Event::with('category')
            ->where('start_date', '>', now())
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        // Get recent bookings
        $recentBookings = EventBooking::with('event')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentActivities',
            'expiringLeases',
            'upcomingEvents',
            'recentBookings'
        ));
    }
}
