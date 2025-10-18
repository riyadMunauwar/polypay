<?php 

namespace Riyad\Polypay\Gateways;

use Riyad\Polypay\AbstractGateway;
use Riyad\Polypay\Contracts\SupportPaymentVerification;
use Riyad\Polypay\DTO\Config;
use Riyad\Polypay\DTO\PaymentResult;
use Riyad\Polypay\DTO\BaseDTO;
use Riyad\Polypay\GatewayRegistry;

class Paystation extends AbstractGateway implements SupportPaymentVerification
{
    private string $baseUrl;
    private string $merchantId;
    private string $password;
    private string $callbackUrl;
    private string $payWithCharge;


    public function __construct()
    {
        $paystationGateway = GatewayRegistry::instance();
        $metaData = $paystationGateway->getMeta($this->name())['config'];

        $this->baseUrl = 'https://api.paystation.com.bd';
        $this->merchantId = $metaData->merchantId;
        $this->password = $metaData->password;
        $this->callbackUrl = $metaData->callbackUrl;
        $this->payWithCharge = $metaData->payWithCharge;
    }


    public function name(): string 
    {
        return 'paystation';
    }


    public function config(): Config 
    {
        return new Config([
            'displayName' => 'Giopay',
            'description' => 'Description',
            'logoUrl' => 'logoUrl',
        ]);
    }


    public function pay(BaseDTO $dto): PaymentResult 
    {
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
            'callback_url'    => $this->callbackUrl, 
            'checkout_items'  => $dto->checkoutItems,
            'opt_a'           => $dto->optionA,
            'opt_b'           => $dto->optionB,
            'opt_c'           => $dto->optionC,
            'emi'             => $dto->emi ?? 0,
        ];

        try {
                         
            $response = $this->initiatePayment($paymentData);

            if($response['status_code'] != 200 && !$response['payment_url'] ?? false){
                return new PaymentResult([
                    'success' => false,
                    'message' => 'Failed to created payment links',
                    'gatewayResponse' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            return new PaymentResult([
                'success' => true,
                'message' => 'Successfully payment link created',
                'gatewayResponse' => $response,
                'paymentUrl' => $response['payment_url'],
                'gateway' => $this->name(),
            ]);

        } catch(\RuntimeException $ex) {
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


    public function verify(BaseDTO $dto, string $gateway) : bool 
    {
        return true;
    }

    
    private function initiatePayment(array $paymentData): array
    {
        $url = $this->baseUrl . '/initiate-payment';

        $curl = curl_init();

        if (!$curl) {
            throw new \RuntimeException('Failed to initialize cURL.');
        }

        // Convert POST fields to URL-encoded format if needed
        $postFields = http_build_query($paymentData);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // Set a reasonable timeout
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new \RuntimeException("cURL request failed: {$curlError}");
        }

        // Decode JSON response safely
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode JSON response: ' . json_last_error_msg());
        }

        // Check HTTP status code
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("API request failed with status {$httpCode}: " . ($decodedResponse['message'] ?? $response));
        }

        return $decodedResponse;
    }


    private function verifyTransaction(array $data): array
    {
        $url = $this->baseUrl . '/transaction-status';

        $curl = curl_init();

        if (!$curl) {
            throw new \RuntimeException('Failed to initialize cURL.');
        }

        // Convert POST fields to URL-encoded format if needed
        $postFields = http_build_query($data);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // Set a reasonable timeout
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl); 

        if ($response === false) {
            throw new \RuntimeException("cURL request failed: {$curlError}");
        }

        // Decode JSON response safely
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode JSON response: ' . json_last_error_msg());
        }

        // Check HTTP status code
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("API request failed with status {$httpCode}: " . ($decodedResponse['message'] ?? $response));
        }

        return $decodedResponse;
    }
}