<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->publicKey = config('services.paystack.public_key');
    }

    /**
     * Initialize a payment transaction
     */
    public function initializeTransaction(array $data): array
    {
        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transaction/initialize', [
                'email' => $data['email'],
                'amount' => $data['amount'] * 100, // Convert to kobo
                'reference' => $data['reference'] ?? $this->generateReference(),
                'callback_url' => $data['callback_url'] ?? config('app.url') . '/api/wakalinelogistics/v1/credits/verify',
                'metadata' => $data['metadata'] ?? [],
            ]);

            $result = $response->json();

            if ($response->successful() && $result['status']) {
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'message' => 'Transaction initialized successfully',
                ];
            }

            Log::error('Paystack initialization failed', ['response' => $result]);

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initialize transaction',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack initialization error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'An error occurred while initializing payment',
            ];
        }
    }

    /**
     * Verify a payment transaction
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transaction/verify/' . $reference);

            $result = $response->json();

            if ($response->successful() && $result['status']) {
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'message' => 'Transaction verified successfully',
                ];
            }

            Log::error('Paystack verification failed', ['response' => $result]);

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to verify transaction',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack verification error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'An error occurred while verifying payment',
            ];
        }
    }

    /**
     * Get transaction details
     */
    public function getTransaction(string $reference): array
    {
        return $this->verifyTransaction($reference);
    }

    /**
     * List all transactions
     */
    public function listTransactions(array $params = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transaction', $params);

            $result = $response->json();

            if ($response->successful() && $result['status']) {
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'message' => 'Transactions retrieved successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to retrieve transactions',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack list transactions error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'An error occurred while retrieving transactions',
            ];
        }
    }

    /**
     * Generate a unique payment reference
     */
    public function generateReference(): string
    {
        return 'WKL-' . strtoupper(uniqid()) . '-' . time();
    }

    /**
     * Get public key for frontend
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $hash = hash_hmac('sha512', $payload, $this->secretKey);
        return hash_equals($hash, $signature);
    }
}
