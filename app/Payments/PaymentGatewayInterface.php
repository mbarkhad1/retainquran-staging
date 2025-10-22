<?php

namespace App\Payments;

interface PaymentGatewayInterface
{
	/**
	 * Create a payment intent for a given amount (in smallest currency unit) and metadata.
	 *
	 * @param int $amount
	 * @param array $metadata
	 * @param string|null $currency
	 * @return array
	 */
	public function createPaymentIntent(int $amount, array $metadata = [], ?string $currency = null): array;

	/**
	 * Retrieve a payment intent by id.
	 *
	 * @param string $paymentIntentId
	 * @return array
	 */
	public function retrievePaymentIntent(string $paymentIntentId): array;

	/**
	 * Verify and parse a Stripe webhook payload.
	 *
	 * @param string $payload
	 * @param string $signatureHeader
	 * @return array
	 */
	public function verifyWebhook(string $payload, string $signatureHeader): array;

	/**
	 * Ensure a customer exists for the given email and name, or create it.
	 *
	 * @param string $email
	 * @param string|null $name
	 * @return array
	 */
	public function getOrCreateCustomer(string $email, ?string $name = null): array;

	/**
	 * Create a setup intent for saving payment methods to a customer.
	 *
	 * @param string $customerId
	 * @return array
	 */
	public function createSetupIntent(string $customerId): array;

	/**
	 * List saved card payment methods for a customer.
	 *
	 * @param string $customerId
	 * @return array
	 */
	public function listPaymentMethods(string $customerId): array;

	/**
	 * Create and confirm a payment intent using a saved payment method.
	 *
	 * @param int $amount
	 * @param string $customerId
	 * @param string $paymentMethodId
	 * @param array $metadata
	 * @param string|null $currency
	 * @return array
	 */
	public function chargeSavedPaymentMethod(int $amount, string $customerId, string $paymentMethodId, array $metadata = [], ?string $currency = null): array;

	/**
	 * Create a subscription for a customer to a given price.
	 * Optionally set a default payment method.
	 *
	 * @param string $customerId
	 * @param string $priceId
	 * @param string|null $paymentMethodId
	 * @param array $metadata
	 * @return array
	 */
	public function createSubscription(string $customerId, string $priceId, ?string $paymentMethodId = null, array $metadata = []): array;
}


