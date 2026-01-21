<?php

namespace Riyad\PolyPay\Gateways\Paystation\DTO;

use Riyad\PolyPay\DTO\BaseDTO;

class PaystationVerificationDTO extends BaseDTO
{
    public string $transactionId = '';
}