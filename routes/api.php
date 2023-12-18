<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InterfaceController;
use App\Http\Controllers\UssdController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/ussd/receiver', [InterfaceController::class, 'index']);
Route::post('/ussd', [UssdController::class, 'ussdRequestHandler']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
