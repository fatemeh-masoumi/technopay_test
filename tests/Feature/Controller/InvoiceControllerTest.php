<?php

namespace Tests\Feature\Controller;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Wallet;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Wallet $wallet;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000000,
            'is_active' => true,
        ]);
        

        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 500000,
        ]);
    }

    public function test_pay_request_success()
    {
        $response = $this->actingAs($this->user)->postJson(
            route('invoices.payRequest', $this->invoice->id),
            ['method' => 'wallet']
        );
  
        $response->assertStatus(200);
   
      
        $response->assertJsonStructure([
            'message',
            'payment_id',
            'otp_code',
        ]);
    }

    public function test_pay_request_fail_blocked_user()
    {
        $this->user->blocked = true;
        $this->user->save();

        $response = $this->actingAs($this->user)->postJson(
            route('invoices.payRequest', $this->invoice->id),
            ['method' => 'wallet']
        );

      
        $response->assertStatus(403);
        $response->assertJson(['message' => 'کاربر مسدود است']);
    }

    public function test_confirm_payment_success()
    {
        $responsePay = $this->actingAs($this->user)->postJson(
            route('invoices.payRequest', $this->invoice->id),
            ['method' => 'wallet']
        );

        $paymentId = $responsePay->json('payment_id');
        $otp = $responsePay->json('otp_code');

        $payment = Payment::find($paymentId);

        $responseConfirm = $this->actingAs($this->user)->postJson(
            route('payments.confirm', $payment->id),
            ['otp_code' => $otp]
        );

        $responseConfirm->assertStatus(200);
        $responseConfirm->assertJson([
            'message' => 'پرداخت با موفقیت انجام شد'
        ]);
    }

    public function test_confirm_payment_fail_invalid_otp()
    {
        $this->withoutExceptionHandling();
        $responsePay = $this->actingAs($this->user)->postJson(
            route('invoices.payRequest', $this->invoice->id),
            ['method' => 'wallet']
        );

        $paymentId = $responsePay->json('payment_id');

        $payment = Payment::find($paymentId);

        $responseConfirm = $this->actingAs($this->user)->postJson(
            route('payments.confirm', $payment->id),
            ['otp_code' => 'wrong-otp']
        );

        $responseConfirm->assertStatus(400);
        $responseConfirm->assertJson([
            'message' => 'پرداخت ناموفق یا OTP اشتباه/منقضی شده است'
        ]);
    }
}
