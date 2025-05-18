<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserBalance;
use App\Models\Transaction;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Tạo số dư ban đầu 10 triệu
        $balance = new UserBalance();
        $balance->user_id = $user->id;
        $balance->balance = 10000000; // 10 triệu
        $balance->save();

        // Tạo transaction ghi nhận số dư ban đầu
        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->type = 'DEPOSIT';
        $transaction->amount = 10000000;
        $transaction->status = 'COMPLETED';
        $transaction->reference = 'REG' . Str::random(10);
        $transaction->description = 'Số dư ban đầu khi đăng ký';
        $transaction->save();
    }
}
