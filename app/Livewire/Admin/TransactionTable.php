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
    public $perPage = 10;
    public $dateFilter = '';
    public $customStartDate = '';
    public $customEndDate = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'service' => ['except' => ''],
        'dateFilter' => ['except' => ''],
        'customStartDate' => ['except' => ''],
        'customEndDate' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function viewTransaction($id)
    {
        $this->selectedTransaction = Transaction::with('user')->find($id);
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

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
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
            ->when($this->dateFilter, function ($query) {
                if ($this->dateFilter === 'today') {
                    $query->where('created_at', '>=', now()->startOfDay());
                } elseif ($this->dateFilter === '7days') {
                    $query->where('created_at', '>=', now()->subDays(7)->startOfDay());
                } elseif ($this->dateFilter === '30days') {
                    $query->where('created_at', '>=', now()->subDays(30)->startOfDay());
                } elseif ($this->dateFilter === 'custom' && $this->customStartDate && $this->customEndDate) {
                    $query->whereBetween('created_at', [
                        \Carbon\Carbon::parse($this->customStartDate)->startOfDay(),
                        \Carbon\Carbon::parse($this->customEndDate)->endOfDay()
                    ]);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.transaction-table', [
            'transactions' => $transactions,
        ]);
    }
}
