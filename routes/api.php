<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\SeasonController;
use App\Http\Controllers\Api\FixtureController;
use App\Http\Controllers\Api\AuthController;
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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('jwt.auth')->group(function () {
    Route::get('/areas/{id}', [AreaController::class, 'getAreaById']);
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('competitions', [CompetitionController::class, 'getAllCompetitions']);
    Route::get('competitions/{id}', [CompetitionController::class, 'getCompetitionById']);
});

Route::get('/competitions/sync', [CompetitionController::class, 'sync']);
Route::get('/areas/sync', [AreaController::class, 'sync']);
Route::get('/teams/sync', [TeamController::class, 'sync']);
Route::get('/seasons/sync', [SeasonController::class, 'sync']);
Route::get('/fixtures/sync', [FixtureController::class, 'sync']);
