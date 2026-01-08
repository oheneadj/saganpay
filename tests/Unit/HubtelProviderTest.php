<?php

namespace Tests\Unit;

use App\Services\Hubtel\HubtelClient;
use App\Services\Payments\HubtelProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class HubtelProviderTest extends TestCase
{
    use RefreshDatabase;
    public function test_pay_ghana_water_queries_session_id_and_pays()
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $clientMock = Mockery::mock(HubtelClient::class);
        $provider = new HubtelProvider($clientMock);

        $data = [
            'service_type' => 'Ghana_Water',
            'account_number' => '1234567890',
            'amount' => 100,
            'mobile_number' => '0541234567',
            'email' => 'test@example.com',
            'client_reference' => 'REF123',
            'user_id' => $user->id
        ];

        // 1. Expect queryCommissionService call
        $clientMock->shouldReceive('queryCommissionService')
            ->once()
            ->with(
                '6c1e8a82d2e84feeb8bfd6be2790d71d', // Ghana Water ID
                '1234567890',
                '0541234567'
            )
            ->andReturn([
                'ResponseCode' => '0000',
                'Data' => [
                    ['Name' => 'accountName', 'Value' => 'John Doe'],
                    ['Name' => 'sessionId', 'Value' => 'SESSION_XYZ_123'],
                    ['Display' => 'sessionId', 'Value' => 'SESSION_XYZ_123'] // Providing both for robustness
                ]
            ]);

        // 2. Expect final payment post call with correct SessionId
        $clientMock->shouldReceive('post')
            ->once()
            ->withArgs(function ($endpoint, $payload) {
                return $endpoint === '/6c1e8a82d2e84feeb8bfd6be2790d71d' &&
                       $payload['Destination'] === '1234567890' &&
                       $payload['Extradata']['SessionId'] === 'SESSION_XYZ_123';
            })
            ->andReturn(['ResponseCode' => '0000']);

        // Act
        $result = $provider->pay($data);

        // Assert
        $this->assertEquals(['ResponseCode' => '0000'], $result);
    }
}
