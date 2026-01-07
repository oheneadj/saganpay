# SaganPay Payment Flow Documentation

This document describes the technical flow of a transaction in the SaganPay application, following the Hubtel state machine.

## 1. Sequence Diagram

```mermaid
sequenceDiagram
    participant User
    participant Browser (Alpine.js)
    participant Server (Livewire)
    participant Hubtel API
    participant Database

    User->>Browser: Fills Form & Clicks "Proceed Payment"
    Browser->>Server: submitForm()
    Server->>Server: Validate data
    Server->>Server: Change state to 'processing'
    Server->>Browser: Update UI to "Processing"
    
    Note over Browser,Server: Transitioning to Processing State
    
    Browser->>Server: initiatePayment() (triggered by wire:init)
    Server->>Hubtel API: Send POST Request (Service ID)
    Hubtel API-->>Server: Response (ResponseCode 0001 - Pending)
    Server->>Database: Create Transaction (status: pending)
    Server->>Browser: Return control
    
    loop Every 2 seconds
        Browser->>Server: pollTransactionStatus()
        Server->>Database: Check for status change
        Database-->>Server: Returning status
        alt status is 'success'
            Server->>Browser: Change state to 'success'
        else status is 'failed'
            Server->>Browser: Change state to 'failed'
        end
    end

    Note over Hubtel API,Database: Asynchronous Callback
    Hubtel API->>Server: Callback POST (ResponseCode 0000 - Success)
    Server->>Database: Update Transaction (status: success)
```

## 2. State Management

The application uses a `state` variable to control the UI.

| State | Description | UI Block Visible |
|-------|-------------|------------------|
| `form` | Initial state where user fills details. | Payment Form |
| `processing` | Payment has been sent to Hubtel; waiting for mobile prompt/callback. | Processing Spinner |
| `success` | Hubtel callback confirmed payment. | Success Receipt |
| `failed` | API error or user cancelled/failed payment. | Error Message |

## 3. Potential Failure Points

1. **State Desync**: If the browser's Alpine.js state doesn't update when the server's Livewire state changes.
2. **Polling Efficiency**: If `wire:poll` starts too early or the `clientReference` is missing.
3. **Idempotency**: Ensuring the same payment isn't sent twice if the user refreshes during 'processing'.

## 4. Logical Flow in Code

1. **`submitForm`**: 
   - Validates input.
   - Sets `state` to `processing`.
   - Generates a unique `clientReference`.
2. **`initiatePayment`**:
   - Triggered automatically when the "Processing" UI is rendered.
   - Calls the `HubtelProvider` to perform the actual API request.
3. **`pollTransactionStatus`**:
   - Runs periodically in the background.
   - Only updates the UI when the database reflects a status change from the external callback.
