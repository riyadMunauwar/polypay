<?php 

namespace Riyad\Polypay\Constants;

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
    public const BEFORE_PAYMENT_PROCESS = 'beforePaymentProcess';

    /** 
     * Hook name executed after a payment fails.
     */
    public const AFTER_PAYMENT_FAILED = 'afterPaymentFailed';

    /** 
     * Hook name executed after a payment succeeds.
     */
    public const AFTER_PAYMENT_SUCCESS = 'afterPaymentSuccess';
}