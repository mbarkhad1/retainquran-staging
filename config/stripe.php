<?php

return [
	// Publish your Stripe keys in .env as STRIPE_KEY and STRIPE_SECRET
	'key' => env('STRIPE_KEY', ''),
	'secret' => env('STRIPE_SECRET', ''),

	// Optional webhook secret used to verify webhook signatures
	'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),

	// Default currency for charges/intents
	'currency' => env('STRIPE_CURRENCY', 'usd'),
];


