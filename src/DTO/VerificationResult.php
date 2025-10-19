<?php

namespace Riyad\Polypay\DTO;

class VerificationResult extends BaseDTO
{
    public ?string $gateway;
    public bool $success;
    public ?string $message;
}