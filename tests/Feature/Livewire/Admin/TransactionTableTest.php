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

    public function test_filters_transactions_by_date()
    {
        $today = Transaction::forceCreate([
            'client_reference' => 'REF-TODAY',
            'account_number' => '111',
            'service_type' => 'ECG_Prepaid',
            'amount' => 10,
            'customer_name' => 'Today Trans',
            'mobile_number' => '0241111111',
            'email' => 'today@test.com',
            'status' => 'success',
            'created_at' => now()
        ]);

        $old = Transaction::forceCreate([
            'client_reference' => 'REF-OLD',
            'account_number' => '222',
            'service_type' => 'ECG_Prepaid',
            'amount' => 10,
            'customer_name' => 'Old Trans',
            'mobile_number' => '0242222222',
            'email' => 'old@test.com',
            'status' => 'success',
            'created_at' => now()->subDays(10)
        ]);

        Livewire::test(TransactionTable::class)
            // Filter Today
            ->set('dateFilter', 'today')
            ->assertSee('Today Trans')
            ->assertDontSee('Old Trans')
            // Filter 7 Days
            ->set('dateFilter', '7days')
            ->assertSee('Today Trans')
            ->assertDontSee('Old Trans')
            // Filter 30 Days
            ->set('dateFilter', '30days')
            ->assertSee('Today Trans')
            ->assertSee('Old Trans');
    }

    public function test_filters_transactions_by_custom_range()
    {
        $target = Transaction::forceCreate([
            'client_reference' => 'REF-TARGET',
            'account_number' => '333',
            'service_type' => 'ECG_Prepaid',
            'amount' => 10,
            'customer_name' => 'Target Trans',
            'mobile_number' => '0243333333',
            'email' => 'target@test.com',
            'status' => 'success',
            'created_at' => now()->subDays(5)
        ]);

        $out = Transaction::forceCreate([
            'client_reference' => 'REF-OUT',
            'account_number' => '444',
            'service_type' => 'ECG_Prepaid',
            'amount' => 10,
            'customer_name' => 'Out Trans',
            'mobile_number' => '0244444444',
            'email' => 'out@test.com',
            'status' => 'success',
            'created_at' => now()->subDays(15)
        ]);

        Livewire::test(TransactionTable::class)
            ->set('dateFilter', 'custom')
            ->set('customStartDate', now()->subDays(6)->toDateString())
            ->set('customEndDate', now()->subDays(4)->toDateString())
            ->assertSee('Target Trans')
            ->assertDontSee('Out Trans');
    }
}
