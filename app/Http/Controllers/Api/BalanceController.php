<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BalanceController extends Controller
{
    use ApiResponseTrait;

    private BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Nạp tiền vào tài khoản
     */
    public function deposit(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1000',
                'description' => 'nullable|string|max:255'
            ]);

            $result = $this->balanceService->deposit(
                $request->user(),
                $validated['amount'],
                $validated['description'] ?? null
            );

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['error'] ?? 'Nạp tiền thất bại',
                    Response::HTTP_BAD_REQUEST
                );
            }

            return $this->successResponse([
                'transaction' => $result['transaction'],
                'new_balance' => $result['new_balance']
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Rút tiền từ tài khoản
     */
    public function withdraw(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1000',
                'description' => 'nullable|string|max:255'
            ]);

            $result = $this->balanceService->withdraw(
                $request->user(),
                $validated['amount'],
                $validated['description'] ?? null
            );

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['error'] ?? 'Rút tiền thất bại',
                    Response::HTTP_BAD_REQUEST
                );
            }

            return $this->successResponse([
                'transaction' => $result['transaction'],
                'new_balance' => $result['new_balance']
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Lấy số dư hiện tại
     */
    public function getBalance(Request $request): JsonResponse
    {
        try {
            $result = $this->balanceService->getBalance($request->user());

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['error'] ?? 'Không thể lấy số dư',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            return $this->successResponse([
                'balance' => $result['balance']
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Lấy lịch sử giao dịch
     */
    public function getTransactionHistory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'limit' => 'nullable|integer|min:1|max:50'
            ]);

            $result = $this->balanceService->getTransactionHistory(
                $request->user(),
                $validated['limit'] ?? 10
            );

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['error'] ?? 'Không thể lấy lịch sử giao dịch',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            return $this->successResponse([
                'transactions' => $result['transactions']
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Cấp tiền cho tất cả người dùng
     */
    public function giveAllUserBalance(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1000'
            ]);

            $result = $this->balanceService->giveAllUser($validated['amount']);

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['error'] ?? 'Cấp tiền thất bại',
                    Response::HTTP_BAD_REQUEST
                );
            }

            return $this->successResponse([
                'message' => 'Cấp tiền thành công cho tất cả người dùng'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
