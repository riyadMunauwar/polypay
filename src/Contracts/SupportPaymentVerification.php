<?php

namespace Riyad\Polypay\Contracts;

use Riyad\Polypay\DTO\BaseDTO;

/**
 * Interface SupportPaymentVerification
 *
 * Provides a contract for handling payment verification across different gateways.
 *
 * @package Riyad\Polypay\Contracts
 */
interface SupportPaymentVerification
{
    /**
     * Handle payment verification.
     *
     * @param PaymentVerification $dto Data Transfer Object containing payment verification details.
     *
     * @return bool Returns true if the payment is successfully verified, otherwise false.
     */
    public function verify(BaseDTO $dto): bool;
}