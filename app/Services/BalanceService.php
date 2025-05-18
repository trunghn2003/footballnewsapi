<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBalance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BalanceService
{
    /**
     * Nạp tiền vào tài khoản
     */
    public function deposit(User $user, float $amount, string $description = null): array
    {
        try {
            DB::beginTransaction();

            // Tạo giao dịch nạp tiền
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->type = 'DEPOSIT';
            $transaction->amount = $amount;
            $transaction->status = 'COMPLETED';
            $transaction->reference = 'DEP' . Str::random(10);
            $transaction->description = $description ?? 'Nạp tiền vào tài khoản';
            $transaction->save();

            // Cập nhật số dư
            $balance = UserBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );
            $balance->balance += $amount;
            $balance->save();

            DB::commit();

            return [
                'success' => true,
                'transaction' => $transaction,
                'new_balance' => $balance->balance
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Rút tiền từ tài khoản
     */
    public function withdraw(User $user, float $amount, string $description = null): array
    {
        try {
            DB::beginTransaction();

            // Kiểm tra số dư
            $balance = UserBalance::where('user_id', $user->id)->first();
            if (!$balance || $balance->balance < $amount) {
                return [
                    'success' => false,
                    'error' => 'Số dư không đủ'
                ];
            }

            // Tạo giao dịch rút tiền
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->type = 'WITHDRAW';
            $transaction->amount = $amount;
            $transaction->status = 'COMPLETED';
            $transaction->reference = 'WIT' . Str::random(10);
            $transaction->description = $description ?? 'Rút tiền từ tài khoản';
            $transaction->save();

            // Cập nhật số dư
            $balance->balance -= $amount;
            $balance->save();

            DB::commit();

            return [
                'success' => true,
                'transaction' => $transaction,
                'new_balance' => $balance->balance
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Đặt cược
     */
    public function placeBet(User $user, float $amount, array $betDetails): array
    {
        try {
            DB::beginTransaction();

            // Kiểm tra số dư
            $balance = UserBalance::where('user_id', $user->id)->first();
            if (!$balance || $balance->balance < $amount) {
                return [
                    'success' => false,
                    'error' => 'Số dư không đủ để đặt cược'
                ];
            }

            // Tạo giao dịch đặt cược
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->type = 'BET';
            $transaction->amount = $amount;
            $transaction->status = 'COMPLETED';
            $transaction->reference = 'BET' . Str::random(10);
            $transaction->description = "Đặt cược {$betDetails['bet_type']} cho trận đấu #{$betDetails['fixture_id']}";
            $transaction->metadata = $betDetails;
            $transaction->save();

            // Cập nhật số dư
            $balance->balance -= $amount;
            $balance->save();

            DB::commit();

            return [
                'success' => true,
                'transaction' => $transaction,
                'new_balance' => $balance->balance
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Xử lý thắng cược
     */
    public function processWin(User $user, float $amount, array $betDetails): array
    {
        try {
            DB::beginTransaction();

            // Tạo giao dịch thắng cược
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->type = 'WIN';
            $transaction->amount = $amount;
            $transaction->status = 'COMPLETED';
            $transaction->reference = 'WIN' . Str::random(10);
            $transaction->description = "Thắng cược {$betDetails['bet_type']} từ trận đấu #{$betDetails['fixture_id']}";
            $transaction->metadata = $betDetails;
            $transaction->save();

            // Cập nhật số dư
            $balance = UserBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );
            $balance->balance += $amount;
            $balance->save();

            DB::commit();

            return [
                'success' => true,
                'transaction' => $transaction,
                'new_balance' => $balance->balance
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy số dư hiện tại
     */
    public function getBalance(User $user): array
    {
        $balance = UserBalance::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        return [
            'success' => true,
            'balance' => $balance->balance
        ];
    }

    /**
     * Lấy lịch sử giao dịch
     */
    public function getTransactionHistory(User $user, int $limit = 10): array
    {
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'transactions' => $transactions
        ];
    }
    public function giveAllUser($amount)
    {
        DB::beginTransaction();
        try {
            foreach (User::all() as $user) {
                // Tạo giao dịch nạp tiền
                $transaction = new Transaction();
                $transaction->user_id = $user->id;
                $transaction->type = 'DEPOSIT';
                $transaction->amount = $amount;
                $transaction->status = 'COMPLETED';
                $transaction->reference = 'DEP' . Str::random(10);
                $transaction->description = 'Nạp tiền vào tài khoản';
                $transaction->save();

                // Cập nhật số dư
                $balance = UserBalance::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0]
                );
                $balance->balance += $amount;
                $balance->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
        return [
            'success' => true,
            'transaction' => $transaction,
            'new_balance' => $balance->balance
        ];
    }
}
