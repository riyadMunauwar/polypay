<?php

namespace Riyad\Polypay\DTO;

class Payment extends BaseDTO
{
    public string $id;
    public string $customerId;
    public string $amount;
    public string $currency;
    public string $phonenumber;
    public ?string  $email;
}