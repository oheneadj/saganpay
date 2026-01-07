<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Services\Hubtel\HubtelClient;
use Illuminate\Support\Facades\Log;

class HubtelProvider implements PaymentProviderInterface
{
    protected HubtelClient $client;
    
    /**
     * Service Mapping
     * Key: Service Type (used in SaganPay)
     * Value: Hubtel Service ID (from documentation)
     */
    protected const SERVICES = [
        'ECG_Prepaid' => 'e6d6bac062b5499cb1ece1ac3d742a84',
        'ECG_Postpaid' => 'e6d6bac062b5499cb1ece1ac3d742a84', // Shared ID for now
        'Ghana_Water_Postpaid' => '6c1e8a82d2e84feeb8bfd6be2790d71d',
        'DSTV' => '297a96656b5846ad8b00d5d41b256ea7',
        'GOTV' => 'e6ceac7f3880435cb30b048e9617eb41',
    ];

    public function __construct(HubtelClient $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(array $data): array
    {
        $serviceType = $data['service_type'] ?? 'ECG_Prepaid';
        $serviceId = self::SERVICES[$serviceType] ?? self::SERVICES['ECG_Prepaid'];
        $clientReference = $data['client_reference'] ?? uniqid('SP-');

        // Persist the transaction attempt (idempotent)
        \App\Models\Transaction::firstOrCreate(
            ['client_reference' => $clientReference],
            [
                'account_number' => $data['account_number'],
                'service_type' => $serviceType,
                'amount' => (float) $data['amount'],
                'customer_name' => $data['customer_name'] ?? 'SaganPay User',
                'mobile_number' => $data['mobile_number'],
                'email' => $data['email'],
                'status' => 'pending',
            ]
        );

        $payload = [
            'Destination' => $data['mobile_number'],
            'Amount' => (float) $data['amount'],
            'Channel' => $data['channel'] ?? 'mobilemoney',
            'CallbackUrl' => route('payment.callback'),
            'ClientReference' => $clientReference,
            'Extradata' => $this->getExtraData($serviceType, $data),
        ];

        return $this->client->post('/' . $serviceId, $payload);
    }

    /**
     * Prepare service-specific Extradata
     */
    protected function getExtraData(string $serviceType, array $data): array
    {
        $extraData = [
            'bundle' => $data['account_number'],
        ];

        // Specific requirements for Ghana Water
        if ($serviceType === 'Ghana_Water_Postpaid') {
            $extraData['Email'] = $data['email'];
            $extraData['SessionId'] = uniqid(); // Typically generated from a meter query, using placeholder.
        }

        return $extraData;
    }

    /**
     * {@inheritdoc}
     */
    public function checkStatus(string $clientReference): array
    {
        return $this->client->get('/transactionstatus/' . $clientReference);
    }
}
