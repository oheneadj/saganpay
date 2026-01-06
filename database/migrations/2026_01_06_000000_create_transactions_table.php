<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('client_reference')->unique();
            $table->string('hubtel_transaction_id')->nullable();
            $table->string('account_number');
            $table->string('service_type');
            $table->decimal('amount', 10, 2);
            $table->string('customer_name');
            $table->string('mobile_number');
            $table->string('email');
            $table->string('status')->default('pending'); // pending, success, failed
            $table->json('response_data')->nullable(); // Store raw Hubtel response
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
