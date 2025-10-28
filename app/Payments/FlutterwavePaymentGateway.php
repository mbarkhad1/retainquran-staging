<?php

namespace App\Payments;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwavePaymentGateway
{
	private $publicKey;
	private $secretKey;
	private $encryptionKey;
	private $environment;
	private $baseUrl;

	public function __construct()
	{
		$this->publicKey = (string) config('flutterwave.public_key');
		$this->secretKey = (string) config('flutterwave.secret_key');
		$this->encryptionKey = (string) config('flutterwave.encryption_key');
		$this->environment = (string) config('flutterwave.environment', 'staging');
		
		// Set base URL based on environment
		$this->baseUrl = $this->environment === 'live' 
			? 'https://api.flutterwave.com/v3'
			: 'https://api.flutterwave.com/v3';
	}

	private function makeRequest(string $method, string $endpoint, array $data = []): array
	{
		// dd($this->baseUrl . $endpoint, $this->secretKey);
		$url = $this->baseUrl . $endpoint;
		$flutterwaveHeaders = [
			'Authorization' => 'Bearer ' . $this->secretKey,
			'Content-Type' => 'application/json',
		];
		// dd($flutterwaveHeaders);

		

		try {
			// $response = Http::withOptions(['headers' => $flutterwaveHeaders])->$method($url, $data);
			$response = Http::withHeaders($flutterwaveHeaders)->$method($url, $data);
			// dd($response);
			if ($response->successful()) {
				return $response->json();
			} else {
				Log::error('Flutterwave API Error', [
					'status' => $response->status(),
					'response' => $response->body(),
					'endpoint' => $endpoint,
					'data' => $data
				]);
				
				return [
					'status' => 'error',
					'message' => $response->json()['message'] ?? 'API request failed',
					'data' => $response->json(),
				];
			}
		} catch (\Exception $e) {
			Log::error('Flutterwave API Exception', [
				'message' => $e->getMessage(),
				'endpoint' => $endpoint,
				'data' => $data
			]);
			
			return [
				'status' => 'error',
				'message' => $e->getMessage(),
				'data' => null
			];
		}
	}

	public function createPayment(array $data): array
	{
		$payload = [
			'tx_ref' => $data['tx_ref'] ?? 'tx_' . time() . '_' . uniqid(),
			'amount' => $data['amount'],
			'currency' => $data['currency'] ?? config('flutterwave.currency', 'NGN'),
			'redirect_url' => $data['redirect_url'] ?? config('app.url') . '/api/flutterwave/callback',
			'customer' => [
				'email' => $data['email'],
				'name' => $data['customer_name'] ?? $data['email'],
			],
			'customizations' => [
				'title' => $data['title'] ?? 'Payment',
				'description' => $data['description'] ?? 'Payment for services',
				'logo' => $data['logo'] ?? '',
			],
		];

		// Add metadata if provided
		if (isset($data['metadata']) && is_array($data['metadata'])) {
			$payload['meta'] = $data['metadata'];
		}

		return $this->makeRequest('POST', '/payments', $payload);
	}

	public function verifyPayment(string $transactionId): array
	{
		return $this->makeRequest('GET', "/transactions/{$transactionId}/verify");
	}

	public function createCustomer(array $data): array
	{
		$payload = [
			'email' => $data['email'],
			'name' => $data['name'] ?? $data['email'],
			'phone_number' => $data['phone_number'] ?? '',
		];

		// Add optional fields if provided
		if (isset($data['phone_number'])) {
			$payload['phone_number'] = $data['phone_number'];
		}

		return $this->makeRequest('POST', '/customers', $payload);
	}

	public function getCustomer(string $customerId): array
	{
		return $this->makeRequest('GET', "/customers/{$customerId}");
	}

	public function saveCard(array $data): array
	{
		$payload = [
			'email' => $data['email'],
			'card_number' => $data['card_number'],
			'cvv' => $data['cvv'],
			'expiry_month' => $data['expiry_month'],
			'expiry_year' => $data['expiry_year'],
			'currency' => $data['currency'] ?? config('flutterwave.currency', 'NGN'),
		];

		return $this->makeRequest('POST', '/cards/tokenize', $payload);
	}

	public function chargeCard(array $data): array
	{
		$payload = [
			'tx_ref' => $data['tx_ref'] ?? 'tx_' . time() . '_' . uniqid(),
			'amount' => $data['amount'],
			'currency' => $data['currency'] ?? config('flutterwave.currency', 'NGN'),
			'email' => $data['email'],
			'card_token' => $data['card_token'],
			'customer' => [
				'email' => $data['email'],
			],
			'authorization' => [
				'mode' => 'card',
				'pin' => $data['pin'] ?? '',
			],
		];

		return $this->makeRequest('POST', '/charges?type=card', $payload);
	}

	public function createSubscription(array $data): array
	{
		$payload = [
			'amount' => $data['amount'],
			'plan' => $data['plan_name'],
			'customer' => [
				'email' => $data['customer_email'],
				'name' => $data['customer_name'] ?? $data['customer_email'],
			],
			'interval' => $data['interval'],
			'currency' => $data['currency'] ?? config('flutterwave.currency', 'NGN'),
		];

		return $this->makeRequest('POST', '/subscriptions', $payload);
	}

	public function getSubscription(string $subscriptionId): array
	{
		return $this->makeRequest('GET', "/subscriptions/{$subscriptionId}");
	}

	public function cancelSubscription(string $subscriptionId): array
	{
		return $this->makeRequest('PUT', "/subscriptions/{$subscriptionId}/cancel");
	}

	public function verifyWebhook(string $payload, string $signature): bool
	{
		$webhookSecret = config('flutterwave.webhook_secret');
		
		if (empty($webhookSecret)) {
			Log::warning('Flutterwave webhook secret not configured');
			return false;
		}
		
		$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
		
		return hash_equals($expectedSignature, $signature);
	}

	public function flutterwaveCallback(Request $request)
	{
		try {
			$transactionId = $request->get('transaction_id');

			if (!$transactionId) {
				return response()->json([
					'success' => false,
					'message' => 'Transaction ID missing from Flutterwave callback.'
				], 400);
			}

			// Verify the transaction with Flutterwave API
			$response = Http::withToken(config('flutterwave.secret_key'))
				->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

			if ($response->failed()) {
				return response()->json([
					'success' => false,
					'message' => 'Payment verification failed.'
				], 400);
			}

			$data = $response->json();

			if (($data['status'] ?? '') === 'success' && ($data['data']['status'] ?? '') === 'successful') {
				// ✅ Payment successful
				// You can update your DB here (mark as paid)
				// Payment::where('tx_ref', $data['data']['tx_ref'])->update(['status' => 'paid']);

				return response()->json([
					'success' => true,
					'message' => 'Payment verified successfully via redirect callback.',
					'data' => $data['data']
				]);
			}

			// ❌ Payment failed or pending
			return response()->json([
				'success' => false,
				'message' => 'Payment not successful.',
				'data' => $data['data'] ?? null
			]);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Error verifying payment: ' . $e->getMessage(),
			], 500);
		}
	}

}
