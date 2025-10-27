<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use App\Services\Payment\WalletPaymentStrategy;

class InvoiceController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function payRequest(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
        ]);


    
        $invoice = Invoice::find($request->invoice_id);

        if(!$invoice){
            return response()->json(['message' => 'این فاکتور موجود نیست'], 400);
        }
      
        $user = $request->user();
        if ($invoice->user_id !== $user->id) {
            return response()->json(['message' => 'این فاکتور متعلق به شما نیست'], 403);
        }

        $wallet = $user->wallet;

        if ($user->blocked) {
            return response()->json(['message' => 'کاربر مسدود است'], 403);
        }

        if (!$wallet || !$wallet->active) {
            return response()->json(['message' => 'کیف پول فعال نیست'], 403);
        }

        $todaySpent = $user->payments()
            ->where('method', 'wallet')
            ->where('status', 'paid')
            ->whereDate('paid_at', now())
            ->sum('amount');

        if (($todaySpent + $invoice->amount) > WalletPaymentStrategy::DAILY_LIMIT) {
            return response()->json(['message' => 'محدودیت روزانه پرداخت شده است'], 400);
        }

        $payment = $this->paymentService->createPaymentWithOtp($invoice);

        return response()->json([
            'message' => 'کد تایید برای شما ارسال شد',
            'otp' => $payment->otp_code, // فقط برای تست
            'payment_id' => $payment->id
        ]);
    }


    /**
     * تایید پرداخت
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'otp_code' => 'required',
        ]);

        $payment = Payment::find($request->payment_id);

        $success = $this->paymentService->confirmPayment($payment, $request->otp_code);

        if (!$success) {
            return response()->json(['message' => 'پرداخت ناموفق یا OTP اشتباه/منقضی شده است'], 400);
        }

        return response()->json([
            'message' => 'پرداخت با موفقیت انجام شد',
            'invoice' => $payment->payable,
            'payment' => $payment
        ]);
    }
}
