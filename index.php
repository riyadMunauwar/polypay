<?php 

require_once 'vendor/autoload.php';

use Riyad\Polypay\DTO\Config;
use Riyad\Polypay\DTO\PaystationDTO;
use Riyad\Polypay\DTO\PaymentResult;
use Riyad\Polypay\DTO\PaystationGatewayConfig;
use Riyad\Polypay\DTO\PaystationVerificationDTO;
use Riyad\Polypay\PaymentManager;
use Riyad\Polypay\GatewayRegistry;
use Riyad\Polypay\Gateways\Paystation;
use Riyad\Polypay\Contracts\BeforePaymentProcessContract;
use Riyad\Polypay\Contracts\AfterPaymentSuccessContract;
use Riyad\Polypay\Contracts\AfterPaymentFailedContract;
use Riyad\Polypay\Contracts\HookContract;
use Riyad\Polypay\Constants\HookReturnMode;
use Riyad\Polypay\HookRegistry;


$registry = GatewayRegistry::init();
$hookRegistry = HookRegistry::init();


$manager = PaymentManager::init($registry, $hookRegistry);

$manager->register('paystation', function(){
    return new Paystation();
}, ['config' => new PaystationGatewayConfig(['merchantId' => '1066-1746978236', 'password' => 'B@k$8236', 'callbackUrl' => 'Hello', 'payWithCharge' => true])]);


class CreateTransction implements AfterPaymentSuccessContract
{
    public function handle(PaymentResult $dto, string $gatewayName) : mixed
    {
        var_dump('Continue...');
        return $dto;
    }
}

$manager->onBeforePaymentProcess(function($dto, $gatewayName){
    var_dump('before_payment_process');

    return $dto;
});

$manager->onBeforePaymentProcess(function($dto, $gateway){
    return $dto;
});

$manager->onAfterPaymentSuccess(function($dto){
    var_dump('Success...');
});

$manager->onAfterPaymentFailed(function($dto){
    var_dump('Failed');
});

$payment = new PaystationDTO([
    'invoiceNumber'    => rand(10000, 3000000),
    'currency'          => 'BDT',
    'amount'            => (string) rand(100, 1000),
    'reference'         => (string) rand(100, 1000),
    'customerName'     => 'Riyad Munauwar',
    'customerPhone'    => '01794263387',
    'customerEmail'    => 'riyadtest@gmail.com',
    'customerAddress'  => 'Address: Dhaka, Mymensingh',
    'checkoutItems'    => [],
    'optionA'          => 'Hello',
    'optionB'          => 'Hello',
    'optionC'          => 'Hello',
    'emi'               => 0,
]);

// $res = $manager->gateway('paystation')->pay($payment);
$res = $manager->gateway('paystation')->verify(new PaystationVerificationDTO(['transactionId' => '$sdfsdf']));

var_dump($res);

// $manager->paymentSuccess(new PaymentResult([
//     'id' => 'id',
//     'customerId' => 'hello',
//     'transactionId' => 'tranctionid',
//     'gateway' => 'giopay',
// ]));

// $manager->gateway('nagad')->paymentFailed(new PaymentResult([
//     'success' => true,
// ]));




