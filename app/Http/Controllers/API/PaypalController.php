<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Payments\PaypalPaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaypalController extends Controller
{
	private $paypal;

	public function __construct(PaypalPaymentGateway $paypal)
	{
		$this->paypal = $paypal;
	}

	public function createOrder(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'amount' => 'required|integer|min:1',
			'currency' => 'sometimes|string',
			'metadata' => 'sometimes|array',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}
		$amount = (int) $request->input('amount');
		$currency = (string) ($request->input('currency') ?: config('paypal.currency', 'USD'));
		$metadata = (array) $request->input('metadata', []);
		$order = $this->paypal->createOrder($amount, $currency, $metadata);
		return response()->json(['order' => $order]);
	}

	public function captureOrder(string $orderId)
	{
		$result = $this->paypal->captureOrder($orderId);
		return response()->json(['result' => $result]);
	}

	public function webhook(Request $request)
	{
		$webhookId = (string) config('paypal.webhook_id');
		try {
			$verification = $this->paypal->verifyWebhook(
				(string) $request->header('Paypal-Transmission-Id'),
				(string) $request->header('Paypal-Transmission-Time'),
				(string) $request->header('Paypal-Transmission-Sig'),
				$webhookId,
				$request->getContent(),
				(string) $request->header('Paypal-Cert-Url'),
				(string) $request->header('Paypal-Auth-Algo')
			);
			if (($verification['verification_status'] ?? '') !== 'SUCCESS') {
				return response('Invalid signature', 400);
			}
		} catch (\Throwable $e) {
			Log::warning('PayPal webhook verification failed', ['error' => $e->getMessage()]);
			return response('Invalid signature', 400);
		}
		return response('OK', 200);
	}

	public function ensurePayer(Request $request)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}
		// Payer id comes from PayPal checkout approval. We'll store it on capture.
		return response()->json(['paypal_payer_id' => $user->paypal_payer_id]);
	}
}


