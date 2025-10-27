<?php

namespace App\Services\Payment;

use Carbon\Carbon;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;


class WalletPaymentStrategy implements PaymentStrategy
{
    const DAILY_LIMIT = 1000000; 

    public function pay(Invoice $invoice): bool
    {
        $wallet = $invoice->user->wallet;

        // بررسی موجودی و محدودیت روزانه
        if (!$wallet || !$wallet->active || $wallet->balance < $invoice->amount) {
            return false;
        }

        $todaySpent = Invoice::where('status', 'paid')
            ->whereDate('paid_at', Carbon::today())
            ->sum('amount');

        if (($todaySpent + $invoice->amount) > self::DAILY_LIMIT) {
            return false;
        }

        DB::transaction(function () use ($invoice, $wallet) {
            $wallet->balance -= $invoice->amount;
            $wallet->save();

            $invoice->update([
                'status' => 'paid',
                'paid_at' => Carbon::now(),
            ]);
        });

        return true;
    }
}
