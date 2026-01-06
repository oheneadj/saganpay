<?php

namespace App\Contracts;

interface PaymentProviderInterface
{
    /**
     * Initiate a payment request.
     *
     * @param array $data
     * @return array
     */
    public function pay(array $data): array;

    /**
     * Check the status of a specific transaction.
     *
     * @param string $clientReference
     * @return array
     */
    public function checkStatus(string $clientReference): array;
}
