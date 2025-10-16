<?php 

require_once 'vendor/autoload.php';

use Riyad\Polypay\DTO\Config;
use Riyad\Polypay\DTO\Payment;
use Riyad\Polypay\DTO\PaymentResult;
use Riyad\Polypay\PaymentManager;
use Riyad\Polypay\GatewayRegistry;
use Riyad\Polypay\Gateways\Giopay;
use Riyad\Polypay\Contracts\BeforePaymentProcessContract;
use Riyad\Polypay\Contracts\AfterPaymentSuccessContract;
use Riyad\Polypay\Contracts\AfterPaymentFailedContract;
use Riyad\Polypay\Contracts\HookContract;
use Riyad\Polypay\Constants\HookReturnMode;
use Riyad\Polypay\HookRegistry;

// $registry = GatewayRegistry::init();



// // $registry->unregister('nagad');

// // $registry->clear();

// // $gateway = $registry->getMeta('nagad');

// $manager = PaymentManager::init($registry);

// $manager->register('giopay', function(){
//     return new Giopay();
// });

// $manager->register('aamarpay', function(){
//     return new Giopay();
// });

// $registry->unregister('aamarpay', function(){
//     return new Giopay();
// });

// $manager->register('nagad', function(){
//     return new Giopay();
// });

// $manager->register('nagad', function(){
//     return new Giopay();
// });

// // $filtered = $manager->map(function($g) {
// //     return [$g->name() => $g->config()->description];
// // });

// // print_r($filtered);

class CreateTransction implements AfterPaymentSuccessContract
{
    public function handle(PaymentResult $dto, string $gatewayName) : mixed
    {
        var_dump('Continue...');
        var_dump($gatewayName);

        return $dto;
    }
}

// $manager->onBeforePaymentProcess(CreateTransction::class);

// $manager->onBeforePaymentProcess(function($dto, $gateway){
//     var_dump('Continue...');
//     return $dto;
// });

// $manager->onAfterPaymentSuccess(function($dto){
//     var_dump($dto);
//     var_dump('Success...');
// });

// $manager->onAfterPaymentFailed(function($dto){
//     var_dump('Failed');
// });

// $payment = new Payment([
//     'id' => rand(1, 1000),
//     'customerId' => rand(100, 500),
//     'amount' => (string) rand(1, 50),
//     'currency' => 'BDT',
//     'phonenumber' => '01794263387',
//     'email' => '01794263387',
// ]);

// $manager->gateway('nagad')->pay($payment);

// $manager->paymentSuccess(new PaymentResult([
//     'id' => 'id',
//     'customerId' => 'hello',
//     'transactionId' => 'tranctionid',
//     'gateway' => 'giopay',
// ]));

$hookManager = HookRegistry::init();


// Configure 'after.payment.success' as single hook with return allowed
// $hookManager->configureHook(
//     'after.payment.success',
//     allowMultiple: false,
//     defaultPriority: 0,
//     returnMode: HookReturnMode::SINGLE,
//     strictContracts: [AfterPaymentSuccessContract::class]
// );

// Register by class name and declare contract(s)
$hookManager->register(
    'after.payment.success',
    CreateTransction::class,
    // 'Class',
    // priority: 10,
    contracts: [AfterPaymentSuccessContract::class]
);

$result = $hookManager->getHooks('after.payment.success');
// Execute
// $result = $hookManager->execute('after.payment.success', new PaymentResult(['id' => 'id', 'customerId' => 'isdfsd', 'transactionId' => 'sdfsdf', 'gateway' => 'giopay']), 'stripe');

var_dump($result);