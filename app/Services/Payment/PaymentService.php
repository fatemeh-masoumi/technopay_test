<?php

namespace App\Services\Payment;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function createPaymentWithOtp(Invoice $invoice, PaymentStrategyInterface $strategy): Payment
    {
        return Payment::create([
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'amount' => $invoice->amount,
            'status' => 'pending',
            'method' => $strategy->getName(),
            'otp_code' => rand(100000, 999999),
            'otp_expires_at' => now()->addMinutes(5),
        ]);
    }

    public function confirmPayment(Payment $payment, string $otp, PaymentStrategyInterface $strategy): bool
    {
        if ($payment->otp_code !== $otp || Carbon::now()->greaterThan($payment->otp_expires_at)) {
            return false;
        }
    
        return DB::transaction(function () use ($payment, $strategy) {
            return $strategy->pay($payment);
        });
    }
}

