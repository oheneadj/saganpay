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
        // Normalize service type for mapping
        $normalizedService = match($serviceType) {
            'Ghana_Water' => 'Ghana_Water_Postpaid',
            default => $serviceType
        };
        
        $serviceId = self::SERVICES[$normalizedService] ?? self::SERVICES['ECG_Prepaid'];
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
                'user_id' => $data['user_id'] ?? null,
            ]
        );

        // Build Payload based on normalized service type
        $payload = match ($normalizedService) {
            'Ghana_Water_Postpaid' => $this->buildWaterPayload($data, $clientReference),
            'DSTV', 'GOTV' => $this->buildTVPayload($data, $clientReference),
            default => $this->buildECGPayload($data, $clientReference), // Default to ECG logic
        };

        return $this->client->post('/' . $serviceId, $payload);
    }

    private function buildWaterPayload(array $data, string $clientReference): array
    {
        // 1. Query for SessionId
        $serviceId = self::SERVICES['Ghana_Water_Postpaid'];
        // Use provided mobile number or fallback to a default if needed (though API arguably needs a real number)
        $mobileNumber = $data['mobile_number'] ?? '0540000000'; 
        
        $queryResponse = $this->client->queryCommissionService(
            $serviceId,
            $data['account_number'],
            $mobileNumber
        );

        $sessionId = null;
        // Parse response to find sessionId
        // Response format usually has 'Data' array with Name/Value pairs
        if (isset($queryResponse['Data']) && is_array($queryResponse['Data'])) {
            // Sometimes it's a direct key, sometimes a list. Based on standard Hubtel calls, likely a list of objects.
            // Documentation implies we need to find the specific field.
            // Let's assume the standard Name/Value structure or check for direct key if 'Display' logic holds.
            // Adjusting based on common Hubtel patterns:
            foreach ($queryResponse['Data'] as $item) {
                // Check if this item is the Session ID. 
                // The prompt mentioned: "where Display === sessionId"
                if ((isset($item['Display']) && $item['Display'] === 'sessionId') || (isset($item['Name']) && $item['Name'] === 'sessionId')) {
                    $sessionId = $item['Value'] ?? null;
                    break;
                }
            }
        }

        if (!$sessionId) {
            Log::warning('Hubtel: Failed to retrieve SessionId for Ghana Water', ['response' => $queryResponse]);
            // We throw an exception here because without a SessionId, the payment IS GUARANTEED to fail.
            // The UI should catch this or we handle it gracefully.
            throw new \Exception('Could not validate Meter Number. Please check the number and try again.');
        }

        return [
            'Destination' => $data['account_number'], // Meter Number
            'Amount' => (float) $data['amount'],
            'CallbackUrl' => route('payment.callback'),
            'ClientReference' => $clientReference,
            'Extradata' => [
                'bundle' => $data['account_number'],
                'Email' => $data['email'],
                'SessionId' => $sessionId,
            ],
        ];
    }

    private function buildECGPayload(array $data, string $clientReference): array
    {
        return [
            'Destination' => $data['mobile_number'], // Mobile Number linked to meter
            'Amount' => (float) $data['amount'],
            'CallbackUrl' => route('payment.callback'),
            'ClientReference' => $clientReference,
            'Extradata' => [
                'bundle' => $data['account_number'], // Meter Number goes here
            ],
        ];
    }

    private function buildTVPayload(array $data, string $clientReference): array
    {
        return [
            'Destination' => $data['account_number'], // Account Number
            'Amount' => (float) $data['amount'],
            'CallbackUrl' => route('payment.callback'),
            'ClientReference' => $clientReference,
            // TV services typically don't require complex Extradata like ECG/Water in this context,
            // but we can ensure it's handled if needed. Based on prompt, only Extradata mentioned for ECG/Water.
            // However, looking at the TV example in the prompt, there is NO Extradata field.
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function checkStatus(string $clientReference): array
    {
        return $this->client->checkTransactionStatus($clientReference);
    }
}
