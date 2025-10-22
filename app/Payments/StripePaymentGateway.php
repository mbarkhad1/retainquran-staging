<?php

namespace App\Payments;

use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGatewayInterface
{
	/** @var StripeClient */
	private $client;

	/** @var string */
	private $webhookSecret;

	/** @var string */
	private $defaultCurrency;

	public function __construct()
	{
		$this->client = new StripeClient(config('stripe.secret'));
		$this->webhookSecret = (string) config('stripe.webhook_secret');
		$this->defaultCurrency = (string) config('stripe.currency', 'usd');
	}

	public function createPaymentIntent(int $amount, array $metadata = [], ?string $currency = null): array
	{
		$currencyToUse = $currency ?: $this->defaultCurrency;
		$intent = $this->client->paymentIntents->create([
			'amount' => $amount,
			'currency' => $currencyToUse,
			'automatic_payment_methods' => ['enabled' => true],
			'metadata' => $metadata,
		]);

		return $intent->toArray();
	}

	public function retrievePaymentIntent(string $paymentIntentId): array
	{
		$intent = $this->client->paymentIntents->retrieve($paymentIntentId);
		return $intent->toArray();
	}

	public function verifyWebhook(string $payload, string $signatureHeader): array
	{
		$event = Webhook::constructEvent($payload, $signatureHeader, $this->webhookSecret);
		return $event->toArray();
	}

	public function getOrCreateCustomer(string $email, ?string $name = null): array
	{
		$existing = $this->client->customers->all(['email' => $email, 'limit' => 1]);
		if (!empty($existing->data)) {
			return $existing->data[0]->toArray();
		}
		$customer = $this->client->customers->create([
			'email' => $email,
			'name' => $name,
		]);
		return $customer->toArray();
	}

	public function createSetupIntent(string $customerId): array
	{
		$intent = $this->client->setupIntents->create([
			'customer' => $customerId,
			'payment_method_types' => ['card'],
		]);
		return $intent->toArray();
	}

	public function listPaymentMethods(string $customerId): array
	{
		$list = $this->client->paymentMethods->all([
			'customer' => $customerId,
			'type' => 'card',
		]);
		return $list->toArray();
	}

	public function chargeSavedPaymentMethod(int $amount, string $customerId, string $paymentMethodId, array $metadata = [], ?string $currency = null): array
	{
		$currencyToUse = $currency ?: $this->defaultCurrency;
		$intent = $this->client->paymentIntents->create([
			'amount' => $amount,
			'currency' => $currencyToUse,
			'customer' => $customerId,
			'payment_method' => $paymentMethodId,
			'confirm' => true,
			'off_session' => true,
			'metadata' => $metadata,
		]);
		return $intent->toArray();
	}

	public function createSubscription(string $customerId, string $priceId, ?string $paymentMethodId = null, array $metadata = []): array
	{
		$params = [
			'customer' => $customerId,
			'items' => [[ 'price' => $priceId ]],
			'expand' => ['latest_invoice.payment_intent'],
			'metadata' => $metadata,
		];
		if ($paymentMethodId) {
			// Attach payment method and set as default
			$this->client->paymentMethods->attach($paymentMethodId, ['customer' => $customerId]);
			$this->client->customers->update($customerId, [
				'invoice_settings' => [
					'default_payment_method' => $paymentMethodId,
				],
			]);
		}
		$subscription = $this->client->subscriptions->create($params);
		return $subscription->toArray();
	}
}


