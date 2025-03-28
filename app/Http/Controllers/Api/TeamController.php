<?php

namespace App\Http\Controllers\Api;

use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

class TeamController extends Controller
{
    private TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Trigger competition sync
     */
    public function sync(): JsonResponse
    {
        $result = $this->teamService->syncTeamsAndPlayers();

        if (!$result) {
            return response()->json([
                'message' => 'Team sync failed',
                
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Competitions synced successfully',
         
        ]);
    }

}
