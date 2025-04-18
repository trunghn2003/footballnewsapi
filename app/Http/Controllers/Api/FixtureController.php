<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FixtureService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class FixtureController extends Controller
{
    use ApiResponseTrait;
    private FixtureService $fixtureService;

    public function __construct(FixtureService $fixtureService)
    {
        $this->fixtureService = $fixtureService;
    }

    public function sync(): JsonResponse
    {
        $result = $this->fixtureService->syncFixtures();
        if (!$result['success']) {
            return response()->json([
                'message' => 'Fixture sync failed',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return response()->json([
            'message' => 'Fixture sync successfully',

        ], Response::HTTP_OK);
    }

    public function getFixtureById(int $id)
    {
        $fixture = $this->fixtureService->getFixtureById($id);
        return $this->successResponse($fixture);
    }

    public function getLineupByFixtureId(int $id)
    {
        $fixture = $this->fixtureService->getLineupByFixtureId($id);
        return $this->successResponse($fixture);
    }

    public function getFixtures(Request $request)
    {
        $filters = $request->only(['competition', 'ids', 'dateFrom', 'dateTo', 'status', 'teamName', 'teamId', 'competition_id']);
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);

        $fixtures = $this->fixtureService->getFixtures($filters, $perPage, $page);
        return $this->successResponse($fixtures);
    }

    public function getFixtureCompetition(Request $request)
    {
        $filters = $request->only(['dateFrom', 'dateTo', 'competition']);
        $fixtures = $this->fixtureService->getFixtureByCompetition($filters);
        return $this->successResponse($fixtures);
    }

    /**
     * Get recent fixtures for a team
     *
     * @param Request $request
     * @param int $teamId
     * @return JsonResponse
     */
    public function getRecentFixtures(Request $request, int $teamId): JsonResponse
    {
        try {
            $limit = $request->input('limit', 5);
            $result = $this->fixtureService->getRecentFixturesByTeam($teamId, $limit);
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get upcoming fixtures for a team
     *
     * @param Request $request
     * @param int $teamId
     * @return JsonResponse
     */
    public function getUpcomingFixtures(Request $request, int $teamId): JsonResponse
    {
        try {
            $filter = $request->only(['competition', 'dateFrom', 'dateTo', 'status',
                'teamName', 'teamId', 'competition_id', 'limit']);
            $result = $this->fixtureService->getUpcomingFixturesByTeam($teamId, $filter);
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy lịch sử đối đầu giữa hai đội bóng dựa trên ID trận đấu
     *
     * @param Request $request
     * @param int $fixtureId ID của trận đấu
     * @return JsonResponse
     */
    public function getHeadToHeadFixturesByFixtureId(Request $request, int $fixtureId): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $result = $this->fixtureService->getHeadToHeadFixturesByFixtureId($fixtureId, $limit);
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function syncv2(): JsonResponse
    {
        $result = $this->fixtureService->syncFixturesv2();
        if (!$result['success']) {
            return response()->json([
                'message' => 'Fixture sync failed',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return response()->json([
            'message' => 'Fixture sync successfully',

        ], Response::HTTP_OK);
    }

    public function syncv3()
    {
        $result =  $this->fixtureService->fetchFixturev3();
        if (!$result['success']) {
            return $this->errorResponse($result['message'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->successResponse($result, Response::HTTP_OK);

    }
}
