<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceBetRequest;
use App\Services\BettingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BettingController extends Controller
{
    use ApiResponseTrait;

    private BettingService $bettingService;

    public function __construct(BettingService $bettingService)
    {
        $this->bettingService = $bettingService;
    }

    /**
     * Place a new bet
     */
    public function placeBet(PlaceBetRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->bettingService->placeBet(
                $request->user(),
                $validated['fixture_id'],
                $validated['bet_type'],
                $validated['amount'],
                $validated['predicted_score'] ?? null
            );

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['error'] ?? 'Failed to place bet',
                    Response::HTTP_BAD_REQUEST
                );
            }

            return $this->successResponse($result['bet']);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get user's betting history
     */
    public function getBettingHistory(Request $request): JsonResponse
    {
        try {
            $fixtureId = $request->input('fixture_id');
            $result = $this->bettingService->getUserBettingHistory($request->user(), $fixtureId);

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['error'] ?? 'Failed to get betting history',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            return $this->successResponse($result['bets']);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Process bet results for a completed match
     */
    public function processBetResults(int $fixtureId): JsonResponse
    {
        try {
            $result = $this->bettingService->processBetResults($fixtureId);

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['error'] ?? 'Failed to process bet results',
                    Response::HTTP_BAD_REQUEST
                );
            }

            return $this->successResponse([
                'message' => 'Bet results processed successfully',
                'processed_bets' => $result['processed_bets']
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get player rankings
     *
     * @return JsonResponse
     */
    public function getPlayerRankings(): JsonResponse
    {
        $result = $this->bettingService->getPlayerRankings();
        return $this->successResponse($result['rankings']);
    }
}
