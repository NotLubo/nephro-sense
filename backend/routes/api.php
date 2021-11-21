<?php

use App\Http\Controllers\ArduinoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/token', [ArduinoController::class, 'getToken']);
Route::post('/property-data', [ArduinoController::class, 'getPropertyData']);
Route::post('/all-properties', [ArduinoController::class, 'getThingProperties']);
Route::post('/things', [ArduinoController::class, 'getThings']);

Route::get('/all', [ArduinoController::class, 'getAllData']);
