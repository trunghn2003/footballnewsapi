<?php

namespace App\Http\Controllers\Api;

use App\Services\AreaService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;

class AreaController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private AreaService $areaService
    ) {
    }

    /**
     * Trigger area sync
     */
    public function sync(): JsonResponse
    {
        $result = $this->areaService->syncAreas();

        if (!$result['success']) {
            return response()->json([
                'message' => 'Area sync failed',
                'error' => $result['error']
            ], 500);
        }

        return response()->json([
            'message' => 'Areas synced successfully',
            'stats' => $result['stats']
        ]);
    }

    /**
     * Get area by id
     * @param int $id
     * @return JsonResponse
     */
    public function getAreaById(int $id): JsonResponse
    {
        $area = $this->areaService->getAreaById($id);
        return $this->successResponse($area);
    }

    /**
     * Get all areas
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['name']);
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);
        $result = $this->areaService->getPaginatedAreas($filters, $perPage, $page);
        return $this->successResponse($result);
    }
}
