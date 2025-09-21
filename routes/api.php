<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/hello', function () {
    return response()->json(['message' => 'Hello, Laravel API!']);
});
Route::get('/invite/{qr_code}', [ApiController::class, 'getInviteByQrCode']);
