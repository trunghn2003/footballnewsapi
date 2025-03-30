<?php

namespace App\Http\Controllers\Api;

use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use PHPUnit\Framework\MockObject\Api;

class TeamController extends Controller
{
    private TeamService $teamService;
    use ApiResponseTrait;

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

    public function addFavoriteTeam(int $teamId): JsonResponse
    {
        $result = $this->teamService->addFavoriteTeam($teamId);
        // dd($result);
        if (!$result) {
            return $this->errorResponse('Failed to add favorite team');
        }
        return $this->successResponse('Favorite team added successfully');

    }

    public function removeFavoriteTeam(int $teamId): JsonResponse
    {
        $result = $this->teamService->removeFavoriteTeam($teamId);
        if (!$result) {
            return $this->errorResponse('Failed to remove favorite team');
        }
        return $this->successResponse('Favorite team removed successfully');
    }

}
