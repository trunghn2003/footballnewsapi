<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FixtureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
class FixtureController extends Controller
{
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
}
