<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\{
    DashboardController,
    SurveyController
};
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

//In the last parameter the route specified which will have to be searched by slug, because the default is 'Id'
Route::get('/survey-by-slug/{survey:slug}', [SurveyController::class, 'showForGuest']);
Route::post('/survey/{survey}/answer', [SurveyController::class, 'storeAnswers']);

Route::group([
    'prefix' => 'auth',
    'middleware' => ['auth:sanctum']
], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/me', [AuthController::class, 'me']);

    Route::resource('/survey', SurveyController::class);
    Route::get('/dashboard', [DashboardController::class, 'reports']);
});
