<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\SeasonController;
use App\Http\Controllers\Api\FixtureController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\StandingController;
use App\Http\Controllers\Api\FixturePredictController;
use App\Http\Controllers\Api\BettingController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\LiveMatchController;

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
Route::post('verify-otp', [AuthController::class, 'verifyOTP']);
Route::post('resend-otp', [AuthController::class, 'resendOTP']);
// Thêm route quên mật khẩu
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('verify-reset-password-otp', [AuthController::class, 'verifyResetPasswordOTP']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');

Route::middleware('jwt.auth')->group(function () {
    Route::get('/areas/{id}', [AreaController::class, 'getAreaById']);
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('competitions', [CompetitionController::class, 'getAllCompetitions']);
    Route::get('competitions/{id}', [CompetitionController::class, 'getCompetitionById']);

    Route::get('/favorite/competitions', [CompetitionController::class, 'getFavouriteCompetitions']);
    Route::post('/competitions/favorite/{competitionId}', [CompetitionController::class, 'addToFavourite']);
    Route::delete('/competitions/favorite/{competitionId}', [CompetitionController::class, 'removeFromFavourite']);

    Route::get('fixtures/{id}', [FixtureController::class, 'getFixtureById']);
    Route::get('fixtures', [FixtureController::class, 'getFixtures']);
    Route::get('fixtures/competition/season', [FixtureController::class, 'getFixtureCompetition']);

    // Fixture routes
    Route::prefix('fixtures')->group(function () {
        Route::get('byRound/{competitionId}', [FixtureController::class, 'getFixturePerRound']);
        Route::get('team/{teamId}/recent', [FixtureController::class, 'getRecentFixtures']);
        Route::get('team/{teamId}/upcoming', [FixtureController::class, 'getUpcomingFixtures']);
        Route::get('recently/ai', [FixtureController::class, 'getRecentFixturesByFilters']);
        Route::get('upcoming/ai', [FixtureController::class, 'getAllUpcomingFixtures']);
        Route::get('head-to-head/{fixtureId}', [FixtureController::class, 'getHeadToHeadFixturesByFixtureId']);
        Route::get('predict/{fixtureId}', [FixturePredictController::class, 'predictMatch']);
        Route::get('lineup/{fixtureId}', [FixtureController::class, 'getLineupByFixtureId']);

        // Pin fixture routes
        Route::post('{fixtureId}/pin', [App\Http\Controllers\Api\PinnedFixtureController::class, 'pinFixture']);
        Route::delete('{fixtureId}/pin', [App\Http\Controllers\Api\PinnedFixtureController::class, 'unpinFixture']);
        Route::get('{fixtureId}/pin/status', [App\Http\Controllers\Api\PinnedFixtureController::class, 'checkPinStatus']);
        Route::get('pinned/pin', [App\Http\Controllers\Api\PinnedFixtureController::class, 'getUserPinnedFixtures']);
    });

    Route::post('teams/favorite/{teamId}', [TeamController::class, 'addFavoriteTeam']);
    Route::delete('teams/teams/{teamId}', [TeamController::class, 'removeFavoriteTeam']);
    Route::get('teams/favorite', [TeamController::class, 'getFavoriteTeams']);
    Route::get('teams', [TeamController::class, 'getTeams']);
    Route::get('teams/{teamId}', [TeamController::class, 'getTeam']);
    Route::get('teams/{teamId}/competitions/{competitionId}/stats', [TeamController::class, 'getTeamStatsByCompetition']);

    Route::get('/scrape-articles/{competitionId}', [NewsController::class, 'scrapeArticles']);
    Route::get('/news/{newsId}', [NewsController::class, 'getNewsById']);

    Route::post('news/{id}/save', [NewsController::class, 'saveNews']);
    Route::delete('news/{id}/save', [NewsController::class, 'unsaveNews']);
    Route::get('news/saved/get', [NewsController::class, 'getSavedNews']);
Route::get('/news', [NewsController::class, 'getAllNews']);


    // Comment routes
    Route::get('/news/{newsId}/comments', [CommentController::class, 'getCommentsByNews']);
    Route::post('/comments', [CommentController::class, 'createComment']);
    Route::put('/comments/{commentId}', [CommentController::class, 'updateComment']);
    Route::delete('/comments/{commentId}', [CommentController::class, 'deleteComment']);
    Route::get('/comments/{commentId}', [CommentController::class, 'getCommentById']);

    // Standing routes
    Route::post('/standings/sync', [StandingController::class, 'storeStandings']);
    Route::get('/standings', [StandingController::class, 'getStandings']);
    Route::get('/standings/matchday', [StandingController::class, 'getStandingsByMatchday']);
    Route::get('/standings/type', [StandingController::class, 'getStandingsByType']);

    // bet
    Route::post('/betting/place-bet', [BettingController::class, 'placeBet']);
    Route::get('/betting/history', [BettingController::class, 'getBettingHistory']);
    Route::post('/betting/process-results/{fixtureId}', [BettingController::class, 'processBetResults']);
    Route::get('/betting/rankings', [BettingController::class, 'getPlayerRankings']);
    // balance
    Route::post('/balance/giveAllUserBalance', [BalanceController::class, 'giveAllUserBalance']);
    Route::post('/balance/deposit', [BalanceController::class, 'deposit']);
    Route::post('/balance/withdraw', [BalanceController::class, 'withdraw']);
    Route::get('/balance', [BalanceController::class, 'getBalance']);
    Route::get('/balance/transactions', [BalanceController::class, 'getTransactionHistory']);

    //standing
    Route::post('/competitions/{competitionId}/standings', [StandingController::class, 'storeStandings']);
    Route::get('/competitions/{competitionId}/standings', [StandingController::class, 'getStandings']);
    Route::get('/competitions/{competitionId}/standings/{type}', [StandingController::class, 'getStandingsByType']);

    Route::get('notifications', [NotificationController::class, 'getNotifications']);
    Route::post('notifications/markAsRead/{id}', [NotificationController::class, 'markAsRead']);

    // Notification preferences routes
    Route::get('notifications/preferences', [NotificationController::class, 'getPreferences']);
    Route::post('notifications/preferences', [NotificationController::class, 'updatePreferences']);


    Route::post('/change-password', [AuthController::class, 'changePassword']);
});

Route::post('/competitions/sync', [CompetitionController::class, 'sync']);
Route::post('/areas/sync', [AreaController::class, 'sync']);
// Route::get('/teams/sync', [TeamController::class, 'sync']);
Route::get('/seasons/sync', [SeasonController::class, 'sync']);
Route::post('/fixtures/sync', [FixtureController::class, 'sync']);
Route::post('/fixtures/syncv2', [FixtureController::class, 'syncv2']);

Route::middleware('jwt.auth')->group(function () {
    Route::get('/profile', [AuthController::class, 'me']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    Route::delete('/profile/avatar', [AuthController::class, 'deleteAvatar']);
});

Route::get('/featured/competitions', [CompetitionController::class, 'getFeatured']);

Route::get('/syncFixturesv3', [FixtureController::class, 'syncv3']);

Route::get('/search', [SearchController::class, 'search']);

Route::get('matches/live', [LiveMatchController::class, 'getLiveMatches']);
Route::post('/query/chatbot', [FixtureController::class, 'chatbot']);
