<?php

return [
	'public_key' => env('FLUTTERWAVE_PUBLIC_KEY', ''),
	'secret_key' => env('FLUTTERWAVE_SECRET_KEY', ''),
	'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY', ''),
	'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET', ''),
	'currency' => env('FLUTTERWAVE_CURRENCY', 'NGN'),
	'environment' => env('FLUTTERWAVE_ENVIRONMENT', 'staging'), // 'staging' or 'live'
];
