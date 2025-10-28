<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Wallet;
use App\Services\Payment\PaymentService;
use App\Services\Payment\WalletPaymentStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $service;
    protected User $user;
    protected Wallet $wallet;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PaymentService();

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

    public function test_create_payment_with_otp()
    {
        $strategy = new WalletPaymentStrategy();
        $payment = $this->service->createPaymentWithOtp($this->invoice, $strategy);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertNotNull($payment->otp_code);
    }

    public function test_confirm_payment_success()
    {
        $strategy = new WalletPaymentStrategy();
        $payment = $this->service->createPaymentWithOtp($this->invoice, $strategy);

        $success = $this->service->confirmPayment($payment, $payment->otp_code, $strategy);

        $this->assertTrue($success);
        $this->assertEquals('paid', $payment->fresh()->status);
    }

    public function test_confirm_payment_fail_invalid_otp()
    {
        $strategy = new WalletPaymentStrategy();
        $payment = $this->service->createPaymentWithOtp($this->invoice, $strategy);

        $success = $this->service->confirmPayment($payment, 'wrong-otp', $strategy);

        $this->assertFalse($success);
        $this->assertEquals('pending', $payment->fresh()->status);
    }
}
