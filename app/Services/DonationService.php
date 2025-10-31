<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\User;
use App\Payments\PaymentGatewayInterface;
use App\Payments\StripePaymentGateway;
use App\Payments\PaypalPaymentGateway;
use App\Payments\FlutterwavePaymentGateway;
use App\Payments\XenditPaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DonationService
{
	private $stripeGateway;
	private $paypalGateway;
	private $flutterwaveGateway;
	private $xenditGateway;

	public function __construct(
		StripePaymentGateway $stripeGateway,
		PaypalPaymentGateway $paypalGateway,
		FlutterwavePaymentGateway $flutterwaveGateway,
		XenditPaymentGateway $xenditGateway
	) {
		$this->stripeGateway = $stripeGateway;
		$this->paypalGateway = $paypalGateway;
		$this->flutterwaveGateway = $flutterwaveGateway;
		$this->xenditGateway = $xenditGateway;
	}

	/**
	 * Initiate a donation payment
	 */
	public function initiatePayment(
		User $user,
		float $amount,
		string $paymentType,
		string $paymentFrequency,
		?string $currency = null,
		string $description = 'Donation',
		array $metadata = []
	): array {
		
		// dd($paymentType);
		// Create donation record
		$donation = Donation::create([
			'user_id' => $user->id,
			'amount' => $amount,
			'currency' => $currency ?? $this->getDefaultCurrency($paymentType),
			'payment_type' => $paymentType,
			'payment_frequency' => $paymentFrequency,
			'description' => $description,
			'metadata' => $metadata,
			'status' => 'pending',
		]);
		// dd($donation);

		// Route to appropriate payment gateway
		switch ($paymentType) {
			case 'stripe':
				return $this->initiateStripePayment($donation, $amount, $currency, $description, $metadata);
			case 'paypal':
				return $this->initiatePaypalPayment($donation, $amount, $currency, $description, $metadata);
			case 'flutterwave':
				return $this->initiateFlutterwavePayment($donation, $amount, $currency, $description, $metadata);
			case 'xendit':
				return $this->initiateXenditPayment($donation, $amount, $currency, $description, $metadata);
			default:
				throw new \InvalidArgumentException("Unsupported payment type: {$paymentType}");
		}
	}

	/**
	 * Process a one-time payment
	 */
	public function processOneTimePayment(
		User $user,
		float $amount,
		string $paymentType,
		?string $currency = null,
		string $description = 'Donation',
		array $metadata = []
	): array {
		return $this->initiatePayment($user, $amount, $paymentType, 'one_time', $currency, $description, $metadata);
	}

	/**
	 * Setup monthly recurring donation
	 */
	public function setupMonthlyDonation(
		User $user,
		float $amount,
		string $paymentType,
		?string $currency = null,
		string $description = 'Monthly Donation',
		array $metadata = []
	): array {
		return $this->initiatePayment($user, $amount, $paymentType, 'monthly', $currency, $description, $metadata);
	}

	/**
	 * Get user's donation history
	 */
	public function getUserDonations(User $user): array
	{
		return $user->donations()
			->orderBy('created_at', 'desc')
			->get()
			->toArray();
	}

	/**
	 * Cancel monthly donation
	 */
	public function cancelMonthlyDonation(User $user, int $donationId): array
	{
		$donation = Donation::where('user_id', $user->id)
			->where('id', $donationId)
			->where('payment_frequency', 'monthly')
			->firstOrFail();

		// Cancel subscription with payment provider
		if ($donation->subscription_id) {
			$this->cancelSubscriptionWithProvider($donation);
		}

		$donation->markAsCancelled();

		return [
			'donation_id' => $donation->id,
			'status' => 'cancelled',
			'message' => 'Monthly donation cancelled successfully'
		];
	}

	/**
	 * Initiate Stripe payment
	 */
	private function initiateStripePayment(Donation $donation, float $amount, ?string $currency, string $description, array $metadata): array
	{
		$amountInCents = (int) ($amount * 100);
		$currency = $currency ?? 'usd';

		if ($donation->payment_frequency === 'one_time') {
			$intent = $this->stripeGateway->createPaymentIntent($amountInCents, $metadata, $currency);
			$donation->update(['payment_provider_id' => $intent['id']]);

			return [
				'donation_id' => $donation->id,
				'payment_type' => 'stripe',
				'client_secret' => $intent['client_secret'],
				'payment_intent' => $intent,
			];
		} else {
			$intent = $this->stripeGateway->createPaymentIntent($amountInCents, $metadata, $currency);
			$donation->update(['payment_provider_id' => $intent['id']]);

			return [
				'donation_id' => $donation->id,
				'payment_type' => 'stripe',
				'client_secret' => $intent['client_secret'],
				'payment_intent' => $intent,
				'message' => 'Complete this payment to setup monthly donations',
			];
		}
	}

	/**
	 * Initiate PayPal payment
	 */
	private function initiatePaypalPayment(Donation $donation, float $amount, ?string $currency, string $description, array $metadata): array
	{
		$amountInCents = (int) ($amount * 100);
		$currency = $currency ?? 'USD';

		$order = $this->paypalGateway->createOrder($amountInCents, $currency, $metadata);
		$donation->update(['payment_provider_id' => $order['id']]);

		return [
			'donation_id' => $donation->id,
			'payment_type' => 'paypal',
			'order' => $order,
		];
	}

	/**
	 * Initiate Flutterwave payment
	 */
	private function initiateFlutterwavePayment(Donation $donation, float $amount, ?string $currency, string $description, array $metadata): array
	{
		$currency = $currency ?? 'NGN';

		$paymentData = [
			'amount' => $amount,
			'currency' => $currency,
			'email' => $donation->user->email,
			'customer_name' => $donation->user->usr_name ?? $donation->user->email,
			'description' => $description,
			'metadata' => $metadata,
		];

		$payment = $this->flutterwaveGateway->createPayment($paymentData);
		$donation->update(['payment_provider_id' => $payment['data']['tx_ref'] ?? null]);

		return [
			'donation_id' => $donation->id,
			'payment_type' => 'flutterwave',
			'payment' => $payment,
		];
	}

	/**
	 * Initiate Xendit payment
	 */
	private function initiateXenditPayment(Donation $donation, float $amount, ?string $currency, string $description, array $metadata): array
	{
		$currency = $currency ?? 'IDR'; // Xendit primarily supports IDR, PHP, USD depending on account setup
		$externalId = 'donation_' . $donation->id . '_' . time();

		$payload = [
			'external_id' => $externalId,
			'amount' => (int) $amount,
			'payer_email' => $donation->user->email,
			'description' => $description,
			'success_url' => config('app.url') . '/payment/success',
			'cancel_url' => config('app.url') . '/payment/cancel',
			'metadata' => $metadata,
			'currency' => $currency,
		];

		$response = $this->xenditGateway->initiateDonationPayment($payload);

		$donation->update([
			'payment_provider_id' => $response['id'] ?? null,
		]);

		return [
			'donation_id' => $donation->id,
			'payment_type' => 'xendit',
			'checkout_url' => $response['invoice_url'] ?? ($response['payment_url'] ?? null),
			'response' => $response,
		];
	}

	/**
	 * Cancel subscription with payment provider
	 */
	private function cancelSubscriptionWithProvider(Donation $donation): void
	{
		try {
			switch ($donation->payment_type) {
				case 'stripe':
					// Cancel Stripe subscription
					break;
				case 'paypal':
					// Cancel PayPal subscription
					break;
				case 'flutterwave':
					// Cancel Flutterwave subscription
					break;
				case 'xendit':
					// Cancel Xendit recurring (if implemented)
					break;
			}
		} catch (\Exception $e) {
			Log::error('Failed to cancel subscription with provider', [
				'donation_id' => $donation->id,
				'payment_type' => $donation->payment_type,
				'error' => $e->getMessage()
			]);
		}
	}

	/**
	 * Get default currency for payment type
	 */
	private function getDefaultCurrency(string $paymentType): string
	{
		switch ($paymentType) {
			case 'stripe':
				return 'usd';
			case 'paypal':
				return 'USD';
			case 'flutterwave':
				return 'NGN';
			case 'xendit':
				return 'IDR';
			default:
				return 'USD';
		}
	}

	/**
	 * Handle payment webhook
	 */
	public function handlePaymentWebhook(string $paymentType, array $webhookData): void
	{
		switch ($paymentType) {
			case 'stripe':
				$this->handleStripeWebhook($webhookData);
				break;
			case 'paypal':
				$this->handlePaypalWebhook($webhookData);
				break;
			case 'flutterwave':
				$this->handleFlutterwaveWebhook($webhookData);
				break;
			case 'xendit':
				$this->handleXenditWebhook($webhookData);
				break;
		}
	}

	private function handleStripeWebhook(array $webhookData): void
	{
		Log::info('Stripe webhook received', $webhookData);
	}

	private function handlePaypalWebhook(array $webhookData): void
	{
		Log::info('PayPal webhook received', $webhookData);
	}

	private function handleFlutterwaveWebhook(array $webhookData): void
	{
		Log::info('Flutterwave webhook received', $webhookData);
	}

	private function handleXenditWebhook(array $webhookData): void
	{
		Log::info('Xendit webhook received', $webhookData);

		if (isset($webhookData['status']) && $webhookData['status'] === 'PAID') {
			$donation = Donation::where('payment_provider_id', $webhookData['id'] ?? null)->first();
			if ($donation) {
				$donation->update(['status' => 'completed']);
				Log::info('Donation marked as completed via Xendit webhook', ['donation_id' => $donation->id]);
			}
		}
	}
}
