<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Services\Payments\HubtelProvider;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

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

    protected function rules(): array
    {
        return [
            'formData.account_number' => 'required|string',
            'formData.service_type' => 'required|string|in:ECG_Prepaid,ECG_Postpaid,Ghana_Water_Postpaid,Ghana_Water',
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

    public function submitForm(HubtelProvider $provider)
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstErrorField = array_key_first($e->errors());
            $this->dispatch('focus-error', field: $firstErrorField);
            throw $e;
        }
        
        $this->state = 'processing';
        $this->clientReference = uniqid('SP-');

        // Format phone for Hubtel
        $originalPhone = $this->formData['mobile_number'];
        $this->formData['mobile_number'] = $this->formatPhoneNumber($originalPhone);

        $response = $provider->pay(array_merge($this->formData, [
            'client_reference' => $this->clientReference
        ]));

        Log::info('Hubtel Payment Initialized', [
            'reference' => $this->clientReference, 
            'original_phone' => $originalPhone,
            'formatted_phone' => $this->formData['mobile_number'],
            'response' => $response
        ]);

        if (isset($response['ResponseCode'])) {
            if ($response['ResponseCode'] === '0001' || $response['ResponseCode'] === '0000') {
                // Initial success or pending.
                // We will poll the DB for the final status updated by the callback.
                return;
            }
        }

        // Immediate failure if no success/pending code
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

        if ($transaction) {
            if ($transaction->status === 'success') {
                $this->handleSuccess($transaction);
            } elseif ($transaction->status === 'failed') {
                $this->state = 'failed';
            }
        }
    }

    protected function handleSuccess(Transaction $transaction)
    {
        $this->transactionId = $transaction->hubtel_transaction_id ?? 'N/A';
        $this->paymentDate = $transaction->completed_at?->format('M d, Y') ?? now()->format('M d, Y');
        $this->paymentTime = $transaction->completed_at?->format('h:i A') ?? now()->format('h:i A');
        $this->state = 'success';
    }

    protected function markAsFailed()
    {
        $this->state = 'failed';
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
    }

    public function render()
    {
        return view('livewire.payment-form');
    }
}
