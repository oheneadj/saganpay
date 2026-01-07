<?php

namespace App\Livewire\Admin;

use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionTable extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $service = '';
    public $selectedTransaction = null;
    public $showModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'service' => ['except' => ''],
    ];

    public function viewTransaction($id)
    {
        $this->selectedTransaction = Transaction::find($id);
        $this->showModal = true;
        $this->dispatch('open-modal', 'transaction-details');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedTransaction = null;
        $this->dispatch('close-modal', 'transaction-details');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $transactions = Transaction::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('customer_name', 'like', '%' . $this->search . '%')
                      ->orWhere('account_number', 'like', '%' . $this->search . '%')
                      ->orWhere('client_reference', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->service, function ($query) {
                $query->where('service_type', $this->service);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.transaction-table', [
            'transactions' => $transactions,
        ]);
    }
}
