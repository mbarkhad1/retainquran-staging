<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Payments\XenditPaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class XenditController extends Controller
{
    private $xendit;

    public function __construct(XenditPaymentGateway $xendit)
    {
        $this->xendit = $xendit;
    }

    /**
     * Create a Xendit invoice (Checkout URL)
     */
    public function createInvoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'sometimes|string',
            'description' => 'sometimes|string',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $amount = (float) $request->input('amount');
        $currency = $request->input('currency', 'IDR');
        $description = $request->input('description', 'Donation');
        $metadata = (array) $request->input('metadata', []);

        $invoice = $this->xendit->createInvoice([
            'external_id' => 'donation_' . uniqid(),
            'payer_email' => $user->email,
            'description' => $description,
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => $metadata,
        ]);

        return response()->json([
            'invoice' => $invoice,
            'checkout_url' => $invoice['invoice_url'] ?? null,
        ]);
    }

    /**
     * Handle Xendit webhook
     */
    public function webhook(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('Xendit webhook received', $payload);

            $status = $payload['status'] ?? null;
            $externalId = $payload['external_id'] ?? null;

            if ($status === 'PAID') {
                // TODO: Update donation record where external_id matches
                Log::info("Donation paid for external_id: $externalId");
            }

            return response('OK', 200);
        } catch (\Throwable $e) {
            Log::error('Xendit webhook error', ['error' => $e->getMessage()]);
            return response('Webhook error', 400);
        }
    }

    /**
     * Get an invoice by ID
     */
    public function getInvoice(Request $request, $invoiceId)
    {
        try {
            $invoice = $this->xendit->getInvoice($invoiceId);
            return response()->json($invoice);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch Xendit invoice', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unable to fetch invoice'], 500);
        }
    }
}
