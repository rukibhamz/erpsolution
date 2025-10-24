<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Property\PropertyController;
use App\Http\Controllers\Accounting\TransactionController;
use Illuminate\Support\Facades\Route;

// Include public routes
require __DIR__.'/public.php';

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

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Property Management Routes
    Route::middleware('can:view-properties')->group(function () {
        Route::resource('admin/properties', PropertyController::class)->names([
            'index' => 'admin.properties.index',
            'create' => 'admin.properties.create',
            'store' => 'admin.properties.store',
            'show' => 'admin.properties.show',
            'edit' => 'admin.properties.edit',
            'update' => 'admin.properties.update',
            'destroy' => 'admin.properties.destroy',
        ]);
        
        // SECURITY FIX: Add CSRF protection to AJAX routes
        Route::middleware('csrf')->group(function () {
            Route::patch('admin/properties/{property}/toggle-status', [PropertyController::class, 'toggleStatus'])->name('admin.properties.toggle-status');
            Route::delete('admin/properties/{property}/remove-image', [PropertyController::class, 'removeImage'])->name('admin.properties.remove-image');
            Route::post('admin/properties/fix-status-inconsistencies', [PropertyController::class, 'fixStatusInconsistencies'])->name('admin.properties.fix-status-inconsistencies');
        });
    });

    // Transaction Management Routes
    Route::middleware('can:view-transactions')->group(function () {
        Route::resource('admin/transactions', TransactionController::class)->names([
            'index' => 'admin.transactions.index',
            'create' => 'admin.transactions.create',
            'store' => 'admin.transactions.store',
            'show' => 'admin.transactions.show',
            'edit' => 'admin.transactions.edit',
            'update' => 'admin.transactions.update',
            'destroy' => 'admin.transactions.destroy',
        ]);
        
        // SECURITY FIX: Add CSRF protection to transaction action routes
        Route::middleware('csrf')->group(function () {
            Route::patch('admin/transactions/{transaction}/approve', [TransactionController::class, 'approve'])->name('admin.transactions.approve');
            Route::patch('admin/transactions/{transaction}/reject', [TransactionController::class, 'reject'])->name('admin.transactions.reject');
            Route::patch('admin/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('admin.transactions.cancel');
        });
    });

    // User Management Routes
    Route::middleware('can:view-users')->group(function () {
        Route::resource('admin/users', UserController::class)->names([
            'index' => 'admin.users.index',
            'create' => 'admin.users.create',
            'store' => 'admin.users.store',
            'show' => 'admin.users.show',
            'edit' => 'admin.users.edit',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);
    });
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});