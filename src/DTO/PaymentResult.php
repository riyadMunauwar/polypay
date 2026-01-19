<?php

namespace Riyad\PolyPay\DTO;

class PaymentResult extends BaseDTO
{
    public ?string $gateway;
    public bool $success;
    public mixed $response;
    public ?string $message;
    public ?array $errors;
    public ?string $paymentUrl;
}