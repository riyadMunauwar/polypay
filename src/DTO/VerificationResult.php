<?php

namespace Riyad\PolyPay\DTO;

class VerificationResult extends BaseDTO
{
    public ?string $gateway;
    public bool $success;
    public ?string $message;
}