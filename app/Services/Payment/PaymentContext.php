<?php

namespace App\Services\Payment;

use App\Models\Invoice;

class PaymentContext
{
    protected PaymentStrategy $strategy;

    public function __construct(PaymentStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function pay(Invoice $invoice): bool
    {
        return $this->strategy->pay($invoice);
    }
}
