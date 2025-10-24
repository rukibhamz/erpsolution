<?php

use App\Http\Controllers\Public\PublicEventController;
use App\Http\Controllers\Public\PublicBookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| These routes are accessible to the public without authentication.
| Used for the online booking portal and public-facing features.
|
*/

// Public Event Routes
Route::get('/', [PublicEventController::class, 'index'])->name('public.events.index');
Route::get('/events', [PublicEventController::class, 'index'])->name('public.events.index');
Route::get('/events/{event}', [PublicEventController::class, 'show'])->name('public.events.show');
Route::get('/events/calendar', [PublicEventController::class, 'calendar'])->name('public.events.calendar');

// Public Booking Routes
Route::get('/bookings/create/{event}', [PublicBookingController::class, 'create'])->name('public.bookings.create');
Route::post('/bookings', [PublicBookingController::class, 'store'])->name('public.bookings.store');
Route::get('/bookings/{booking}', [PublicBookingController::class, 'show'])->name('public.bookings.show');
Route::get('/bookings/{booking}/payment', [PublicBookingController::class, 'payment'])->name('public.bookings.payment');
Route::post('/bookings/{booking}/payment', [PublicBookingController::class, 'processPayment'])->name('public.bookings.process-payment');