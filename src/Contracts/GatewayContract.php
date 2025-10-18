<?php

namespace Riyad\Polypay\Contracts;

use Riyad\Polypay\DTO\BaseDTO;
use Riyad\Polypay\DTO\PaymentResult;
use Riyad\Polypay\DTO\Config;

/**
 * Interface GatewayContract
 *
 * Defines the contract that all payment gateways must implement.
 * Each gateway provides a unique name, configuration, and payment processing logic.
 */
interface GatewayContract
{
    /**
     * Get the unique name of the gateway.
     *
     * This name is used to register, select, and identify the gateway within the system.
     *
     * @return string Unique gateway name
     */
    public function name(): string;

    /**
     * Get the configuration object for the gateway.
     *
     * The configuration contains all necessary settings required to initialize and operate the gateway.
     *
     * @return Config $dto Config data transfer object
     */
    public function config(): Config;

    /**
     * Process a payment using the gateway.
     *
     * Each gateway must implement this method to handle the actual payment logic.
     *
     * @param Payment $dto Payment data transfer object containing the payment details
     * @return PaymentResult Result of the payment processing
     *
     * @throws \RuntimeException If payment cannot be processed or fails due to gateway errors
     */
    public function pay(BaseDTO $dto): PaymentResult;
}