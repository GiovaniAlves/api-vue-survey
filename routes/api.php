<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SurveyController;
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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group([
    'prefix' => 'auth',
    'middleware' => ['auth:sanctum']
], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/me', [AuthController::class, 'me']);

    Route::resource('/survey', SurveyController::class);
});
