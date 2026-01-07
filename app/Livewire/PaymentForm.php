<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Services\Payments\HubtelProvider;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PaymentForm extends Component
{
    public string $state = 'form';
    public array $formData = [
        'account_number' => '',
        'service_type' => '',
        'amount' => '',
        'customer_name' => '',
        'mobile_number' => '',
        'email' => ''
    ];

    public string $transactionId = '';
    public string $paymentDate = '';
    public string $paymentTime = '';
    public string $clientReference = '';
    public string $errorMessage = 'An unexpected error occurred. Please try again later.';

    protected array $errorMessages = [
        '2000' => 'Transaction failed. Please try again.',
        '2001' => 'The account number is invalid, or the transaction session has expired.',
        '4000' => 'A system error occurred. Please verify your details and try again.',
        '4010' => 'Information provided is incomplete or invalid. Please check and try again.',
        '4101' => 'Could not verify your account. Please ensure the meter/account number is correct.',
        'default' => 'Payment could not be completed at this time. Please check your connection or contact your bank.'
    ];

    protected function rules(): array
    {
        return [
            'formData.account_number' => 'required|string',
            'formData.service_type' => 'required|string|in:ECG_Prepaid,ECG_Postpaid,Ghana_Water_Postpaid,Ghana_Water,DSTV,GOTV',
            'formData.amount' => 'required|numeric|min:1',
            'formData.customer_name' => 'required|string',
            'formData.mobile_number' => ['required', 'string', 'regex:/^(0\d{9}|233\d{9}|\d{9})$/'],
            'formData.email' => 'required|email',
        ];
    }

    protected function messages(): array
    {
        return [
            'formData.account_number.required' => 'Please enter your meter/account number',
            'formData.service_type.required' => 'Please select a service type',
            'formData.amount.required' => 'Please enter the amount',
            'formData.amount.min' => 'Please enter a valid amount (minimum GHS 1.00)',
            'formData.customer_name.required' => 'Please enter your name',
            'formData.mobile_number.required' => 'Please enter your mobile number',
            'formData.mobile_number.regex' => 'Please enter a valid Ghana mobile number (e.g., 0501234567, 233501234567, or 501234567)',
            'formData.email.required' => 'Please enter your email address',
            'formData.email.email' => 'Please enter a valid email address',
        ];
    }

    public function mount()
    {
        $this->formData['service_type'] = 'ECG_Prepaid';
    }

    protected function formatPhoneNumber($phone): string
    {
        $cleanPhone = preg_replace('/\D/', '', $phone);
        
        if (str_starts_with($cleanPhone, '0') && strlen($cleanPhone) === 10) {
            return '233' . substr($cleanPhone, 1);
        } elseif (str_starts_with($cleanPhone, '233') && strlen($cleanPhone) === 12) {
            return $cleanPhone;
        } elseif (strlen($cleanPhone) === 9) {
            return '233' . $cleanPhone;
        }
        
        return $cleanPhone; // Fallback to clean phone if format is unknown (validation should catch this though)
    }

    public function submitForm()
    {
        Log::info('submitForm entered', ['formData' => $this->formData]);
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstErrorField = array_key_first($e->errors());
            $this->dispatch('focus-error', field: $firstErrorField);
            throw $e;
        }
        
        $this->state = 'processing';
        $this->clientReference = uniqid('SP-');

        // Format phone for Hubtel immediately for UI consistency
        $this->formData['mobile_number'] = $this->formatPhoneNumber($this->formData['mobile_number']);

        $this->dispatch('payment-submitted')->self();
    }

    /**
     * Triggered via event to ensure UI state changes first
     */
    #[\Livewire\Attributes\On('payment-submitted')]
    public function initiatePayment(HubtelProvider $provider)
    {
        if ($this->state !== 'processing' || !$this->clientReference) {
            return;
        }

        // Double check transaction hasn't already been created by a fast callback
        $existing = Transaction::where('client_reference', $this->clientReference)->first();
        if ($existing && $existing->status !== 'pending') {
            $this->handleTransactionUpdate($existing);
            return;
        }

        $response = $provider->pay(array_merge($this->formData, [
            'client_reference' => $this->clientReference,
            'user_id' => Auth::id(),
        ]));

        Log::info('Hubtel Payment Initialized', [
            'reference' => $this->clientReference, 
            'formatted_phone' => $this->formData['mobile_number'],
            'response' => $response
        ]);

        if (isset($response['ResponseCode'])) {
            if ($response['ResponseCode'] === '0001' || $response['ResponseCode'] === '0000') {
                return;
            }
            // Use specific message if available, otherwise use code mapping
            $this->markAsFailed($response['ResponseCode'], $response['Message'] ?? null);
            return;
        }

        $this->markAsFailed();
    }

    /**
     * Polled by Livewire to check if the transaction is finished.
     */
    public function pollTransactionStatus()
    {
        if ($this->state !== 'processing' || !$this->clientReference) {
            return;
        }

        $transaction = Transaction::where('client_reference', $this->clientReference)->first();

        // 5-minute fallback logic from diagram
        if ($transaction && $transaction->status === 'pending' && $transaction->created_at->diffInMinutes(now()) >= 5) {
            $this->performManualStatusCheck($transaction);
            return;
        }

        if ($transaction) {
            $this->handleTransactionUpdate($transaction);
        }
    }

    protected function performManualStatusCheck(Transaction $transaction)
    {
        $provider = app(HubtelProvider::class);
        $statusResp = $provider->checkStatus($transaction->client_reference);
        
        Log::info('Manual Status Check performed', ['reference' => $transaction->client_reference, 'response' => $statusResp]);
        
        // Logic to update transaction based on statusResp would go here
        // For now, we assume if we reached here without a callback, we should re-verify
    }

    protected function handleTransactionUpdate(Transaction $transaction)
    {
        if ($transaction->status === 'success') {
            $this->handleSuccess($transaction);
        } elseif ($transaction->status === 'failed') {
            // Extract code and description from callback data if available
            $code = $transaction->response_data['ResponseCode'] ?? null;
            $description = $transaction->response_data['Data']['Description'] ?? ($transaction->response_data['Message'] ?? null);
            
            $this->markAsFailed($code, $description);
        }
    }

    protected function handleSuccess(Transaction $transaction)
    {
        $this->transactionId = $transaction->hubtel_transaction_id ?? 'N/A';
        $this->paymentDate = $transaction->completed_at?->format('M d, Y') ?? now()->format('M d, Y');
        $this->paymentTime = $transaction->completed_at?->format('h:i A') ?? now()->format('h:i A');
        $this->state = 'success';
    }

    protected function markAsFailed(?string $code = null, ?string $customMessage = null)
    {
        $this->state = 'failed';
        
        if ($customMessage) {
            $this->errorMessage = $customMessage;
        } else {
            $this->errorMessage = $this->errorMessages[$code] ?? $this->errorMessages['default'];
        }

        // Only update DB if we have a record
        if ($this->clientReference) {
            Transaction::where('client_reference', $this->clientReference)->update(['status' => 'failed']);
        }
    }

    public function resetForm()
    {
        $this->reset(['formData', 'state', 'transactionId', 'paymentDate', 'paymentTime', 'clientReference']);
        $this->formData['service_type'] = 'ECG Prepaid';
    }

    public function tryAgain()
    {
        $this->state = 'form';
        $this->clientReference = '';
    }

    public function render()
    {
        return view('livewire.payment-form');
    }
}
