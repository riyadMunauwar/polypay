<?php

namespace Riyad\Polypay\Contracts;

use Riyad\Polypay\DTO\PaymentResult;

/**
 * Interface AfterPaymentSuccessContract
 *
 * Defines a contract for hooks that should execute **after a payment is successfully processed**.
 * Implementing classes must provide a `handle` method to respond to successful payments.
 */
interface AfterPaymentSuccessContract
{
    /**
     * Handle actions after a payment has been successfully processed.
     *
     * This method is called after the `pay` method on the gateway returns a successful result.
     * Implementations can perform actions such as logging, notifications, or updating records.
     *
     * @param PaymentResult $dto The payment result object returned by the gateway
     * @param string $gateway The name of the gateway that processed the payment
     * @return mixed Optional return value depending on hook implementation
     *
     * @throws \RuntimeException If post-processing fails
     */
    public function handle(PaymentResult $dto, string $gateway): mixed;
}