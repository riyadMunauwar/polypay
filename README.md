Perfect ✅ — we’ll keep all namespaces exactly as `Riyad\Polypay` in the documentation.

Here’s the **full Laravel-style, framework-agnostic, guided README.md** for your package:

---

````markdown
# Polypay – Framework-Agnostic Payment Abstraction for PHP
**Built for Bangladeshi gateways first. Flexible enough for the world.**

Polypay provides a clean, unified API for integrating multiple payment gateways in any PHP application. It is framework-agnostic, so you can use it in **vanilla PHP**, **Laravel**, **Symfony**, or any other framework. While initially focused on Bangladeshi gateways, it is flexible enough to support international gateways as well.

---

## Installation

Install Polypay using Composer:

```bash
composer require riyad/polypay
````

---

## Quick Start

Polypay uses **singleton registries** to manage gateways and hooks. You can initialize them once and use them anywhere in your application.

```php
<?php

use Riyad\Polypay\Gateways\GatewayRegistry;
use Riyad\Polypay\HookRegistry;
use Riyad\Polypay\PaymentManager;

// Initialize Hook Registry (singleton)
$hookRegistry = HookRegistry::init();

// Initialize Gateway Registry (singleton)
$registry = GatewayRegistry::init();

// Create the PaymentManager (central processor)
$paymentManager = new PaymentManager($registry, $hookRegistry);
```

Now `$paymentManager` is ready to manage payments, and you can also use `$registry` and `$hookRegistry` directly to register gateways and hooks.

---

## Registering a Payment Gateway

Polypay allows you to register any gateway dynamically.

```php
<?php

use Riyad\Polypay\Gateways\Giopay;

$registry->register('giopay', function() {
    return new Giopay();
}, [
    'displayName' => 'Giopay',
    'description' => 'Giopay Payment Gateway',
    'logoUrl' => 'https://example.com/giopay-logo.png'
]);
```

* **Name:** Unique identifier for the gateway (`giopay`)
* **Factory:** A callable returning an instance of a gateway
* **Meta:** Optional metadata like display name, description, or logo

To unregister a gateway:

```php
$registry->unregister('giopay');
```

Check if a gateway exists:

```php
if ($registry->has('giopay')) {
    // Gateway exists
}
```

Retrieve a gateway instance:

```php
$gateway = $registry->get('giopay');
```

---

## Using PaymentManager

Once a gateway is registered, you can select it and process payments.

```php
<?php

use Riyad\Polypay\DTO\Payment;

// Select the active gateway
$paymentManager->gateway('giopay');

// Prepare payment DTO
$payment = new Payment([
    'amount' => 1000,
    'currency' => 'BDT',
    'customerId' => '12345',
]);

// Process the payment
$result = $paymentManager->pay($payment);

// $result is an instance of PaymentResult
echo $result->transactionId;
```

---

## Working with Hooks

Polypay supports hooks **before payment processing**, **after success**, and **after failure**.

### Before Payment Processing

```php
$paymentManager->onBeforePaymentProcess(function ($payment, $gatewayName) {
    // Modify payment or log details before processing
    $payment->extraNote = "Processing via {$gatewayName}";
    return $payment;
});
```

### After Payment Success

```php
$paymentManager->onAfterPaymentSuccess(function ($paymentResult, $gatewayName) {
    // Send confirmation email, log success
    error_log("Payment succeeded: {$paymentResult->transactionId}");
});
```

### After Payment Failed

```php
$paymentManager->onAfterPaymentFailed(function ($paymentResult, $gatewayName) {
    // Notify customer, retry, or log failure
    error_log("Payment failed: {$paymentResult->transactionId}");
});
```

Hooks are executed automatically when calling `$paymentManager->pay()`.

---

## Utilities

### Map over gateways

```php
$gatewayNames = $paymentManager->map(function ($gateway) {
    return $gateway->name();
});
```

### Filter gateways

```php
$bdGateways = $paymentManager->filter(function ($gateway) {
    return $gateway->config()->currency === 'BDT';
});
```

---

## Extending Polypay

You can create your own gateway by extending `AbstractGateway`:

```php
<?php

use Riyad\Polypay\AbstractGateway;
use Riyad\Polypay\DTO\Config;
use Riyad\Polypay\DTO\Payment;
use Riyad\Polypay\DTO\PaymentResult;

class MyGateway extends AbstractGateway
{
    public function name(): string
    {
        return 'mygateway';
    }

    public function config(): Config
    {
        return new Config([
            'displayName' => 'My Gateway',
            'description' => 'Custom payment gateway',
            'logoUrl' => 'https://example.com/logo.png',
        ]);
    }

    public function pay(Payment $dto): PaymentResult
    {
        // Process payment logic
        return new PaymentResult([
            'id' => uniqid(),
            'customerId' => $dto->customerId,
            'transactionId' => rand(1000, 9999),
            'gateway' => $this->name(),
        ]);
    }
}
```

Register your custom gateway like any other:

```php
$registry->register('mygateway', function() {
    return new MyGateway();
});
```

---

## Accessing Singleton Instances Anywhere

Because Polypay uses singletons for registries, you can access them from anywhere in your app:

```php
$registry     = GatewayRegistry::instance();
$hookRegistry = HookRegistry::instance();
$paymentManager = PaymentManager::instance();
```

This ensures you always work with the **same gateways and hooks** across your app.

---

## Summary

Polypay provides:

* **Unified payment API** — process multiple gateways seamlessly
* **Flexible gateway registration** — plug in any gateway dynamically
* **Hook system** — before, after success, after failure
* **Framework-agnostic** — works in Laravel, Symfony, or vanilla PHP
* **Bangladesh-first but globally extensible**

> Start simple: register a gateway, select it, pay, and hook in custom logic — all from one clean API.

---

## License

MIT License © Riyad

```

---

This README is:

- **Guided & Laravel-style** with inline comments  
- **Framework-agnostic** (works in any PHP project)  
- **Shows full lifecycle**: installation, gateway registration, hooks, payment processing, and extension  

---

If you want, I can **also create a “Quick Example” snippet** for GitHub’s top section that shows a full payment flow in **just 5–6 lines** — perfect for developers who skim docs.  

Do you want me to add that?
```
