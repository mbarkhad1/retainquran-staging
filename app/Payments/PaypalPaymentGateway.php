<?php

namespace App\Payments;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Webhooks\VerifyWebhookSignatureRequest;

class PaypalPaymentGateway
{
	/** @var PayPalHttpClient */
	private $client;

	public function __construct()
	{
		$clientId = (string) config('paypal.client_id');
		$clientSecret = (string) config('paypal.client_secret');
		$mode = (string) config('paypal.mode', 'sandbox');
		$environment = $mode === 'live'
			? new ProductionEnvironment($clientId, $clientSecret)
			: new SandboxEnvironment($clientId, $clientSecret);
		$this->client = new PayPalHttpClient($environment);
	}

	public function createOrder(int $amount, string $currency, array $metadata = []): array
	{
		$request = new OrdersCreateRequest();
		$request->prefer('return=representation');
		$request->body = [
			'intent' => 'CAPTURE',
			'purchase_units' => [[
				'amount' => [
					'currency_code' => strtoupper($currency),
					'value' => number_format($amount / 100, 2, '.', ''),
				],
				'custom_id' => $metadata['custom_id'] ?? null,
			]],
		];
		$response = $this->client->execute($request);
		return json_decode(json_encode($response->result), true);
	}

	public function captureOrder(string $orderId): array
	{
		$request = new OrdersCaptureRequest($orderId);
		$request->prefer('return=representation');
		$response = $this->client->execute($request);
		return json_decode(json_encode($response->result), true);
	}

	public function verifyWebhook(string $transmissionId, string $timestamp, string $signature, string $webhookId, string $eventBody, string $certUrl, string $authAlgo): array
	{
		$request = new VerifyWebhookSignatureRequest();
		$request->body = [
			'transmission_id' => $transmissionId,
			'transmission_time' => $timestamp,
			'cert_url' => $certUrl,
			'auth_algo' => $authAlgo,
			'transmission_sig' => $signature,
			'webhook_id' => $webhookId,
			'webhook_event' => json_decode($eventBody, true),
		];
		$response = $this->client->execute($request);
		return json_decode(json_encode($response->result), true);
	}
}


