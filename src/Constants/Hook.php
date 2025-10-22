<?php 

namespace Riyad\PolyPay\Constants;

/**
 * Class Hook
 *
 * Defines string constants for named hooks used in the payment system.
 *
 * - `BEFORE_PAYMENT_PROCESS`: Triggered before a payment is processed.
 * - `AFTER_PAYMENT_FAILED`: Triggered after a payment fails.
 * - `AFTER_PAYMENT_SUCCESS`: Triggered after a payment succeeds.
 *
 * This class is `final` and cannot be instantiated.
 */
final class Hook 
{
    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct() {}

    /** 
     * Hook name executed before a payment is processed.
     */
    public const BEFORE_PAYMENT_PROCESS = 'pay.beforePaymentProcess';
    public const AFTER_PAYMENT_PROCESS = 'pay.afterPaymentProcess';
    public const AFTER_PAYMENT_FAILED = 'pay.afterPaymentFailed';
    public const AFTER_PAYMENT_SUCCESS = 'pay.afterPaymentSuccess';
}