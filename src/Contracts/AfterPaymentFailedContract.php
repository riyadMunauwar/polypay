<?php

namespace Riyad\Polypay\Contracts;

use Riyad\Polypay\DTO\PaymentResult;

/**
 * Interface AfterPaymentFailedContract
 *
 * Defines a contract for hooks that execute **after a payment has failed**.
 * Implementing classes must provide a `handle` method to respond to failed payments.
 */
interface AfterPaymentFailedContract
{
    /**
     * Handle actions after a payment failure.
     *
     * This method is called after the `pay` method on the gateway returns a failed result.
     * Implementations can perform tasks such as logging, sending notifications, or rolling back changes.
     *
     * @param PaymentResult $dto The payment result object returned by the gateway
     * @param string $gateway The name of the gateway that attempted the payment
     * @return mixed Optional return value depending on hook implementation
     *
     * @throws \RuntimeException If post-failure processing cannot be completed
     */
    public function handle(PaymentResult $dto, string $gateway): mixed;
}