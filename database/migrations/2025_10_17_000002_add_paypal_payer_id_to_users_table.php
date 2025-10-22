<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up()
	{
		Schema::table('users', function (Blueprint $table) {
			$table->string('paypal_payer_id')->nullable()->after('stripe_customer_id');
		});
	}

	public function down()
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('paypal_payer_id');
		});
	}
};


