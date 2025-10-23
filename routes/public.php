<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\PublicEventController;
use App\Http\Controllers\Public\PublicBookingController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| These routes are accessible to the public without authentication.
| They handle the public-facing booking portal.
|
*/

// Public Event Routes
Route::prefix('public')->name('public.')->group(function () {
    // Event browsing
    Route::get('/events', [PublicEventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [PublicEventController::class, 'show'])->name('events.show');
    Route::get('/events-calendar', [PublicEventController::class, 'calendar'])->name('events.calendar');
    Route::get('/events-calendar-data', [PublicEventController::class, 'calendarData'])->name('events.calendar-data');
    
    // Booking routes
    Route::get('/events/{event}/book', [PublicBookingController::class, 'create'])->name('bookings.create');
    Route::post('/events/{event}/book', [PublicBookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [PublicBookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/payment', [PublicBookingController::class, 'payment'])->name('bookings.payment');
    Route::post('/bookings/{booking}/payment', [PublicBookingController::class, 'processPayment'])->name('bookings.process-payment');
    Route::patch('/bookings/{booking}/cancel', [PublicBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/bookings/{booking}/confirm', [PublicBookingController::class, 'confirm'])->name('bookings.confirm');
});

// Redirect root to public events
Route::get('/', function () {
    return redirect()->route('public.events.index');
});
