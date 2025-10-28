<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Payments\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class StripeController extends Controller
{
	private $payments;

	public function __construct(PaymentGatewayInterface $payments)
	{
		$this->payments = $payments;
	}

	public function createIntent(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'amount' => 'required|integer|min:1', // smallest currency unit
			'currency' => 'sometimes|string',
			'metadata' => 'sometimes|array',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$amount = (int) $request->input('amount');
		$currency = $request->input('currency');
		$metadata = (array) $request->input('metadata', []);

		$intent = $this->payments->createPaymentIntent($amount, $metadata, $currency);
		return response()->json([
			'client_secret' => $intent['client_secret'] ?? null,
			'payment_intent' => $intent,
		]);
	}

	public function webhook(Request $request)
	{
		$signature = $request->header('Stripe-Signature', '');
		try {
			$event = $this->payments->verifyWebhook($request->getContent(), $signature);
		} catch (\Throwable $e) {
			Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
			return response('Invalid signature', 400);
		}

		// You can branch by $event['type'] (e.g., 'payment_intent.succeeded')
		// For now, just 200 OK to acknowledge
		return response('OK', 200);
	}

	public function ensureCustomer(Request $request)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}
		if ($user->stripe_customer_id) {
			return response()->json(['customer_id' => $user->stripe_customer_id]);
		}
		$customer = $this->payments->getOrCreateCustomer($user->email, $user->usr_name ?? null);
		$user->stripe_customer_id = $customer['id'];
		$user->save();
		return response()->json(['customer_id' => $customer['id']]);
	}

	public function createSetupIntent(Request $request)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}
		if (!$user->stripe_customer_id) {
			$customer = $this->payments->getOrCreateCustomer($user->email, $user->usr_name ?? null);
			$user->stripe_customer_id = $customer['id'];
			$user->save();
		}
		$intent = $this->payments->createSetupIntent($user->stripe_customer_id);
		return response()->json(['client_secret' => $intent['client_secret'] ?? null, 'setup_intent' => $intent]);
	}

	public function listSavedCards(Request $request)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}
		if (!$user->stripe_customer_id) {
			return response()->json(['data' => []]);
		}
		$methods = $this->payments->listPaymentMethods($user->stripe_customer_id);
		return response()->json($methods);
	}

	public function chargeWithSavedCard(Request $request)
	{
		$user = Auth::user();
		$validator = Validator::make($request->all(), [
			'amount' => 'required|integer|min:1',
			'payment_method_id' => 'required|string',
			'currency' => 'sometimes|string',
			'metadata' => 'sometimes|array',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}
		if (!$user->stripe_customer_id) {
			return response()->json(['message' => 'No saved customer'], 400);
		}
		$amount = (int) $request->input('amount');
		$paymentMethodId = (string) $request->input('payment_method_id');
		$currency = $request->input('currency');
		$metadata = (array) $request->input('metadata', []);
		$intent = $this->payments->chargeSavedPaymentMethod($amount, $user->stripe_customer_id, $paymentMethodId, $metadata, $currency);
		return response()->json(['payment_intent' => $intent]);
	}

	public function createSubscription(Request $request)
	{
		$user = Auth::user();
		$validator = Validator::make($request->all(), [
			'price_id' => 'required|string',
			'payment_method_id' => 'sometimes|string',
			'metadata' => 'sometimes|array',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}
		if (!$user->stripe_customer_id) {
			$customer = $this->payments->getOrCreateCustomer($user->email, $user->usr_name ?? null);
			$user->stripe_customer_id = $customer['id'];
			$user->save();
		}
		$priceId = (string) $request->input('price_id');
		$paymentMethodId = $request->input('payment_method_id');
		$metadata = (array) $request->input('metadata', []);
		$subscription = $this->payments->createSubscription($user->stripe_customer_id, $priceId, $paymentMethodId, $metadata);
		return response()->json(['subscription' => $subscription]);
	}
}


