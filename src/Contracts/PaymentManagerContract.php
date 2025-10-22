<?php

namespace Riyad\PolyPay\Contracts;

use Riyad\PolyPay\Contracts\GatewayContract;
use Riyad\PolyPay\DTO\BaseDTO;
use Riyad\PolyPay\DTO\VerificationResult;
use Riyad\PolyPay\DTO\PaymentResult;
use Riyad\PolyPay\DTO\PaymentVerification;

/**
 * Interface PaymentManagerContract
 *
 * Defines the contract for a payment manager service.
 * Responsible for registering/unregistering gateways, selecting active gateway,
 * processing payments, and managing hooks for payment lifecycle events.
 */
interface PaymentManagerContract
{
    /**
     * Register a payment gateway factory with optional metadata.
     *
     * @param string $name Unique gateway name
     * @param callable $factory Factory that returns an instance of GatewayContract
     * @param array $meta Optional metadata associated with the gateway
     * @return void
     *
     * @throws \InvalidArgumentException If the name is empty or factory is not callable
     */
    public function register(string $name, callable $factory, array $meta = []): void;

    /**
     * Unregister a previously registered payment gateway.
     *
     * @param string $name Gateway name
     * @return void
     *
     * @throws GatewayNotFoundException If the gateway is not registered
     */
    public function unregister(string $name): void;

    /**
     * Select the active gateway for subsequent payment processing.
     *
     * @param string $gateway Name of the gateway to activate
     * @return static The current instance for method chaining
     *
     * @throws GatewayNotFoundException If the gateway is not registered
     */
    public function gateway(string $gateway): GatewayContract;

    /**
     * Apply a callback function to all registered gateways and collect results.
     *
     * @param callable $callback Function with signature: function(GatewayContract $gateway): mixed
     * @return array<string, mixed> Array of results keyed by gateway name
     */
    public function map(callable $callback): array;

    /**
     * Filter gateways using a callback function.
     *
     * @param callable $callback Function with signature: function(GatewayContract $gateway): bool
     * @return array<string, GatewayContract> Array of gateways that match the filter
     */
    public function filter(callable $callback): array;

    /**
     * Execute all registered hooks for a successful payment.
     *
     * @param PaymentResult $dto The result of the payment
     * @return mixed Result returned by the hook execution (depends on hook configuration)
     *
     * @throws \RuntimeException If no gateway is selected
     */
    public function paymentSuccess(string $gateway, PaymentResult $dto): void;

    /**
     * Execute all registered hooks for a failed payment.
     *
     * @param PaymentResult $dto The result of the payment
     * @return mixed Result returned by the hook execution (depends on hook configuration)
     *
     * @throws \RuntimeException If no gateway is selected
     */
    public function paymentFailed(string $gateway, PaymentResult $dto): void;
}