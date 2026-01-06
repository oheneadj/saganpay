<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Services\Hubtel\HubtelClient;

class ECGProvider implements PaymentProviderInterface
{
    protected HubtelClient $client;
    
    // Hubtel ECG Service ID from documentation image
    protected string $serviceId = 'e6d6bac062b5499cb1ece1ac3d742a84';

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
            'Destination' => $data['mobile_number'],
            'Amount' => (float) $data['amount'],
            'Channel' => $data['channel'] ?? 'mobilemoney',
            'CallbackUrl' => route('payment.callback'),
            'ClientReference' => $clientReference,
            'Extradata' => [
                'bundle' => $data['account_number'],
            ],
        ];

        return $this->client->post('/' . $this->serviceId, $payload);
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
