<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware(['role:admin|manager'])->group(function () {
        // User Management
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        
        // Property Management
        Route::resource('properties', \App\Http\Controllers\Property\PropertyController::class);
        Route::patch('properties/{property}/toggle-status', [\App\Http\Controllers\Property\PropertyController::class, 'toggleStatus'])->name('properties.toggle-status');
        Route::delete('properties/{property}/remove-image', [\App\Http\Controllers\Property\PropertyController::class, 'removeImage'])->name('properties.remove-image');
        
        // Tenant Management
        Route::resource('tenants', \App\Http\Controllers\Property\TenantController::class);
        Route::patch('tenants/{tenant}/toggle-status', [\App\Http\Controllers\Property\TenantController::class, 'toggleStatus'])->name('tenants.toggle-status');
        
        // Lease Management
        Route::resource('leases', \App\Http\Controllers\Property\LeaseController::class);
        Route::patch('leases/{lease}/terminate', [\App\Http\Controllers\Property\LeaseController::class, 'terminate'])->name('leases.terminate');
        Route::patch('leases/{lease}/renew', [\App\Http\Controllers\Property\LeaseController::class, 'renew'])->name('leases.renew');
        
        // Booking Management (to be implemented)
        Route::get('bookings', function () {
            return view('admin.bookings.index');
        })->name('bookings.index');
        
        // Accounting (to be implemented)
        Route::get('accounting', function () {
            return view('admin.accounting.index');
        })->name('accounting.index');
    });
});
