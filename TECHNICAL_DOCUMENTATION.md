# SaganPay Technical Documentation

This document outlines the architecture, data flow, and core components of the SaganPay payment integration.

## 1. High-Level Architecture
SaganPay is built using Laravel 12 and Livewire 3. It follows a modular, extensible pattern (KISS & DRY) to allow for easy addition of new utility services in the future.

### Core Components
- **Contracts**: `App\Contracts\PaymentProviderInterface` - Standardizes how any payment provider (Hubtel, etc.) must behave.
- **Services**: 
    - `App\Services\Hubtel\HubtelClient`: A single, base client for all Hubtel API interactions (DRY).
    - `App\Services\Payments\ECGProvider`: Implements ECG-specific logic using the Hubtel client.
- **Persistence**: `App\Models\Transaction` - Eloquent model for storing every transaction attempt and its final state.
- **UI & State**: `App\Livewire\PaymentForm` - A Livewire component that manages the entire lifecycle of a payment on a single page.
- **Webhooks**: `App\Http\Controllers\PaymentCallbackController` - A dedicated controller to receive and process real-time status updates from Hubtel.

---

## 2. Step-by-Step Payment Flow

### Step 1: Initialization (Client App)
The user fills out the payment form in `welcome.blade.php`, which houses the `PaymentForm` Livewire component.
- **Method**: `PaymentForm::mount()` sets default values (e.g., service type to "ECG Prepaid").

### Step 2: Form Submission & Persistence
When the user clicks "Proceed Payment":
- **Method**: `PaymentForm::submitForm()` is triggered.
- **Persistence**: It calls `ECGProvider::pay()`, which immediately creates a `pending` record in the `transactions` table.
- **API Request**: The `ECGProvider` then uses the `HubtelClient` to send a POST request to Hubtel's Commission Service API.

### Step 3: Processing & Polling
Once the request is sent, the UI switches to the "Processing" state.
- **UI State**: `state` changes to `'processing'` in `PaymentForm.php`.
- **Livewire Polling**: The view (`payment-form.blade.php`) uses `wire:poll.2s="pollTransactionStatus"`. Every 2 seconds, Livewire checks the database for the status of that specific `client_reference`.

### Step 4: External Callback (Webhook)
Hubtel processes the transaction and sends a POST request to our `/payment/callback` route.
- **Controller**: `PaymentCallbackController::__invoke()` receives the JSON payload.
- **Verification**: It finds the transaction in our database via the `ClientReference`.
- **Update**: It updates the record status to `success` or `failed` and stores the `hubtel_transaction_id` and raw response data.

### Step 5: Final State Resolution
The background polling in the Livewire component (`pollTransactionStatus`) detects the status change in the database.
- **Logic**: If status is `success`, it calls `handleSuccess()` to populate the receipt data and switch the UI state. If `failed`, it shows the error view.

---

## 3. Class Definitions

### `PaymentProviderInterface`
Defines the contract for all providers:
- `pay(array $data)`: Initiates a payment.
- `checkStatus(string $reference)`: Manual fallback status check.

### `HubtelClient`
The engine for HTTP communication:
- `post(string $endpoint, array $data)`: Handles Basic Auth and JSON posting.
- `get(string $endpoint, array $query)`: Handles status queries.

### `ECGProvider`
Encapsulates ECG-specific business logic:
- Hardcodes the `ServiceId` for ECG.
- Maps form data to the specific payload format required by Hubtel.

### `Transaction` (Model)
Table schema highlights:
- `client_reference`: Our unique internal ID.
- `status`: `pending`, `success`, or `failed`.
- `response_data`: JSON field to store full API responses for debugging/auditing.
