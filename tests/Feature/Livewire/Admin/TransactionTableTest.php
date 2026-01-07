<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\TransactionTable;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_transaction_table_component()
    {
        Livewire::test(TransactionTable::class)
            ->assertStatus(200);
    }

    public function test_displays_initiated_by_user_in_modal()
    {
        $user = User::factory()->create(['name' => 'Test Initiator']);
        $transaction = Transaction::create([
            'client_reference' => 'REF-TEST-INIT',
            'account_number' => '123456789',
            'service_type' => 'ECG_Prepaid',
            'amount' => 50.00,
            'customer_name' => 'SaganPay Admin',
            'mobile_number' => '0240000000',
            'email' => 'admin@saganpay.com',
            'status' => 'success',
            'user_id' => $user->id
        ]);

        Livewire::test(TransactionTable::class)
            // Select the transaction to view
            ->call('viewTransaction', $transaction->id)
            // Assert the modal property is set
            ->assertSet('showModal', true)
            ->assertSet('selectedTransaction.id', $transaction->id)
            // Assert the modal content is rendered (Livewire assertions check the rendered HTML)
            ->assertSee('Initiated By')
            ->assertSee('Test Initiator');
    }

    public function test_displays_guest_user_for_transactions_without_user_id()
    {
        $transaction = Transaction::create([
            'client_reference' => 'REF-TEST-GUEST',
            'account_number' => '123456789',
            'service_type' => 'ECG_Prepaid',
            'amount' => 50.00,
            'customer_name' => 'Guest Customer',
            'mobile_number' => '0240000000',
            'email' => 'guest@example.com',
            'status' => 'success',
            'user_id' => null
        ]);

        Livewire::test(TransactionTable::class)
            ->call('viewTransaction', $transaction->id)
            ->assertSee('Initiated By')
            ->assertSee('Guest User');
    }
}
