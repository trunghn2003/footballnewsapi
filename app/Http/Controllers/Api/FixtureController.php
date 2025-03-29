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

    public function getFixtures(Request $request)
    {
        $filters = $request->only(['competition', 'ids', 'dateFrom', 'dateTo', 'status', 'teamName', 'teamId']);
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);

        $fixtures = $this->fixtureService->getFixtures($filters, $perPage, $page);
        return $this->successResponse($fixtures);
    }
}