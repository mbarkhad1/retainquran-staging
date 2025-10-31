<?php

namespace App\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class XenditPaymentGateway
{
    /** @var Client */
    private $http;

    /** @var string */
    private $secretKey;

    /** @var string */
    private $webhookToken;

    /** @var string */
    private $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('xendit.secret');
        $this->webhookToken = (string) config('xendit.webhook_token'); // x-callback-token
        $this->baseUrl = config('xendit.base_url', 'https://api.xendit.co');

        // Basic auth: username = secretKey, password = (empty)
        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'auth' => [$this->secretKey, ''],
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Create a Xendit invoice (hosted checkout / payment link)
     *
     * $amount is integer (e.g. cents or smallest currency unit) — Xendit expects amount in decimal currency (e.g. 100000 for IDR).
     * For simplicity this method expects $amount as the amount in the currency's smallest unit as you're already converting for Stripe.
     *
     * Returns the decoded JSON response array (contains invoice_url, id, status, etc.)
     */
    public function createInvoice(string $externalId, int $amount, string $payerEmail, array $params = []): array
    {
        try {
            // dd($params);
            $payload = array_merge([
                'external_id' => $externalId,
                'amount' => $amount,
                'payer_email' => $payerEmail,
                'description' => $params['description'] ?? 'Donation',
                'success_redirect_url' => $params['success_url'] ?? null,
                'failure_redirect_url' => $params['cancel_url'] ?? null,
                'items' => $params['items'] ?? null,
                'currency'  => $params['currency'] ?? 'IDR',
                // 'metadata' => $params['metadata'] ?? null, xendit dont accept empty metadata thats why commenting this
            ], []);

            // Remove nulls (Xendit dislikes null fields)
            $payload = array_filter($payload, function ($v) { return !is_null($v); });
            // dd($payload);
            $resp = $this->http->post('/v2/invoices', [
                'json' => $payload,
            ]);

            // dd($resp);

            $body = (string) $resp->getBody();
            return json_decode($body, true) ?: [];
        } catch (GuzzleException $e) {
            // Log and rethrow or return structured error
            \Log::error('Xendit createInvoice failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create a "payment request" (Payments API) — alternative to invoices if you prefer v3/payment_requests.
     * Example: POST /v3/payment_requests
     */
    public function createPaymentRequest(string $externalId, int $amount, string $payerEmail, array $params = []): array
    {
        try {
            $payload = array_filter([
                'external_id' => $externalId,
                'amount' => $amount,
                'payer_email' => $payerEmail,
                'description' => $params['description'] ?? 'Donation',
                'payment_methods' => $params['payment_methods'] ?? null,
                'metadata' => $params['metadata'] ?? null,
            ], function ($v) { return !is_null($v); });

            $resp = $this->http->post('/v3/payment_requests', [
                'json' => $payload,
            ]);

            return json_decode((string) $resp->getBody(), true) ?: [];
        } catch (GuzzleException $e) {
            \Log::error('Xendit createPaymentRequest failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Retrieve a payment / invoice by id
     */
    public function retrieveInvoice(string $invoiceId): array
    {
        try {
            $resp = $this->http->get("/v2/invoices/{$invoiceId}");
            return json_decode((string) $resp->getBody(), true) ?: [];
        } catch (GuzzleException $e) {
            \Log::error('Xendit retrieveInvoice failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verify webhook: check x-callback-token header matches your configured token.
     * $headers should be the request headers (e.g. $request->header())
     * $payload is the raw JSON body string.
     */
    public function verifyWebhook(string $payload, string $signatureHeader): array
    {
        $isValid = false;

        try {
            // Xendit webhook uses x-callback-token for verification
            if (!$signatureHeader) {
                \Log::warning('Xendit webhook missing x-callback-token header');
            } else {
                $isValid = hash_equals($this->webhookToken, (string) $signatureHeader);
                if (!$isValid) {
                    \Log::warning('Xendit webhook token mismatch');
                }
            }

            // Decode payload whether valid or not (for logging or downstream use)
            $data = json_decode($payload, true) ?? [];
            $data['verified'] = $isValid;

            return $data;
        } catch (\Throwable $e) {
            \Log::error('Xendit webhook verification error: ' . $e->getMessage());
            return ['verified' => false, 'error' => $e->getMessage()];
        }
    }


    // Optional: convenience wrapper for DonationService usage
    public function initiateDonationPayment(array $data): array
    {
        // $data should contain external_id, amount, payer_email, and optional urls/metadata
        return $this->createInvoice($data['external_id'], $data['amount'], $data['payer_email'], $data);
    }
}
