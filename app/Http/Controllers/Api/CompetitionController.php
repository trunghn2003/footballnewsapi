<?php

namespace App\Http\Controllers\Api;

use App\Services\CompetitionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\Competition;

class CompetitionController extends Controller
{
    use ApiResponseTrait;

    private CompetitionService $competitionService;

    public function __construct(CompetitionService $competitionService)
    {
        $this->competitionService = $competitionService;
    }

    /**
     * Trigger competition sync
     */
    public function sync(): JsonResponse
    {
        $result = $this->competitionService->syncCompetitions();

        if (!$result['success']) {
            return response()->json([
                'message' => 'Competition sync failed',
                'error' => $result['error']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Competitions synced successfully',
            'stats' => $result['stats']
        ]);
    }

    /**
     * get All Competition
     */
    public function getAllCompetitions(Request $request): JsonResponse
    {
        $filters = $request->only(['name', 'code', 'type', 'area_id']);
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);
        $result = $this->competitionService->getCompetitions($filters, $perPage, $page);

        return $this->successResponse($result);
    }
    public function getCompetitionById($id): JsonResponse
    {

        return  $this->successResponse($this->competitionService->getCompetitionById($id));
    }

    /**
     * Get featured competitions
     */
    public function getFeatured(): JsonResponse
    {
        $result = $this->competitionService->getFeaturedCompetitions();
        return $this->successResponse($result);
    }

    /**
     * add competition to favourite
     */
    public function addToFavourite($id): JsonResponse
    {
        $result = $this->competitionService->addToFavourite($id);
        if ($result) {
            return $this->successResponse($result);
        } else {
            return $this->errorResponse('Competition not found', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * remove competition from favourite
     */
    public function removeFromFavourite($id): JsonResponse
    {
        $result = $this->competitionService->removeFromFavourite($id);
        if ($result) {
            return $this->successResponse($result);
        } else {
            return $this->errorResponse('Competition not found', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * get favourite competitions
     */
    public function getFavouriteCompetitions(): JsonResponse
    {
        $result = $this->competitionService->getFavouriteCompetitions();
        if ($result) {
            return $this->successResponse($result);
        } else {
            return $this->errorResponse('No favourite competitions found', Response::HTTP_NOT_FOUND);
        }
    }
}
