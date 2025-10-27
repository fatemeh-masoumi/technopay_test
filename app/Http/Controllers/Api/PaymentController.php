<?php

namespace App\Http\Controllers\Api;


use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    const DAILY_LIMIT = 1000; // سقف هزینه روزانه

    public function processPayment(Request $request, Invoice $invoice)
    {
        $user = Auth::user();

        // 1. محاسبه مجموع تراکنش‌های روزانه کاربر
        $todayTransactions = Payment::where('user_id', $user->id)
                ->whereDate('created_at', today()) // فقط تراکنش‌های امروز
                ->sum('amount'); // مجموع مبلغ تراکنش‌ها

        // 2. بررسی اینکه آیا مجموع تراکنش‌ها از سقف روزانه تجاوز کرده است
        if (($todayTransactions + $invoice->amount) > self::DAILY_LIMIT) {
            return response()->json(['error' => 'Daily limit exceeded.'], 400);
        }

        // 3. ادامه پردازش تراکنش (کسر موجودی کیف پول و غیره)
        $wallet = $user->wallet;
        if ($wallet->balance < $invoice->amount) {
            return response()->json(['error' => 'Insufficient balance.'], 400);
        }

        // 4. شروع تراکنش دیتابیس
        DB::beginTransaction();

        try {
            // 5. ایجاد تراکنش جدید
            $transaction = Payment::create([
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->amount,
            ]);

            // 6. کسر موجودی کیف پول
            $wallet->decrement('balance', $invoice->amount);

            // 7. ثبت زمان و موفقیت تراکنش
            DB::commit();
            
            return response()->json(['message' => 'Payment successful.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Payment failed.'], 500);
        }
    }
}
