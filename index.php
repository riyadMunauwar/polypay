<?php 

require_once 'vendor/autoload.php';

use Riyad\PolyPay\DTO\Config;
use Riyad\PolyPay\Gateways\Paystation\DTO\PaystationDTO;
use Riyad\PolyPay\DTO\PaymentResult;
use Riyad\PolyPay\Gateways\Paystation\DTO\PaystationGatewayConfig;
use Riyad\PolyPay\Gateways\Paystation\DTO\PaystationVerificationDTO;
use Riyad\PolyPay\PaymentManager;
use Riyad\PolyPay\GatewayRegistry;
use Riyad\PolyPay\Gateways\Paystation\Paystation;
use Riyad\PolyPay\PayHook;
use Riyad\PolyPay\Constants\Hook;

$registry = GatewayRegistry::init();

$hook = PayHook::instance();

$hook->addFilter(Hook::BEFORE_PAYMENT_PROCESS, function($dto){
    var_dump('Before PROCESS');

    return $dto;
});



$hook->addAction(Hook::AFTER_PAYMENT_PROCESS, function($res) {
    var_dump('After process');
});

$hook->addAction(Hook::AFTER_PAYMENT_FAILED, function($gateway, $dto) {
    var_dump($gateway);
    var_dump($dto);
});

$hook->addAction(Hook::AFTER_PAYMENT_SUCCESS, function($gateway, $dto) {
    var_dump($gateway);
    var_dump($dto);
});


$manager = PaymentManager::init($registry);

$manager->register('paystation', function(){
    return new Paystation();
}, ['config' => new PaystationGatewayConfig(['merchantId' => '1066', 'password' => 'B@', 'payWithCharge' => true])]);


$payment = new PaystationDTO([
    'invoiceNumber'    => rand(10000, 3000000),
    'currency'          => 'BDT',
    'amount'            => (string) rand(100, 1000),
    'reference'         => (string) rand(100, 1000),
    'customerName'     => 'Riyad Munauwar',
    'customerPhone'    => '01794263387',
    'customerEmail'    => 'riyadtest@gmail.com',
    'customerAddress'  => 'Address: Dhaka, Mymensingh',
    'callbackUrl' => 'Hello',
    'checkoutItems'    => [],
    'optionA'          => 'Hello',
    'optionB'          => 'Hello',
    'optionC'          => 'Hello',
    'emi'               => 0,
]);

$res = $manager->gateway('paystation')->pay($payment);
// $res = $manager->gateway('paystation')->verify(new PaystationVerificationDTO(['transactionId' => '$sdfsdf']));

var_dump($res);

$manager->paymentSuccess('paystation', new PaymentResult([
    'success' => true,
    'message' => 'Success',
    'gateway' => 'giopay',
]));

$manager->paymentFailed('paystation', new PaymentResult([
    'success' => true,
]));




