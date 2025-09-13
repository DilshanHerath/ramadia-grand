<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/generate-qr-first-ten', [TicketController::class, 'generateFirstTen']);
Route::get('/generate-first-ten-tickets', [TicketController::class, 'generateFirstTenTickets']);

Route::get('/generate-all-tickets', [TicketController::class, 'generateAllTickets']);
