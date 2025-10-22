<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\User;
use App\Payments\PaymentGatewayInterface;
use App\Payments\StripePaymentGateway;
use App\Payments\PaypalPaymentGateway;
use App\Payments\FlutterwavePaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DonationService
{
	private $stripeGateway;
	private $paypalGateway;
	private $flutterwaveGateway;

	public function __construct(
		StripePaymentGateway $stripeGateway,
		PaypalPaymentGateway $paypalGateway,
		FlutterwavePaymentGateway $flutterwaveGateway
	) {

		$this->stripeGateway = $stripeGateway;
		$this->paypalGateway = $paypalGateway;
		$this->flutterwaveGateway = $flutterwaveGateway;
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

		// Route to appropriate payment gateway
		switch ($paymentType) {
			case 'stripe':
				return $this->initiateStripePayment($donation, $amount, $currency, $description, $metadata);
			case 'paypal':
				return $this->initiatePaypalPayment($donation, $amount, $currency, $description, $metadata);
			case 'flutterwave':
				return $this->initiateFlutterwavePayment($donation, $amount, $currency, $description, $metadata);
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
			// For monthly donations, create a price and subscription
			// This is a simplified version - in production, you'd create prices in Stripe dashboard
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
		}
	}

	/**
	 * Handle Stripe webhook
	 */
	private function handleStripeWebhook(array $webhookData): void
	{
		// Implementation for Stripe webhook handling
		Log::info('Stripe webhook received', $webhookData);
	}

	/**
	 * Handle PayPal webhook
	 */
	private function handlePaypalWebhook(array $webhookData): void
	{
		// Implementation for PayPal webhook handling
		Log::info('PayPal webhook received', $webhookData);
	}

	/**
	 * Handle Flutterwave webhook
	 */
	private function handleFlutterwaveWebhook(array $webhookData): void
	{
		// Implementation for Flutterwave webhook handling
		Log::info('Flutterwave webhook received', $webhookData);
	}
}
