<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FixtureService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class FixtureController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private FixtureService $fixtureService
    ) {
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
        $userId = auth()->check() ? auth()->id() : null;
        $fixture = $this->fixtureService->getFixtureById($id, $userId);
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
        $userId = auth()->check() ? auth()->id() : null;

        $fixtures = $this->fixtureService->getFixtures($filters, $perPage, $page, $userId);
        return $this->successResponse($fixtures);
    }
    public function getFixtureCompetition(Request $request)
    {
        $filters = $request->only(['dateFrom', 'dateTo', 'competition']);
        $userId = auth()->check() ? auth()->id() : null;
        $fixtures = $this->fixtureService->getFixtureByCompetition($filters, $userId);
        return $this->successResponse($fixtures);
    }

    /**
     * Get recent fixtures for a team
     *
     * @param Request $request
     * @param int $teamId
     * @return JsonResponse
     */    public function getRecentFixtures(Request $request, int $teamId): JsonResponse
    {
        try {
            $limit = $request->input('limit', 5);
            $userId = auth()->check() ? auth()->id() : null;
            $result = $this->fixtureService->getRecentFixturesByTeam($teamId, $limit, $userId);
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
     */    public function getUpcomingFixtures(Request $request, int $teamId): JsonResponse
    {
        try {
            $filter = $request->only([
                'competition',
                'dateFrom',
                'dateTo',
                'status',
                'teamName',
                'teamId',
                'competition_id',
                'limit'
            ]);
            $userId = auth()->check() ? auth()->id() : null;
            $result = $this->fixtureService->getUpcomingFixturesByTeam($teamId, $filter, $userId);
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

    /**
     * Get recent fixtures filtered by team name and/or competition name
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentFixturesByFilters(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $filters = [
                'status' => 'FINISHED',
                'recently' => 1
            ];

            // Add team name filter
            if ($request->has('team_name')) {
                $filters['teamName'] = $request->input('team_name');
            }

            // Add competition name filter
            if ($request->has('competition_name')) {
                $competition = $this->fixtureService->getCompetitionByName($request->input('competition_name'));
                if ($competition) {
                    $filters['competition_id'] = $competition->id;
                }
            }

            $fixtures = $this->fixtureService->getFixtures($filters, $perPage, $page);
            return $this->successResponse($fixtures);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get upcoming fixtures (matches that are about to happen)
     *
     * @param Request $request
     * @return JsonResponse
     */    public function getAllUpcomingFixtures(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $filters = [];

            // Add team name filter
            if ($request->has('team_name')) {
                $filters['teamName'] = $request->input('team_name');
            }

            // Add competition filter by name
            if ($request->has('competition_name')) {
                $filters['competitionName'] = $request->input('competition_name');
            } else if ($request->has('competition_id')) {
                $filters['competition_id'] = $request->input('competition_id');
            }

            // Add date range filter (for upcoming days)
            if ($request->has('days_ahead')) {
                $filters['daysAhead'] = $request->input('days_ahead');
            }

            $userId = auth()->check() ? auth()->id() : null;
            $fixtures = $this->fixtureService->getUpcomingFixtures($filters, $perPage, $page, $userId);
            return $this->successResponse($fixtures);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function chatbot(Request $request)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('http://3.1.100.34:5001/query', [
            'query' => $request->query1,
        ]);
        return $response->json();
    }
}
