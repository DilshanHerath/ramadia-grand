<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/generate-qr-first-ten', [TicketController::class, 'generateFirstTen']);
Route::get('/generate-first-ten-tickets', [TicketController::class, 'generateFirstTenTickets']);

Route::get('/generate-all-tickets', [TicketController::class, 'generateAllTickets']);


Route::get('/qr-scanner', [TicketController::class, 'scannerPage']);
Route::post('/qr-verify', [TicketController::class, 'verifyQr'])->name('qr.verify');

// Route::get('/invite/{qr_code}', [ApiController::class, 'getInviteByQrCode']);
Route::get('/invite/{qr_code}', [TicketController::class, 'getInviteByQrCode']);
