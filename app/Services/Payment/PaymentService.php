<?php

namespace App\Services\Payment;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * ایجاد Payment جدید با OTP
     */
    public function createPaymentWithOtp(Invoice $invoice): Payment
    {
        $otp = rand(100000, 999999);

        return $invoice->payments()->create([
            'user_id' => $invoice->user->id,
            'amount' => $invoice->amount,
            'method' => 'wallet',
            'status' => 'pending',
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);
    }

  
    public function confirmPayment(Payment $payment, string $otp): bool
    {
        if ($payment->otp_code !== $otp || Carbon::now()->greaterThan($payment->otp_expires_at)) {
            return false;
        }

        $context = new PaymentContext(new WalletPaymentStrategy());

        // تراکنش امن
        return DB::transaction(function () use ($context, $payment) {
            return $context->pay($payment);
        });
    }
}
