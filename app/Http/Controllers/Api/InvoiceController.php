<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PaymentStrategyFactory;

class InvoiceController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function payRequest(Request $request, Invoice $invoice)
    {
        $request->validate([
            'method' => 'required|string',
        ]);

        $user = $request->user();
       

        if ($invoice->user_id !== $user->id) {
            return response()->json(['message' => 'این فاکتور متعلق به شما نیست'], 403);
        }

        if ($user->blocked) {
            return response()->json(['message' => 'کاربر مسدود است'], 403);
        }

        $strategy = PaymentStrategyFactory::make($request->method);

        $payment = $this->paymentService->createPaymentWithOtp($invoice, $strategy);


        return response()->json([
            'payment_id' => $payment->id,
            'message' => 'کد تایید برای شما ارسال شد',
            'otp_code' => $payment->otp_code, // فقط برای تست
            'confirm_url' => route('payments.confirm', $payment->id),
        ]);
    }

    public function confirm(Request $request, Payment $payment)
    {
        $request->validate(['otp_code' => 'required']);

     
        $strategy = PaymentStrategyFactory::make($payment->method);

        $success = $this->paymentService->confirmPayment($payment, $request->otp_code, $strategy);

        if (!$success) {
            return response()->json(['message' => 'پرداخت ناموفق یا OTP اشتباه/منقضی شده است'], 400);
        }

        return response()->json([
            'message' => 'پرداخت با موفقیت انجام شد',
            'invoice' => $payment->payable,
            'payment' => $payment,
        ]);
    }
}
