<?php

namespace Riyad\Polypay;

use Riyad\Polypay\Contracts\PaymentManagerContract;
use Riyad\Polypay\Contracts\GatewayContract;
use Riyad\Polypay\Contracts\GatewayRegistryContract;
use Riyad\Polypay\DTO\BaseDTO;
use Riyad\Polypay\DTO\PaymentResult;
use Riyad\Polypay\DTO\VerificationResult;
use Riyad\Polypay\Constants\Hook;
use Riyad\Polypay\Constants\HookReturnMode;
use Riyad\Polypay\Contracts\BeforePaymentProcessContract;
use Riyad\Polypay\Contracts\AfterPaymentSuccessContract;
use Riyad\Polypay\Contracts\AfterPaymentFailedContract;
use Riyad\Polypay\Contracts\HookRegistryContract;
use Riyad\Polypay\Contracts\SupportPaymentVerification;
use Riyad\Polypay\Exceptions\UnsupportedFeatureException;

/**
 * Class PaymentManager
 *
 * Manages payment gateways and provides hooks for payment processing events.
 * Implements singleton pattern to ensure a single instance throughout the application.
 *
 * @implements PaymentManagerContract
 */
class PaymentManager implements PaymentManagerContract
{
    /**
     * Singleton instance of PaymentManager.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Registry of available gateways.
     *
     * @var GatewayRegistryContract
     */
    private GatewayRegistryContract $registry;

    /**
     * Registry of hooks for payment processing events.
     *
     * @var HookRegistryContract
     */
    private HookRegistryContract $hookRegistry;

    /**
     * Currently selected gateway for processing payments.
     *
     * @var GatewayContract|null
     */
    private ?GatewayContract $currentGateway = null;

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @param GatewayRegistryContract $registry Gateway registry
     * @param HookRegistryContract $hookRegistry Hook registry
     */
    private function __construct(GatewayRegistryContract $registry, HookRegistryContract $hookRegistry)
    {
        $this->registry = $registry;
        $this->hookRegistry = $hookRegistry;
    }

    /**
     * Initialize the singleton PaymentManager instance.
     * Only the first call sets the registry instances.
     *
     * @param GatewayRegistryContract $registry Gateway registry
     * @param HookRegistryContract $hookRegistry Hook registry
     * @return self
     */
    public static function init(GatewayRegistryContract $registry, HookRegistryContract $hookRegistry): self
    {
        if (!self::$instance) {
            self::$instance = new self($registry, $hookRegistry);
        }
        return self::$instance;
    }

    /**
     * Get the existing singleton instance of PaymentManager.
     *
     * @return self
     * @throws \RuntimeException if the instance is not yet initialized
     */
    public static function instance(): self
    {
        if (!self::$instance) {
            throw new \RuntimeException("PaymentManager not initialized. Call PaymentManager::init() first.");
        }
        return self::$instance;
    }

    /**
     * Register a gateway factory with optional metadata.
     *
     * @param string $name Unique gateway name
     * @param callable $factory Factory that returns a GatewayContract instance
     * @param array $meta Optional metadata about the gateway
     * @return void
     * @throws \InvalidArgumentException If name is empty or factory is not callable
     */
    public function register(string $name, callable $factory, array $meta = []): void
    {
        $this->registry->register($name, $factory, $meta);
    }

    /**
     * Unregister a gateway.
     *
     * @param string $name Gateway name
     * @return void
     * @throws GatewayNotFoundException If the gateway is not registered
     */
    public function unregister(string $name): void
    {
        $this->registry->unregister($name);
    }

    /**
     * Set the active gateway for payment processing.
     *
     * @param string $gateway Gateway name
     * @return static
     */
    public function gateway(string $gateway): static
    {
        $this->currentGateway = $this->registry->get($gateway);
        return $this;
    }

    /**
     * Get the gateway instance from the registry.
     *
     * @param string $gateway The identifier of the gateway to retrieve.
     *
     * @return GatewayContract The resolved gateway instance.
     */
    public function getGateway(string $gateway) : GatewayContract
    {
        return $this->registry->get($gateway);
    }

    /**
     * Process a payment through the currently selected gateway.
     *
     * @param Payment $dto Payment data transfer object
     * @return PaymentResult
     * @throws \RuntimeException if no gateway is selected or DTO is invalid
     */
    public function pay(BaseDTO $dto): PaymentResult
    {
        $this->ensureGatewayIsSelected();

        if (!$dto instanceof BaseDTO) {
            throw new \RuntimeException("Provided DTO must return an instance of BaseDTO");
        }

        // Execute beforePaymentProcess hook
        $dto = $this->hookRegistry->execute(
            Hook::BEFORE_PAYMENT_PROCESS,
            $dto,
            $this->currentGateway->name()
        );

        // Process payment via gateway
        $result = $this->currentGateway->pay($dto);

        return $result;
    }

    /**
     * Verify a payment using the specified gateway.
     *
     * Retrieves the gateway instance from the registry and ensures it supports
     * payment verification. If the gateway does not implement
     * SupportPaymentVerification, an UnsupportedFeatureException is thrown.
     *
     * @param PaymentVerification $dto Data Transfer Object containing payment verification details.
     * @param string $gateway Identifier of the gateway to be used for verification.
     *
     * @return bool Returns true if the payment is successfully verified, otherwise false.
     *
     * @throws UnsupportedFeatureException If the gateway does not support payment verification.
     */
    public function verify(BaseDTO $dto) : VerificationResult
    {
        $this->ensureGatewayIsSelected();

        $gatewayInstance = $this->currentGateway;

        if (!$gatewayInstance instanceof SupportPaymentVerification) {
            throw new UnsupportedFeatureException(
                sprintf('Gateway [%s] does not support payment verification.', $gateway)
            );
        }

        return $gatewayInstance->verify($dto);
    }

    /**
     * Apply a callback to all registered gateways and return the results.
     *
     * @param callable $callback Function to execute for each gateway (GatewayContract $gateway): mixed
     * @return array<string, mixed> Array of results keyed by gateway name
     */
    public function map(callable $callback): array
    {
        $results = [];

        foreach ($this->registry->all(false) as $name) {
            $gateway = $this->registry->get($name); // ensures gateway is instantiated
            $results[] = $callback($gateway);
        }

        return $results;
    }

    /**
     * Filter gateways using a callback.
     *
     * @param callable $callback Function to filter gateways (GatewayContract $gateway): bool
     * @return array<string, GatewayContract> Filtered gateways keyed by name
     */
    public function filter(callable $callback): array
    {
        $filtered = [];

        foreach ($this->registry->all(false) as $name) {
            $gateway = $this->registry->get($name);
            if ($callback($gateway)) {
                $filtered[$name] = $gateway;
            }
        }

        return $filtered;
    }

    /**
     * Register a hook to execute before payment processing.
     *
     * @param string|callable|BeforePaymentProcessContract $hook Hook callback, class name, or instance
     * @return void
     */
    public function onBeforePaymentProcess(string|callable|BeforePaymentProcessContract $hook): void
    {
        if(is_callable($hook)){
            $this->hookRegistry->configureHook(
                Hook::BEFORE_PAYMENT_PROCESS,
                allowMultiple: false,
                defaultPriority: 0,
                returnMode: HookReturnMode::SINGLE,
            );

        }else {
            $this->hookRegistry->configureHook(
                Hook::BEFORE_PAYMENT_PROCESS,
                allowMultiple: false,
                defaultPriority: 0,
                returnMode: HookReturnMode::SINGLE,
                strictContracts: [BeforePaymentProcessContract::class]
            );
        }

        $this->hookRegistry->register(
            Hook::BEFORE_PAYMENT_PROCESS,
            $hook,
        );
    }

    /**
     * Register a hook to execute after a successful payment.
     *
     * @param string|callable|AfterPaymentSuccessContract $hook Hook callback, class name, or instance
     * @return void
     */
    public function onAfterPaymentSuccess(string|callable|AfterPaymentSuccessContract $hook): void
    {
        if(is_callable($hook)){
            $this->hookRegistry->configureHook(
                Hook::AFTER_PAYMENT_SUCCESS,
                allowMultiple: false,
                defaultPriority: 0,
                returnMode: HookReturnMode::SINGLE,
            );
        }else{
            $this->hookRegistry->configureHook(
                Hook::AFTER_PAYMENT_SUCCESS,
                allowMultiple: false,
                defaultPriority: 0,
                returnMode: HookReturnMode::SINGLE,
                strictContracts: [AfterPaymentSuccessContract::class]
            );
        }

        $this->hookRegistry->register(
            Hook::AFTER_PAYMENT_SUCCESS,
            $hook,
        );
    }

    /**
     * Register a hook to execute after a failed payment.
     *
     * @param string|callable|AfterPaymentFailedContract $hook Hook callback, class name, or instance
     * @return void
     */
    public function onAfterPaymentFailed(string|callable|AfterPaymentFailedContract $hook): void
    {
        if(is_callable($hook)){
            $this->hookRegistry->configureHook(
                Hook::AFTER_PAYMENT_FAILED,
                allowMultiple: false,
                defaultPriority: 0,
                returnMode: HookReturnMode::SINGLE,
            );
        }else{
            $this->hookRegistry->configureHook(
                Hook::AFTER_PAYMENT_FAILED,
                allowMultiple: false,
                defaultPriority: 0,
                returnMode: HookReturnMode::SINGLE,
                strictContracts: [AfterPaymentFailedContract::class]
            );
        }

        $this->hookRegistry->register(
            Hook::AFTER_PAYMENT_FAILED,
            $hook,
        );
    }

    /**
     * Execute hooks after a successful payment.
     *
     * @param PaymentResult $dto Payment result
     * @return mixed Result returned by the hook
     */
    public function paymentSuccess(PaymentResult $dto): mixed
    {
        $this->ensureGatewayIsSelected();

        return $this->hookRegistry->execute(
            Hook::AFTER_PAYMENT_SUCCESS,
            $dto,
            $this->currentGateway->name()
        );
    }

    /**
     * Execute hooks after a failed payment.
     *
     * @param PaymentResult $dto Payment result
     * @return mixed Result returned by the hook
     */
    public function paymentFailed(PaymentResult $dto): mixed
    {
        $this->ensureGatewayIsSelected();

        return $this->hookRegistry->execute(
            Hook::AFTER_PAYMENT_FAILED,
            $dto,
            $this->currentGateway->name()
        );
    }

    /**
     * Ensure a payment gateway has been selected.
     *
     * @return void
     * @throws \RuntimeException if no gateway is selected
     */
    private function ensureGatewayIsSelected(): void
    {
        if (!$this->currentGateway) {
            throw new \RuntimeException("No gateway selected. Please call gateway() before proceeding.");
        }
    }
}