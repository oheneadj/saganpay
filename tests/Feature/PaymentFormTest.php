<?php

use App\Livewire\PaymentForm;
use App\Models\Transaction;
use App\Services\Payments\HubtelProvider;
use Livewire\Livewire;
use Illuminate\Support\Facades\Http;
use App\Services\Hubtel\HubtelClient;

beforeEach(function () {
    //
});

it('renders successfully', function () {
    Livewire::test(PaymentForm::class)
        ->assertStatus(200);
});

it('validates custom required messages', function () {
    Livewire::test(PaymentForm::class)
        ->call('submitForm')
        ->assertHasErrors(['formData.account_number' => 'required'])
        ->assertSee('Please enter your meter/account number');
});

it('transitions to processing state on valid submission', function () {
    // Mock Hubtel Response
    Http::fake([
        '*' => Http::response([
            'ResponseCode' => '0001',
            'Message' => 'Transaction pending.',
            'Data' => [
                'ClientReference' => 'SP-12345',
                'TransactionId' => 'HUB-123',
                'Amount' => 10.0
            ]
        ], 200)
    ]);

    Livewire::test(PaymentForm::class)
        ->set('formData.account_number', '12345678')
        ->set('formData.service_type', 'GOTV')
        ->set('formData.amount', 10)
        ->set('formData.customer_name', 'Test User')
        ->set('formData.mobile_number', '0241234567')
        ->set('formData.email', 'test@example.com')
        ->call('submitForm')
        ->assertSet('state', 'processing')
        ->assertSee('Processing Payment');
});

it('initiates payment successfully', function () {
    Http::fake(['*' => Http::response(['ResponseCode' => '0001'], 200)]);

    Livewire::test(PaymentForm::class)
        ->set('formData.account_number', '12345678')
        ->set('formData.amount', 10)
        ->set('formData.customer_name', 'Test User')
        ->set('formData.mobile_number', '233241234567')
        ->set('formData.email', 'test@example.com')
        ->set('clientReference', 'SP-INIT-123')
        ->set('state', 'processing')
        ->call('initiatePayment')
        ->assertSet('state', 'processing');
});

it('polls for transaction status and transitions to success', function () {
    $transaction = Transaction::create([
        'client_reference' => 'SP-POLL-123',
        'account_number' => '12345678',
        'service_type' => 'ECG_Prepaid',
        'amount' => 10.0,
        'customer_name' => 'Test User',
        'mobile_number' => '233241234567',
        'email' => 'test@example.com',
        'status' => 'pending',
    ]);

    $component = Livewire::test(PaymentForm::class)
        ->set('state', 'processing')
        ->set('clientReference', 'SP-POLL-123');
    
    // Still pending
    $component->call('pollTransactionStatus')
        ->assertSet('state', 'processing');

    // Update to success
    $transaction->update(['status' => 'success', 'hubtel_transaction_id' => 'HUB-123', 'completed_at' => now()]);

    $component->call('pollTransactionStatus')
        ->assertSet('state', 'success')
        ->assertSee('Payment Successful');
});

it('prevents multiple transactions for same reference', function () {
    Http::fake(['*' => Http::response(['ResponseCode' => '0001'], 200)]);
    
    // Create existing pending transaction
    Transaction::create([
        'client_reference' => 'SP-EXISTING-123',
        'account_number' => '12345678',
        'service_type' => 'ECG_Prepaid',
        'amount' => 10.0,
        'customer_name' => 'Test User',
        'mobile_number' => '233241234567',
        'email' => 'test@example.com',
        'status' => 'pending',
    ]);

    Livewire::test(PaymentForm::class)
        ->set('formData.account_number', '12345678')
        ->set('formData.amount', 10)
        ->set('state', 'processing')
        ->set('clientReference', 'SP-EXISTING-123')
        ->call('initiatePayment')
        ->assertSet('state', 'processing'); // Assert the state didn't change (still processing, but didn't crash)
});

it('formats phone number before submission', function () {
    Http::fake(['*' => Http::response(['ResponseCode' => '0001'], 200)]);

    Livewire::test(PaymentForm::class)
        ->set('formData.account_number', '12345678')
        ->set('formData.service_type', 'GOTV')
        ->set('formData.amount', 10)
        ->set('formData.customer_name', 'Test User')
        ->set('formData.mobile_number', '0241234567') // Should be formatted to 233241234567
        ->set('formData.email', 'test@example.com')
        ->call('submitForm')
        ->assertSet('formData.mobile_number', '233241234567')
        ->assertSet('formData.service_type', 'GOTV');
});

it('handles immediate failure from hubtel', function () {
    Http::fake([
        '*' => Http::response([
            'ResponseCode' => 'C005',
            'Message' => 'API Error'
        ], 200)
    ]);

     Livewire::test(PaymentForm::class)
        ->set('formData.account_number', '12345678')
        ->set('formData.amount', 10)
        ->set('formData.customer_name', 'Test User')
        ->set('formData.mobile_number', '0241234567')
        ->set('formData.email', 'test@example.com')
        ->call('submitForm')
        ->call('initiatePayment')
        ->assertSet('state', 'failed');
});

it('displays correct error message for registered account error (2001)', function () {
    Http::fake(['*' => Http::response(['ResponseCode' => '2001'], 200)]);

    Livewire::test(PaymentForm::class)
        ->set('formData.account_number', '12345678')
        ->set('formData.amount', 10)
        ->set('formData.customer_name', 'Test User')
        ->set('formData.mobile_number', '0241234567')
        ->set('formData.email', 'test@example.com')
        ->call('submitForm')
        ->call('initiatePayment')
        ->assertSet('state', 'failed')
        ->assertSet('errorMessage', 'The account number is invalid, or the transaction session has expired.');
});

it('displays correct error message for verification error (4101)', function () {
    Http::fake(['*' => Http::response(['ResponseCode' => '4101'], 200)]);

    Livewire::test(PaymentForm::class)
        ->set('formData.account_number', '12345678')
        ->set('formData.amount', 10)
        ->set('formData.customer_name', 'Test User')
        ->set('formData.mobile_number', '0241234567')
        ->set('formData.email', 'test@example.com')
        ->call('submitForm')
        ->call('initiatePayment')
        ->assertSet('state', 'failed')
        ->assertSet('errorMessage', 'Could not verify your account. Please ensure the meter/account number is correct.');
});

it('displays dynamic error descriptions from the transaction response data', function () {
    $transaction = Transaction::create([
        'client_reference' => 'SP-DYNAMIC-ERR',
        'account_number' => '12345678',
        'service_type' => 'ECG_Prepaid',
        'amount' => 10.0,
        'customer_name' => 'Test User',
        'mobile_number' => '233241234567',
        'email' => 'test@example.com',
        'status' => 'pending',
    ]);

    $component = Livewire::test(PaymentForm::class)
        ->set('state', 'processing')
        ->set('clientReference', 'SP-DYNAMIC-ERR');

    // Simulate callback update with specific description
    $transaction->update([
        'status' => 'failed',
        'response_data' => [
            'ResponseCode' => '2001',
            'Data' => ['Description' => 'Session Does not exist or has expired']
        ]
    ]);

    $component->call('pollTransactionStatus')
        ->assertSet('state', 'failed')
        ->assertSet('errorMessage', 'Session Does not exist or has expired')
        ->assertSee('Session Does not exist or has expired');
});

it('resets client reference on try again', function () {
    Livewire::test(PaymentForm::class)
        ->set('clientReference', 'SP-OLD-REF')
        ->set('state', 'failed')
        ->call('tryAgain')
        ->assertSet('state', 'form')
        ->assertSet('clientReference', '');
});

it('displays default error message for unknown error codes', function () {
    Http::fake(['*' => Http::response(['ResponseCode' => '9999'], 200)]);

    Livewire::test(PaymentForm::class)
        ->set('formData.account_number', '12345678')
        ->set('formData.amount', 10)
        ->set('formData.customer_name', 'Test User')
        ->set('formData.mobile_number', '0241234567')
        ->set('formData.email', 'test@example.com')
        ->call('submitForm')
        ->call('initiatePayment')
        ->assertSet('state', 'failed')
        ->assertSet('errorMessage', 'Payment could not be completed at this time. Please check your connection or contact your system administrator.');
});

it('associates transaction with logged in user', function () {
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    Http::fake([
        '*' => Http::response([
            'ResponseCode' => '0001',
            'Message' => 'Transaction pending.',
            'Data' => [
                'ClientReference' => 'SP-USER-TEST',
                'TransactionId' => 'HUB-USER-123',
                'Amount' => 10.0
            ]
        ], 200)
    ]);

    Livewire::test(PaymentForm::class)
        ->set('formData.account_number', '12345678')
        ->set('formData.service_type', 'ECG_Prepaid')
        ->set('formData.amount', 10)
        ->set('formData.customer_name', 'Auth User')
        ->set('formData.mobile_number', '0241234567')
        ->set('formData.email', 'auth@example.com')
        ->call('submitForm')
        ->call('initiatePayment');

    $transaction = \App\Models\Transaction::where('email', 'auth@example.com')->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->user_id)->toBe($user->id);
});

it('sends account number as destination in hubtel payload', function () {
    Http::fake([
        // Mock the Commission Service Query (first call)
        'https://cs.hubtel.com/*' => Http::response([
            'ResponseCode' => '0000', 
            'Data' => [
                ['Display' => 'sessionId', 'Value' => 'TEST_SESSION_ID']
            ]
        ], 200),
        // Mock the Payment Request (second call)
        '*' => Http::response(['ResponseCode' => '0001', 'Data' => ['TransactionId' => '123']], 200)
    ]);

    Livewire::test(PaymentForm::class)
        ->set('formData.account_number', 'GW-TEST-ACC') // Account Number
        ->set('formData.service_type', 'Ghana_Water_Postpaid')
        ->set('formData.amount', 50)
        ->set('formData.customer_name', 'Test User')
        ->set('formData.mobile_number', '0240000000') // Mobile Number
        ->set('formData.email', 'test@example.com')
        ->call('submitForm')
        ->call('initiatePayment');

    Http::assertSent(function ($request) {
        // Skip if it's the GET query to commission service
        if ($request->method() === 'GET') {
            return false;
        }
        return isset($request['Destination']) && $request['Destination'] === 'GW-TEST-ACC';
    });
});

it('validates account and transitions to step 2 for generic services', function () {
    // Mock Validation Response
    Http::fake([
        'https://cs.hubtel.com/*' => Http::response([
            'ResponseCode' => '0000',
            'Data' => [
                ['Display' => 'name', 'Value' => 'Test Customer'],
                ['Display' => 'sessionId', 'Value' => 'TEST_SESSION']
            ]
        ], 200)
    ]);

    Livewire::test(PaymentForm::class)
        ->set('formData.service_type', 'Ghana_Water_Postpaid')
        ->set('formData.account_number', 'GW-123')
        ->call('validateAccount')
        ->assertSet('step', 2)
        ->assertSet('verifiedName', 'Test Customer')
        ->assertSet('verificationSessionId', 'TEST_SESSION')
        ->assertSet('formData.customer_name', 'Test Customer');
});

it('skips validation for ECG services', function () {
    Livewire::test(PaymentForm::class)
        ->set('formData.service_type', 'ECG_Prepaid')
        ->assertSet('step', 2);
});

it('handles validation failure gracefully', function () {
    Http::fake([
        'https://cs.hubtel.com/*' => Http::response([
            'ResponseCode' => '2001',
            'Message' => 'Invalid Account'
        ], 200)
    ]);

    Livewire::test(PaymentForm::class)
        ->set('formData.service_type', 'DSTV')
        ->set('formData.account_number', 'INVALID-ACC')
        ->call('validateAccount')
        ->assertSet('step', 1)
        ->assertSet('verifiedName', '')
        ->assertSet('errorMessage', 'Invalid Account');
});
