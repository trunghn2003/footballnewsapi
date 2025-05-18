<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StandingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class StandingController extends Controller
{
    use ApiResponseTrait;

    protected $standingService;

    public function __construct(StandingService $standingService)
    {
        $this->standingService = $standingService;
    }

    public function storeStandings(Request $request)
    {
        try {
            $this->standingService->storeStandingsFromApi($request->all());
            return $this->successResponse(['message' => 'Standings data stored successfully']);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getStandings(Request $request)
    {
        try {
            $competitionId = $request->competition_id;
            $name = $request->name;
            if (!$competitionId && !$name) {
                return $this->errorResponse('Competition ID or Name are required');
                return $this->errorResponse('Competition ID or name is required');
            }

            $standings = $this->standingService->getStandingsByCompetitionAndSeason($request);
            return $this->successResponse($standings);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getStandingsByMatchday(Request $request)
    {
        try {
            $competitionId = $request->query('competition_id');
            $seasonId = $request->query('season_id');
            $matchday = $request->query('matchday');

            if (!$competitionId || !$seasonId || !$matchday) {
                return $this->errorResponse('Competition ID, Season ID and Matchday are required');
            }

            $standings = $this->standingService->getStandingsByMatchday($competitionId, $seasonId, $matchday);
            return $this->successResponse($standings);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getStandingsByType(Request $request)
    {
        try {
            $competitionId = $request->query('competition_id');
            $seasonId = $request->query('season_id');
            $matchday = $request->query('matchday');
            $type = $request->query('type');

            if (!$competitionId || !$seasonId || !$matchday || !$type) {
                return $this->errorResponse('Competition ID, Season ID, Matchday and Type are required');
            }

            $standings = $this->standingService->getStandingsByType($competitionId, $seasonId, $matchday, $type);
            return $this->successResponse($standings);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
