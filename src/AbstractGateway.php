<?php

namespace Riyad\Polypay;

use Riyad\Polypay\Contracts\GatewayContract;
use Riyad\Polypay\DTO\Payment;
use Riyad\Polypay\DTO\Config;
use Riyad\Polypay\DTO\PaymentResult;

/**
 * Class AbstractGateway
 *
 * Base abstract class for all payment gateways.
 * Implements GatewayContract and defines the required interface for concrete gateways.
 */
abstract class AbstractGateway implements GatewayContract
{
    /**
     * Get the unique name of the gateway.
     *
     * Each concrete gateway must provide its own unique name.
     *
     * @return string Gateway name
     */
    abstract public function name(): string;

    /**
     * Get the configuration for the gateway.
     *
     * Each gateway must provide its own configuration object containing necessary settings.
     *
     * @return Config Gateway configuration
     */
    abstract public function config(): Config;

    /**
     * Process a payment through the gateway.
     *
     * Each concrete gateway must implement this method to handle the actual payment logic.
     *
     * @param Payment $dto Payment data transfer object containing the payment details
     * @return PaymentResult Result of the payment processing
     */
    abstract public function pay(Payment $dto): PaymentResult;
}