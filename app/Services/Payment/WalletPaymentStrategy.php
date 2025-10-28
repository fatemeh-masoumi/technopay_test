<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class WalletPaymentStrategy implements PaymentStrategyInterface 
{
    const DAILY_LIMIT = 1000000; 

    public function pay(Payment $payment)
    {
        $wallet = $payment->user->wallet;

        if (!$wallet || !$wallet->is_active || $wallet->balance < $payment->amount) {
            return false;
        }

        $todaySpent = $payment->user->payments()
            ->where('status', 'paid')
            ->whereDate('paid_at', now())
            ->sum('amount');

        if (($todaySpent + $payment->amount) > self::DAILY_LIMIT) {
            return false;
        }

        DB::transaction(function () use ($payment, $wallet) {
            $wallet->balance -= $payment->amount;
            $wallet->save();

            $payment->payable->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $payment->update(['status' => 'paid']);
        });

        return true;
    }

    public function getName(): string
    {
        return 'wallet';
    }

    public function getDailyLimit(): float
    {
        return self::DAILY_LIMIT;
    }
}
