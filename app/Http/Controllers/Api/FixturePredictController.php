<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FixturePredictService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class FixturePredictController extends Controller
{
    use ApiResponseTrait;

    private FixturePredictService $fixturePredictService;

    public function __construct(FixturePredictService $fixturePredictService)
    {
        $this->fixturePredictService = $fixturePredictService;
    }

    /**
     * Get match prediction for a specific fixture
     *
     * @param int $fixtureId
     * @return JsonResponse
     */
    public function predictMatch(int $fixtureId): JsonResponse
    {
        try {
            $prediction = $this->fixturePredictService->predictMatchOutcome($fixtureId);

            if (!$prediction['success']) {
                return $this->errorResponse(
                    $prediction['error'] ?? 'Failed to generate prediction',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            return $this->successResponse($prediction);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
