<?php

namespace Riyad\Polypay\DTO;

class PaymentResult extends BaseDTO
{
    public string $id;
    public string $customerId;
    public string $transactionId;
    public string $gateway;
}