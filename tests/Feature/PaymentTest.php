<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Payment;
use App\Models\Invoice;

use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Wallet $wallet;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'blocked' => false,
        ]);

        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 500000,
            'is_active' => true,
        ]);

        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100000,
        ]);
    }

    /** @test */
    public function user_can_request_payment_and_receive_otp()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/invoice/pay-request', [
                'invoice_id' => $this->invoice->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'otp',
                'payment_id',
            ]);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function user_can_confirm_payment_with_correct_otp()
    {
        $request = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/invoice/pay-request', [
                'invoice_id' => $this->invoice->id,
            ])
            ->json();

        $paymentId = $request['payment_id'];
        $otp = $request['otp'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/invoice/confirm', [
                'payment_id' => $paymentId,
                'otp_code' => $otp,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'پرداخت با موفقیت انجام شد',
            ]);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 'paid',
        ]);
    }

    /** @test */
    public function cannot_confirm_with_wrong_otp()
    {
        $request = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/invoice/pay-request', [
                'invoice_id' => $this->invoice->id,
            ])
            ->json();

        $paymentId = $request['payment_id'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/invoice/confirm', [
                'payment_id' => $paymentId,
                'otp_code' => '999999', // اشتباه
            ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'کد تایید اشتباه است']);
    }
}
