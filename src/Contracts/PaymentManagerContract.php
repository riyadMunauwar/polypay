<?php

namespace Riyad\Polypay\Contracts;

use Riyad\Polypay\Contracts\GatewayContract;
use Riyad\Polypay\DTO\Payment;
use Riyad\Polypay\DTO\PaymentResult;

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
    public function gateway(string $gateway): static;

    /**
     * Process a payment using the currently active gateway.
     *
     * @param Payment $dto Payment data transfer object
     * @return PaymentResult The result of the payment processing
     *
     * @throws \RuntimeException If no gateway is selected
     */
    public function pay(Payment $dto): PaymentResult;

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
     * Register a hook to execute before payment processing.
     *
     * @param string|callable|BeforePaymentProcessContract $hook The hook to execute
     * @return void
     */
    public function onBeforePaymentProcess(string|callable|BeforePaymentProcessContract $hook): void;

    /**
     * Register a hook to execute after successful payment.
     *
     * @param string|callable|AfterPaymentSuccessContract $hook The hook to execute
     * @return void
     */
    public function onAfterPaymentSuccess(string|callable|AfterPaymentSuccessContract $hook): void;

    /**
     * Register a hook to execute after failed payment.
     *
     * @param string|callable|AfterPaymentFailedContract $hook The hook to execute
     * @return void
     */
    public function onAfterPaymentFailed(string|callable|AfterPaymentFailedContract $hook): void;

    /**
     * Execute all registered hooks for a successful payment.
     *
     * @param PaymentResult $dto The result of the payment
     * @return mixed Result returned by the hook execution (depends on hook configuration)
     *
     * @throws \RuntimeException If no gateway is selected
     */
    public function paymentSuccess(PaymentResult $dto): mixed;

    /**
     * Execute all registered hooks for a failed payment.
     *
     * @param PaymentResult $dto The result of the payment
     * @return mixed Result returned by the hook execution (depends on hook configuration)
     *
     * @throws \RuntimeException If no gateway is selected
     */
    public function paymentFailed(PaymentResult $dto): mixed;
}