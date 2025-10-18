<?php

namespace Riyad\Polypay\DTO;

class PaystationDTO extends BaseDTO
{
    public string $invoiceNumber;
    public string $currency;
    public string $amount;
    public ?string $reference;
    public string $customerName;
    public string $customerPhone;
    public string $customerEmail;
    public ?string $customerAddress;
    public ?array $checkoutItems;
    public ?string $optionA;
    public ?string $optionB;
    public ?string $optionC;
    public ?int $emi;
}