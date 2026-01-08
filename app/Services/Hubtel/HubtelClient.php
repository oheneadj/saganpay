<?php

namespace App\Services\Hubtel;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubtelClient
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $merchantId;

    public function __construct()
    {
        $this->clientId = config('services.hubtel.HUBTEL_CLIENT_ID');
        $this->clientSecret = config('services.hubtel.HUBTEL_CLIENT_SECRET');
        $this->merchantId = config('services.hubtel.HUBTEL_MERCHANT_ID');
        $this->baseUrl = config('services.hubtel.HUBTEL_BASE_URL');
    }

    /**
     * Send a POST request to Hubtel API.
     */
    public function post(string $endpoint, array $data): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->post($this->baseUrl . $endpoint, $data);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Hubtel API POST Error: ' . $e->getMessage(), ['endpoint' => $endpoint, 'data' => $data]);
            return ['ResponseCode' => 'C005', 'Message' => $e->getMessage()];
        }
    }

    /**
     * Send a GET request to Hubtel API.
     */
    public function get(string $endpoint, array $query = []): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->get($this->baseUrl . $endpoint, $query);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Hubtel API GET Error: ' . $e->getMessage(), ['endpoint' => $endpoint, 'query' => $query]);
            return ['ResponseCode' => 'C005', 'Message' => $e->getMessage()];
        }
    }
    /**
     * Check transaction status via the separate status API.
     */
    public function checkTransactionStatus(string $clientReference): array
    {
        // Status API URL: https://api-txnstatus.hubtel.com/transactions/{POS_Sales_ID}/status
        $url = "https://api-txnstatus.hubtel.com/transactions/{$this->merchantId}/status";
        
        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->get($url, [
                    'clientReference' => $clientReference
                ]);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Hubtel Status Check Error: ' . $e->getMessage(), ['url' => $url]);
            return ['ResponseCode' => 'C005', 'Message' => $e->getMessage()];
        }
    }

    /**
     * Query commission service API (e.g. for Ghana Water bill status/SessionId).
     *
     * @param string $serviceId
     * @param string $destination
     * @param string $mobile
     * @return array
     */
    public function queryCommissionService(string $serviceId, string $destination, string $mobile): array
    {
        // This uses a different base URL: https://cs.hubtel.com/commissionservices/{merchantId}/{serviceId}
        $url = "https://cs.hubtel.com/commissionservices/{$this->merchantId}/{$serviceId}";

        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->get($url, [
                    'destination' => $destination,
                    'mobile' => $mobile, // The user's phone number or the payer's phone number
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Hubtel Commission Service Query Failed:', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['ResponseCode' => 'Error', 'Message' => 'Request failed with status ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('Hubtel Commission Service Query Exception: ' . $e->getMessage());
            return ['ResponseCode' => 'Exception', 'Message' => $e->getMessage()];
        }
    }
}
