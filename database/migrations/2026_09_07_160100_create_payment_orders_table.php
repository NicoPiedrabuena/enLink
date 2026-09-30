<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('package_name', 100);
            $table->unsignedInteger('credits');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->string('provider', 40)->default('mercadopago');
            $table->uuid('external_reference')->unique();
            $table->string('provider_preference_id')->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->string('status', 40)->default('awaiting_checkout');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
