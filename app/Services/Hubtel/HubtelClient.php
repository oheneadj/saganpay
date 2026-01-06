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
}
