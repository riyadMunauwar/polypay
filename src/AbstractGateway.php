<?php

namespace Riyad\PolyPay;

use Riyad\PolyPay\Contracts\GatewayContract;
use Riyad\PolyPay\DTO\BaseDTO;
use Riyad\PolyPay\DTO\Config;
use Riyad\PolyPay\DTO\PaymentResult;
use Riyad\PolyPay\DTO\VerificationResult;
use Riyad\PolyPay\Exceptions\UnsupportedFeatureException;

abstract class AbstractGateway implements GatewayContract
{
    abstract public function name(): string;

    abstract public function config(): Config;


    public function pay(BaseDTO $dto): PaymentResult
    {
        $className = get_class($this);

        throw new UnsupportedFeatureException("'{$className}' does not support this pay() method.");
    }

    
    public function verify(BaseDTO $dto) : VerificationResult
    {
        $className = get_class($this);

        throw new UnsupportedFeatureException("'{$className}' does not support this verify() method.");
    }
}