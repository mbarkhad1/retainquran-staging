<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Payments\FlutterwavePaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FlutterwaveController extends Controller
{
	private $flutterwave;

	public function __construct(FlutterwavePaymentGateway $flutterwave)
	{
		$this->flutterwave = $flutterwave;
	}

	public function createPayment(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'amount' => 'required|numeric|min:1',
			'currency' => 'sometimes|string',
			'email' => 'required|email',
			'customer_name' => 'sometimes|string',
			'description' => 'sometimes|string',
			'metadata' => 'sometimes|array',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$data = [
			'amount' => $request->input('amount'),
			'currency' => $request->input('currency', config('flutterwave.currency', 'NGN')),
			'email' => $request->input('email'),
			'customer_name' => $request->input('customer_name'),
			'description' => $request->input('description', 'Donation'),
			'metadata' => $request->input('metadata', []),
		];

		$result = $this->flutterwave->createPayment($data);
		return response()->json(['payment' => $result]);
	}

	public function verifyPayment(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'transaction_id' => 'required|string',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$transactionId = $request->input('transaction_id');
		$result = $this->flutterwave->verifyPayment($transactionId);
		return response()->json(['verification' => $result]);
	}

	public function webhook(Request $request)
	{
		$signature = $request->header('verif-hash', '');
		$payload = $request->getContent();

		try {
			$result = $this->flutterwave->verifyWebhook($payload, $signature);
			if (!$result) {
				return response('Invalid signature', 400);
			}
		} catch (\Throwable $e) {
			Log::warning('Flutterwave webhook verification failed', ['error' => $e->getMessage()]);
			return response('Invalid signature', 400);
		}

		// Process webhook event
		$event = json_decode($payload, true);
		Log::info('Flutterwave webhook received', ['event' => $event]);

		return response('OK', 200);
	}

	public function ensureCustomer(Request $request)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}

		if ($user->flutterwave_customer_id) {
			return response()->json(['customer_id' => $user->flutterwave_customer_id]);
		}

		$customerData = [
			'email' => $user->email,
			'name' => $user->usr_name ?? $user->email,
		];

		$customer = $this->flutterwave->createCustomer($customerData);
		$user->flutterwave_customer_id = $customer['data']['id'] ?? null;
		$user->save();

		return response()->json(['customer_id' => $user->flutterwave_customer_id]);
	}

	public function saveCard(Request $request)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}

		$validator = Validator::make($request->all(), [
			'card_number' => 'required|string',
			'cvv' => 'required|string',
			'expiry_month' => 'required|string',
			'expiry_year' => 'required|string',
			'currency' => 'sometimes|string',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$cardData = [
			'card_number' => $request->input('card_number'),
			'cvv' => $request->input('cvv'),
			'expiry_month' => $request->input('expiry_month'),
			'expiry_year' => $request->input('expiry_year'),
			'currency' => $request->input('currency', config('flutterwave.currency', 'NGN')),
			'email' => $user->email,
		];

		$result = $this->flutterwave->saveCard($cardData);
		return response()->json(['card' => $result]);
	}

	public function chargeSavedCard(Request $request)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}

		$validator = Validator::make($request->all(), [
			'amount' => 'required|numeric|min:1',
			'card_token' => 'required|string',
			'currency' => 'sometimes|string',
			'description' => 'sometimes|string',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$chargeData = [
			'amount' => $request->input('amount'),
			'card_token' => $request->input('card_token'),
			'currency' => $request->input('currency', config('flutterwave.currency', 'NGN')),
			'email' => $user->email,
			'description' => $request->input('description', 'Donation'),
		];

		$result = $this->flutterwave->chargeCard($chargeData);
		return response()->json(['charge' => $result]);
	}

	public function createSubscription(Request $request)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}

		$validator = Validator::make($request->all(), [
			'amount' => 'required|numeric|min:1',
			'plan_name' => 'required|string',
			'interval' => 'required|string|in:weekly,monthly,yearly',
			'currency' => 'sometimes|string',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$subscriptionData = [
			'amount' => $request->input('amount'),
			'plan_name' => $request->input('plan_name'),
			'interval' => $request->input('interval'),
			'currency' => $request->input('currency', config('flutterwave.currency', 'NGN')),
			'customer_email' => $user->email,
		];

		$result = $this->flutterwave->createSubscription($subscriptionData);
		return response()->json(['subscription' => $result]);
	}
}
