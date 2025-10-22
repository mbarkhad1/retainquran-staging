<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up()
	{
		Schema::create('donations', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained()->onDelete('cascade');
			$table->decimal('amount', 10, 2);
			$table->string('currency', 3)->default('USD');
			$table->enum('payment_type', ['stripe', 'paypal', 'flutterwave']);
			$table->enum('payment_frequency', ['one_time', 'monthly']);
			$table->string('payment_provider_id')->nullable(); // Stripe payment_intent_id, PayPal order_id, Flutterwave transaction_id
			$table->string('subscription_id')->nullable(); // For recurring payments
			$table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
			$table->text('description')->nullable();
			$table->json('metadata')->nullable();
			$table->timestamp('payment_date')->nullable();
			$table->timestamp('next_payment_date')->nullable(); // For monthly donations
			$table->timestamps();

			$table->index(['user_id', 'status']);
			$table->index(['payment_type', 'status']);
			$table->index('payment_frequency');
		});
	}

	public function down()
	{
		Schema::dropIfExists('donations');
	}
};
