<?php

namespace Riyad\Polypay\Contracts;

use Riyad\Polypay\DTO\Payment;

/**
 * Interface BeforePaymentProcessContract
 *
 * Defines a contract for hooks that should execute **before** a payment is processed.
 * Implementing classes must provide a `handle` method to modify or validate the payment DTO.
 */
interface BeforePaymentProcessContract
{
    /**
     * Handle a payment before it is processed by the gateway.
     *
     * This method is called prior to invoking the `pay` method on the gateway.
     * Implementations can modify, validate, or enrich the Payment DTO as needed.
     *
     * @param Payment $dto The payment data transfer object to be processed
     * @param string $gatewayName The name of the currently selected gateway
     * @return Payment The potentially modified Payment DTO
     *
     * @throws \RuntimeException If pre-processing fails or validation errors occur
     */
    public function handle(BaseDTO $dto, string $gatewayName): Payment;
}