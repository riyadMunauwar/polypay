<?php 

namespace Riyad\Polypay\Gateways;

use Riyad\Polypay\AbstractGateway;
use Riyad\Polypay\DTO\Config;
use Riyad\Polypay\DTO\PaymentResult;
use Riyad\Polypay\DTO\Payment;

/**
 * Class Giopay
 *
 * Implementation of a payment gateway using the Giopay service.
 * This class extends the AbstractGateway and defines the required
 * methods for gateway identification, configuration, and payment processing.
 */
class Giopay extends AbstractGateway
{
    /**
     * Get the unique name of the gateway.
     *
     * This identifier is used internally to reference the gateway.
     *
     * @return string The unique key for this gateway
     */
    public function name(): string 
    {
        return 'giopay';
    }

    /**
     * Get the configuration details for this gateway.
     *
     * The returned Config DTO contains metadata used for display purposes
     * such as in admin panels or checkout selection screens.
     *
     * @return Config Configuration object containing gateway metadata
     */
    public function config(): Config 
    {
        return new Config([
            'displayName' => 'Giopay',
            'description' => 'Description',
            'logoUrl' => 'logoUrl',
        ]);
    }

    /**
     * Process a payment request using Giopay.
     *
     * Takes a Payment DTO as input and must return a PaymentResult DTO,
     * representing the outcome of the payment attempt.
     *
     * @param Payment $dto Input payment details
     * @return PaymentResult The result of the payment process
     */
    public function pay(Payment $dto): PaymentResult 
    {
        return new PaymentResult([
            'id' => 'id',
            'customerId' => 'sdfsdf',
            'transactionId' => '120',
            'gateway' => 'sdfsdfds',
        ]);
    }
}