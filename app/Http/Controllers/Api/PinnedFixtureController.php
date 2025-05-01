<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PinnedFixtureService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LogicException;

class PinnedFixtureController extends Controller
{
    use ApiResponseTrait;

    protected $pinnedFixtureService;

    public function __construct(PinnedFixtureService $pinnedFixtureService)
    {
        $this->pinnedFixtureService = $pinnedFixtureService;
    }

    /**
     * Ghim một trận đấu
     *
     * @param Request $request
     * @param int $fixtureId
     * @return \Illuminate\Http\JsonResponse
     */
    public function pinFixture(Request $request, $fixtureId)
    {
        try {
            $options = $request->only(['notify_before', 'notify_result']);
            $result = $this->pinnedFixtureService->pinFixture($fixtureId, auth()->id(), $options);
            return $this->successResponse($result, $result['message']);
        } catch (LogicException $e) {
            Log::error('Ghim trận đấu thất bại: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Bỏ ghim một trận đấu
     *
     * @param int $fixtureId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unpinFixture($fixtureId)
    {
        try {
            $result = $this->pinnedFixtureService->unpinFixture($fixtureId, auth()->id());
            return $this->successResponse($result, $result['message']);
        } catch (LogicException $e) {
            Log::error('Bỏ ghim trận đấu thất bại: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Lấy danh sách trận đấu đã ghim của người dùng hiện tại
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPinnedFixtures(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
            $result = $this->pinnedFixtureService->getUserPinnedFixtures(auth()->id(), $perPage, $page);
            return $this->successResponse($result, 'Lấy danh sách trận đấu đã ghim thành công');
        } catch (LogicException $e) {
            Log::error('Lấy danh sách trận đấu đã ghim thất bại: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Kiểm tra trạng thái ghim của một trận đấu
     *
     * @param int $fixtureId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPinStatus($fixtureId)
    {
        try {
            $result = $this->pinnedFixtureService->checkPinStatus($fixtureId, auth()->id());
            return $this->successResponse($result, 'Kiểm tra trạng thái ghim thành công');
        } catch (LogicException $e) {
            Log::error('Kiểm tra trạng thái ghim thất bại: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
