<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Services\Hubtel\HubtelClient;

class ECGProvider implements PaymentProviderInterface
{
    protected HubtelClient $client;
    
    // Hubtel ECG Service ID from documentation image
    protected string $serviceId = 'e2cd8fbc64b74e2a865668a6d91c5c0b';

    public function __construct(HubtelClient $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(array $data): array
    {
        $clientReference = $data['client_reference'] ?? uniqid('SP-');

        // Persist the transaction attempt
        \App\Models\Transaction::create([
            'client_reference' => $clientReference,
            'account_number' => $data['account_number'],
            'service_type' => $data['service_type'] ?? 'ECG Prepaid',
            'amount' => (float) $data['amount'],
            'customer_name' => $data['customer_name'] ?? 'SaganPay User',
            'mobile_number' => $data['mobile_number'],
            'email' => $data['email'],
            'status' => 'pending',
        ]);

        $payload = [
            'CustomerName' => $data['customer_name'] ?? 'SaganPay User',
            'CustomerMsisdn' => $data['mobile_number'],
            'CustomerEmail' => $data['email'],
            'Channel' => $data['channel'] ?? 'mobilemoney',
            'Amount' => (float) $data['amount'],
            'PrimaryCallbackUrl' => route('payment.callback'),
            'Description' => 'ECG Payment for ' . $data['account_number'],
            'ClientReference' => $clientReference,
            'ServiceId' => $this->serviceId,
            'AccountNo' => $data['account_number'],
        ];

        return $this->client->post('/request', $payload);
    }

    /**
     * {@inheritdoc}
     */
    public function checkStatus(string $clientReference): array
    {
        return $this->client->get('/status', [
            'ClientReference' => $clientReference
        ]);
    }
}
