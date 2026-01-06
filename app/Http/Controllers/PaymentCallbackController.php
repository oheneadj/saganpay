<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    /**
     * Handle the Hubtel Payment Callback.
     */
    public function __invoke(Request $request)
    {
        $data = $request->all();
        
        Log::info('Hubtel Callback Received', ['data' => $data]);

        $callbackData = $data['Data'] ?? [];
        $clientReference = $callbackData['ClientReference'] ?? ($data['ClientReference'] ?? null);
        
        if (!$clientReference) {
            return response()->json(['status' => 'error', 'message' => 'No ClientReference provided'], 400);
        }

        $transaction = Transaction::where('client_reference', $clientReference)->first();

        if (!$transaction) {
            Log::warning('Transaction not found for callback', ['client_reference' => $clientReference]);
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        // Update transaction status based on Hubtel ResponseCode
        $responseCode = $data['ResponseCode'] ?? 'FAILED';
        
        if ($responseCode === '0000') {
            $transaction->update([
                'status' => 'success',
                'hubtel_transaction_id' => $callbackData['TransactionId'] ?? ($data['TransactionId'] ?? null),
                'response_data' => $data,
                'completed_at' => now(),
            ]);
        } else {
            $transaction->update([
                'status' => 'failed',
                'response_data' => $data,
                'completed_at' => now(),
            ]);
        }

        return response()->json(['status' => 'success']);
    }
}
