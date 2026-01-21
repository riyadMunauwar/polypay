<?php

namespace Riyad\PolyPay\DTO;

class VerificationResult extends BaseDTO
{
    public ?string $gateway = '';
    public bool $success = false;
    public ?string $message = '';
    public mixed $response = [];
}