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
        if ($normalizedService === 'Ghana_Water_Postpaid') {
             try {
                 $payload = $this->buildWaterPayload($data, $clientReference);
             } catch (\Exception $e) {
                 return ['ResponseCode' => 'Error', 'Message' => $e->getMessage()];
             }
        } else if ($normalizedService === 'DSTV' || $normalizedService === 'GOTV') {
             $payload = $this->buildTVPayload($data, $clientReference);
        } else {
             $payload = $this->buildECGPayload($data, $clientReference);
        }

        return $this->client->post('/' . $serviceId, $payload);
    }

    private function buildWaterPayload(array $data, string $clientReference): array
    {
        try {
            // 1. Check if SessionId is already provided (from validation step)
            $sessionId = $data['session_id'] ?? null;

            if (!$sessionId) {
                // Fallback: Query for SessionId if not provided
                $serviceId = self::SERVICES['Ghana_Water_Postpaid'];
                $mobileNumber = $data['mobile_number'] ?? '0540000000'; 
                
                $queryResponse = $this->client->queryCommissionService(
                    $serviceId,
                    $data['account_number'],
                    $mobileNumber
                );

                // Parse response to find sessionId
                if (isset($queryResponse['Data']) && is_array($queryResponse['Data'])) {
                    foreach ($queryResponse['Data'] as $item) {
                        if ((isset($item['Display']) && $item['Display'] === 'sessionId') || (isset($item['Name']) && $item['Name'] === 'sessionId')) {
                            $sessionId = $item['Value'] ?? null;
                            break;
                        }
                    }
                }

                if (!$sessionId) {
                    Log::warning('Hubtel: Failed to retrieve SessionId for Ghana Water', ['response' => $queryResponse]);
                    throw new \Exception('Could not validate Meter Number. Please check the number and try again.');
                }
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
        } catch (\Exception $e) {
            Log::error('Hubtel Water Payload Error: ' . $e->getMessage());
            throw $e;
        }
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
     * Validate account details for services that support it (Water, DSTV, GoTV).
     * Returns an array with 'verified_name' and optionally 'session_id'.
     * Throws exception on validation failure.
     */
    public function validateAccount(string $serviceType, string $accountNumber, array $data = []): array
    {
        try {
            // Normalize service type
            $normalizedService = match($serviceType) {
                'Ghana_Water' => 'Ghana_Water_Postpaid',
                default => $serviceType
            };

            if (!isset(self::SERVICES[$normalizedService])) {
                throw new \Exception("Service not supported for validation.");
            }

            $serviceId = self::SERVICES[$normalizedService];
            
            // Ghana Water requires mobile number for validation
            // DSTV/GoTV only need account number
            $mobile = null;
            if ($normalizedService === 'Ghana_Water_Postpaid') {
                // Use the mobile number from form data if provided
                $mobile = $data['mobile_number'] ?? null;
                if (!$mobile) {
                    throw new \Exception('Mobile number is required to validate Ghana Water meter.');
                }
            }

            $response = $this->client->queryCommissionService($serviceId, $accountNumber, $mobile);

            if (isset($response['ResponseCode']) && $response['ResponseCode'] !== '0000') {
                 throw new \Exception($response['Message'] ?? 'Account validation failed.');
            }

            $data = $response['Data'] ?? [];
            if (!is_array($data)) {
                 throw new \Exception('Invalid response from provider.');
            }

            $result = [
                'verified_name' => null,
                'session_id' => null,
                'amount_due' => null
            ];

            foreach ($data as $item) {
                $itemKey = $item['Display'] ?? $item['Name'] ?? '';
                if ($itemKey === 'name' || $itemKey === 'Name') {
                    $result['verified_name'] = $item['Value'] ?? null;
                }
                if ($itemKey === 'sessionId' || $itemKey === 'SessionId') {
                    $result['session_id'] = $item['Value'] ?? null;
                }
                if ($itemKey === 'amountDue' || $itemKey === 'AmountDue') {
                    $result['amount_due'] = $item['Value'] ?? null;
                }
            }

            if (!$result['verified_name']) {
                 // Fallback if name not explicitly found but success (unlikely)
                 // Or maybe logic specific to service
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Account Validation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkStatus(string $clientReference): array
    {
        return $this->client->checkTransactionStatus($clientReference);
    }
}
