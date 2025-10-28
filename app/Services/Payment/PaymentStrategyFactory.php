<?php 
namespace App\Services\Payment;

class PaymentStrategyFactory
{

    public const NAMESPACE  =  "\\App\\Services\\Payment\\";

    public static function make(string $method): PaymentStrategyInterface
    {
        $class = self::NAMESPACE . ucfirst($method) . "PaymentStrategy";

    

        if (!class_exists($class)) {
            throw new \Exception("روش پرداخت معتبر نیست: $method");
        }

        $strategy = new $class();

        if (!$strategy instanceof PaymentStrategyInterface) {
            throw new \Exception("استراتژی باید PaymentStrategyInterface را پیاده‌سازی کند.");
        }

        return $strategy;
    }
}