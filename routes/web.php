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
        
        // Event Management
        Route::resource('events', \App\Http\Controllers\Booking\EventController::class);
        Route::get('events/{event}/calendar', [\App\Http\Controllers\Booking\EventController::class, 'calendar'])->name('events.calendar');
        Route::patch('events/{event}/toggle-status', [\App\Http\Controllers\Booking\EventController::class, 'toggleStatus'])->name('events.toggle-status');
        Route::delete('events/{event}/remove-image', [\App\Http\Controllers\Booking\EventController::class, 'removeImage'])->name('events.remove-image');
        
        // Booking Management
        Route::resource('bookings', \App\Http\Controllers\Booking\BookingController::class);
        Route::patch('bookings/{booking}/confirm', [\App\Http\Controllers\Booking\BookingController::class, 'confirm'])->name('bookings.confirm');
        Route::patch('bookings/{booking}/cancel', [\App\Http\Controllers\Booking\BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::patch('bookings/{booking}/complete', [\App\Http\Controllers\Booking\BookingController::class, 'complete'])->name('bookings.complete');
        
        // Payment Management
        Route::resource('payments', \App\Http\Controllers\Booking\PaymentController::class);
        Route::patch('payments/{payment}/mark-completed', [\App\Http\Controllers\Booking\PaymentController::class, 'markCompleted'])->name('payments.mark-completed');
        Route::patch('payments/{payment}/mark-failed', [\App\Http\Controllers\Booking\PaymentController::class, 'markFailed'])->name('payments.mark-failed');
        
        // Accounting System
        Route::resource('accounts', \App\Http\Controllers\Accounting\AccountController::class);
        Route::patch('accounts/{account}/toggle-status', [\App\Http\Controllers\Accounting\AccountController::class, 'toggleStatus'])->name('accounts.toggle-status');
        Route::patch('accounts/{account}/update-balance', [\App\Http\Controllers\Accounting\AccountController::class, 'updateBalance'])->name('accounts.update-balance');
        
        Route::resource('transactions', \App\Http\Controllers\Accounting\TransactionController::class);
        Route::patch('transactions/{transaction}/approve', [\App\Http\Controllers\Accounting\TransactionController::class, 'approve'])->name('transactions.approve');
        Route::patch('transactions/{transaction}/reject', [\App\Http\Controllers\Accounting\TransactionController::class, 'reject'])->name('transactions.reject');
        Route::patch('transactions/{transaction}/cancel', [\App\Http\Controllers\Accounting\TransactionController::class, 'cancel'])->name('transactions.cancel');
        
        Route::resource('journal-entries', \App\Http\Controllers\Accounting\JournalEntryController::class);
        Route::patch('journal-entries/{journalEntry}/approve', [\App\Http\Controllers\Accounting\JournalEntryController::class, 'approve'])->name('journal-entries.approve');
        Route::patch('journal-entries/{journalEntry}/reject', [\App\Http\Controllers\Accounting\JournalEntryController::class, 'reject'])->name('journal-entries.reject');
        Route::patch('journal-entries/{journalEntry}/cancel', [\App\Http\Controllers\Accounting\JournalEntryController::class, 'cancel'])->name('journal-entries.cancel');
        
        // Inventory Management
        Route::resource('inventory', \App\Http\Controllers\Inventory\InventoryController::class);
        Route::patch('inventory/{inventoryItem}/toggle-status', [\App\Http\Controllers\Inventory\InventoryController::class, 'toggleStatus'])->name('inventory.toggle-status');
        Route::post('inventory/{inventoryItem}/add-stock', [\App\Http\Controllers\Inventory\InventoryController::class, 'addStock'])->name('inventory.add-stock');
        Route::post('inventory/{inventoryItem}/remove-stock', [\App\Http\Controllers\Inventory\InventoryController::class, 'removeStock'])->name('inventory.remove-stock');
        
        Route::resource('repairs', \App\Http\Controllers\Inventory\RepairController::class);
        Route::patch('repairs/{repair}/mark-in-progress', [\App\Http\Controllers\Inventory\RepairController::class, 'markInProgress'])->name('repairs.mark-in-progress');
        Route::patch('repairs/{repair}/mark-completed', [\App\Http\Controllers\Inventory\RepairController::class, 'markCompleted'])->name('repairs.mark-completed');
        Route::patch('repairs/{repair}/mark-cancelled', [\App\Http\Controllers\Inventory\RepairController::class, 'markCancelled'])->name('repairs.mark-cancelled');
        
        Route::resource('maintenance', \App\Http\Controllers\Inventory\MaintenanceController::class);
    });
});
