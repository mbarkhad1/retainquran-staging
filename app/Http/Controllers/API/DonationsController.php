<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DonationsController extends Controller
{
	private $donationService;

	public function __construct(DonationService $donationService)
	{
		$this->donationService = $donationService;
	}

	/**
	 * Initiate a donation payment
	 */
	public function initiatePayment(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'amount' => 'required|numeric|min:1',
			'payment_type' => 'required|string|in:stripe,paypal,flutterwave',
			'payment_frequency' => 'required|string|in:one_time,monthly',
			'currency' => 'sometimes|string',
			'description' => 'sometimes|string',
			'metadata' => 'sometimes|array',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$user = Auth::user();
		$amount = (float) $request->input('amount');
		$paymentType = $request->input('payment_type');
		$paymentFrequency = $request->input('payment_frequency');
		$currency = $request->input('currency');
		$description = $request->input('description', 'Donation');
		$metadata = $request->input('metadata', []);

		try {
			$result = $this->donationService->initiatePayment(
				$user,
				$amount,
				$paymentType,
				$paymentFrequency,
				$currency,
				$description,
				$metadata
			);

			return response()->json([
				'success' => true,
				'message' => 'Payment initiated successfully',
				'data' => $result
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to initiate payment: ' . $e->getMessage()
			], 400);
		}
	}

	/**
	 * Process a one-time payment
	 */
	public function processOneTimePayment(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'amount' => 'required|numeric|min:1',
			'payment_type' => 'required|string|in:stripe,paypal,flutterwave',
			'currency' => 'sometimes|string',
			'description' => 'sometimes|string',
			'metadata' => 'sometimes|array',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$user = Auth::user();
		$amount = (float) $request->input('amount');
		$paymentType = $request->input('payment_type');
		$currency = $request->input('currency');
		$description = $request->input('description', 'Donation');
		$metadata = $request->input('metadata', []);

		try {
			$result = $this->donationService->processOneTimePayment(
				$user,
				$amount,
				$paymentType,
				$currency,
				$description,
				$metadata
			);

			return response()->json([
				'success' => true,
				'message' => 'Payment processed successfully',
				'data' => $result
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to process payment: ' . $e->getMessage()
			], 400);
		}
	}

	/**
	 * Setup monthly recurring donation
	 */
	public function setupMonthlyDonation(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'amount' => 'required|numeric|min:1',
			'payment_type' => 'required|string|in:stripe,paypal,flutterwave',
			'currency' => 'sometimes|string',
			'description' => 'sometimes|string',
			'metadata' => 'sometimes|array',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$user = Auth::user();
		$amount = (float) $request->input('amount');
		$paymentType = $request->input('payment_type');
		$currency = $request->input('currency');
		$description = $request->input('description', 'Monthly Donation');
		$metadata = $request->input('metadata', []);

		try {
			$result = $this->donationService->setupMonthlyDonation(
				$user,
				$amount,
				$paymentType,
				$currency,
				$description,
				$metadata
			);

			return response()->json([
				'success' => true,
				'message' => 'Monthly donation setup successfully',
				'data' => $result
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to setup monthly donation: ' . $e->getMessage()
			], 400);
		}
	}

	/**
	 * Get user's donation history
	 */
	public function getDonationHistory(Request $request)
	{
		$user = Auth::user();
		
		try {
			$donations = $this->donationService->getUserDonations($user);
			
			return response()->json([
				'success' => true,
				'data' => $donations
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to retrieve donation history: ' . $e->getMessage()
			], 400);
		}
	}

	/**
	 * Cancel monthly donation
	 */
	public function cancelMonthlyDonation(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'donation_id' => 'required|integer|exists:donations,id',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$user = Auth::user();
		$donationId = $request->input('donation_id');

		try {
			$result = $this->donationService->cancelMonthlyDonation($user, $donationId);
			
			return response()->json([
				'success' => true,
				'message' => 'Monthly donation cancelled successfully',
				'data' => $result
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to cancel monthly donation: ' . $e->getMessage()
			], 400);
		}
	}
}
