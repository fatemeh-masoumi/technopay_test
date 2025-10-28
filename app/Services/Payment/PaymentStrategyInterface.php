<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface PaymentStrategyInterface
{
    public function pay(Payment $payment);
    
    public function getName(): string;

    public function getDailyLimit(): float;
}
