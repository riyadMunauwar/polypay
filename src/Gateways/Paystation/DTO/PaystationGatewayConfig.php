<?php

namespace Riyad\PolyPay\Gateways\Paystation\DTO;

use Riyad\PolyPay\DTO\BaseDTO;

class PaystationGatewayConfig extends BaseDTO
{
    public string $merchantId;
    public string $password;
    public bool $payWithCharge;
}