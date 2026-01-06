<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Services\Payments\ECGProvider;
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

    protected array $rules = [
        'formData.account_number' => 'required|string',
        'formData.service_type' => 'required|string',
        'formData.amount' => 'required|numeric|min:1',
        'formData.customer_name' => 'required|string',
        'formData.mobile_number' => 'required|string|size:10',
        'formData.email' => 'required|email',
    ];

    public function mount()
    {
        $this->formData['service_type'] = 'ECG Prepaid';
    }

    public function submitForm(ECGProvider $provider)
    {
        $this->validate();
        
        $this->state = 'processing';
        $this->clientReference = uniqid('SP-');

        $response = $provider->pay(array_merge($this->formData, [
            'client_reference' => $this->clientReference
        ]));

        Log::info('Hubtel Payment Initialized', ['reference' => $this->clientReference, 'response' => $response]);

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
