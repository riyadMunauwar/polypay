<?php

namespace Riyad\Polypay\DTO;

class PaystationGatewayConfig extends BaseDTO
{
    public string $merchantId;
    public string $password;
    public string $callbackUrl;
    public bool $payWithCharge;
}