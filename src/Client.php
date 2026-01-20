<?php
declare(strict_types=1);

namespace InitPayCK;

use InitPayCK\Exceptions\ValidationException;
use InitPayCK\Http\HttpClient;

final class Client
{
    private string $baseUrl;
    private string $initKey;
    private string $initSecret;
    private HttpClient $http;

    public function __construct(array $config)
    {
        $this->baseUrl    = rtrim((string)($config['base_url'] ?? 'https://init-pay.com/api'), '/');
        $this->initKey    = trim((string)($config['init_key'] ?? ''));
        $this->initSecret = trim((string)($config['init_secret'] ?? ''));

        if ($this->initKey === '' || $this->initSecret === '') {
            throw new ValidationException('init_key and init_secret are required');
        }

        $timeout        = (int)($config['timeout'] ?? 20);
        $connectTimeout = (int)($config['connect_timeout'] ?? 6);
        $verifySsl      = (bool)($config['verify_ssl'] ?? true);

        $this->http = $config['http_client'] ?? new HttpClient($timeout, $connectTimeout, $verifySsl);
    }

    /** Basic base64(key:secret) */
    private function authHeader(): string
    {
        // Evita espacios raros, tabs, saltos de línea
        $user = preg_replace('/\s+/', '', $this->initKey) ?? $this->initKey;
        $pass = preg_replace('/\s+/', '', $this->initSecret) ?? $this->initSecret;

        return 'Basic ' . base64_encode($user . ':' . $pass);
    }

    /**
     * POST /create_payment
     * @param array<string,mixed> $paymentData
     * @return array<string,mixed> response JSON (decoded)
     */
    public function createPayment(array $paymentData): array
    {
        $url = $this->baseUrl . '/create_payment';

        $res = $this->http->request('POST', $url, [
            'Authorization' => $this->authHeader(),
            'Content-Type'  => 'application/json; charset=utf-8',
            'Accept'        => 'application/json',
        ], $paymentData);

        return $res['json'] ?? ['raw' => $res['raw']];
    }
}
