<?php 

namespace Riyad\PolyPay\Gateways\Paystation;

use Riyad\PolyPay\AbstractGateway;
use Riyad\PolyPay\DTO\Config;
use Riyad\PolyPay\DTO\PaymentResult;
use Riyad\PolyPay\DTO\BaseDTO;
use Riyad\PolyPay\GatewayRegistry;
use Riyad\PolyPay\DTO\VerificationResult;
use Riyad\PolyPay\Client\Http;
use Riyad\PolyPay\Client\HttpException;
use Riyad\PolyPay\PayHook;
use Riyad\PolyPay\Constants\Hook;

class Paystation extends AbstractGateway
{
    private string $merchantId;
    private string $password;
    private string $payWithCharge;
    private Http $client;
    private PayHook $hook;


    public function __construct()
    {
        $registry = GatewayRegistry::instance();
        $metaData = $registry->getMeta($this->name())['config'];

        $this->client = new Http('https://api.paystation.com.bd', verifySsl: false);
        $this->hook = PayHook::instance();
        $this->merchantId = $metaData->merchantId;
        $this->password = $metaData->password;
        $this->payWithCharge = $metaData->payWithCharge;
    }


    public function name(): string 
    {
        return 'paystation';
    }


    public function config(): Config 
    {
        return new Config([
            'displayName' => 'Paystation',
            'description' => 'Description',
            'logoUrl' => 'logoUrl',
        ]);
    }


    public function pay(BaseDTO $dto): PaymentResult 
    {
        $dto = $this->hook->applyFilters(Hook::BEFORE_PAYMENT_PROCESS, $dto);

        $paymentData = [
            'merchantId'      => $this->merchantId,
            'password'        => $this->password,
            'invoice_number'  => $dto->invoiceNumber,
            'currency'        => $dto->currency,
            'payment_amount'  => $dto->amount,
            'pay_with_charge' => $this->payWithCharge,
            'reference'       => $dto->reference ?? '',
            'cust_name'       => $dto->customerName,
            'cust_phone'      => $dto->customerPhone,
            'cust_email'      => $dto->customerEmail,
            'cust_address'    => $dto->customerAddress ?? '',
            'callback_url'    => $dto->callbackUrl, 
            'checkout_items'  => $dto->checkoutItems,
            'opt_a'           => $dto->optionA,
            'opt_b'           => $dto->optionB,
            'opt_c'           => $dto->optionC,
            'emi'             => $dto->emi ?? 0,
        ];

        try {
                         
            $response =  $this->client->request(endpoint: '/initiate-payment', method: 'POST', data: $paymentData, contentType: 'application/x-www-form-urlencoded')->json();

            if($response['status_code'] != 200 && !$response['payment_url'] ?? false){
                return new PaymentResult([
                    'success' => false,
                    'message' => 'Failed to created payment links',
                    'response' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            $successDto = new PaymentResult([
                'success' => true,
                'message' => 'Successfully payment link created',
                'response' => $response,
                'paymentUrl' => $response['payment_url'],
                'gateway' => $this->name(),
            ]);

            $this->hook->doAction(Hook::AFTER_PAYMENT_PROCESS, $successDto);

            return $successDto;

        } catch(\HttpException $ex) {
            return new PaymentResult([
                'success' => false,
                'message' => $ex->getMessage(),
            ]);

        } catch (\Exception $ex) {
            return new PaymentResult([
                'success' => false,
                'message' => $ex->getMessage(),
            ]);

        }
        
    }


    public function verify(BaseDTO $dto) : VerificationResult 
    {
        $headers = [
            'merchantId' => $this->merchantId,
        ];

        $data = [
            'trxId' => $dto->transactionId,
        ];

        try {
                         
            $response = $this->client->request(endpoint: '/v2/transaction-status', method: 'POST', data: $data, headers: $headers, contentType: 'application/x-www-form-urlencoded')->json();

            if($response['status_code'] != 200){
                return new VerificationResult([
                    'success' => false,
                    'message' => 'Failed',
                    'response' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            return new VerificationResult([
                'success' => true,
                'message' => 'Transaction found',
                'response' => $response,
                'gateway' => $this->name(),
            ]);

        } catch(\HttpException $ex) {
            return new VerificationResult([
                'success' => false,
                'message' => $ex->getMessage(),
            ]);

        } catch (\Exception $ex) {
            return new VerificationResult([
                'success' => false,
                'message' => $ex->getMessage(),
            ]);

        }
    }

}