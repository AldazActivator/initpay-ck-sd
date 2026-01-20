<?php
declare(strict_types=1);

namespace InitPayCK\Http;

use InitPayCK\Exceptions\HttpException;

final class HttpClient
{
    private int $timeout;
    private int $connectTimeout;
    private bool $verifySsl;

    public function __construct(int $timeout = 20, int $connectTimeout = 6, bool $verifySsl = true)
    {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->verifySsl = $verifySsl;
    }

    /**
     * @param array<string,string> $headers
     * @param array<string,mixed>|null $json
     * @return array{status:int, headers:array<string,string>, raw:string, json:array<string,mixed>|null}
     */
    public function request(string $method, string $url, array $headers = [], ?array $json = null): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new HttpException('Failed to init cURL', 0);
        }

        $method = strtoupper($method);
        $payload = null;

        if ($json !== null) {
            $payload = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($payload === false) {
                throw new HttpException('Failed to encode JSON body', 0);
            }
        }

        $finalHeaders = [];
        foreach ($headers as $k => $v) {
            $finalHeaders[] = $k . ': ' . $v;
        }

        $respHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADERFUNCTION => function ($curl, string $headerLine) use (&$respHeaders): int {
                $len = strlen($headerLine);
                $headerLine = trim($headerLine);
                if ($headerLine === '' || strpos($headerLine, ':') === false) return $len;
                [$name, $value] = explode(':', $headerLine, 2);
                $respHeaders[strtolower(trim($name))] = trim($value);
                return $len;
            },
            CURLOPT_HTTPHEADER      => $finalHeaders,
            CURLOPT_CONNECTTIMEOUT  => $this->connectTimeout,
            CURLOPT_TIMEOUT         => $this->timeout,
            CURLOPT_FOLLOWLOCATION  => false,
            CURLOPT_SSL_VERIFYPEER  => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST  => $this->verifySsl ? 2 : 0,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); // JSON string
        }

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new HttpException('HTTP request failed: ' . ($err ?: 'unknown error'), 0, null, ['url' => $url]);
        }

        $decoded = null;
        $ct = $respHeaders['content-type'] ?? '';
        if (stripos($ct, 'application/json') !== false) {
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) $decoded = $tmp;
        } else {
            // Aun así, intenta parsear JSON si parece JSON
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) $decoded = $tmp;
        }

        // Errores HTTP no-2xx
        if ($status < 200 || $status >= 300) {
            $msg = 'InitPay HTTP error ' . $status;
            if (is_array($decoded) && isset($decoded['error'])) {
                $msg .= ': ' . (string)$decoded['error'];
            }
            throw new HttpException($msg, $status, $raw, ['url' => $url, 'response' => $decoded]);
        }

        return [
            'status'  => $status,
            'headers' => $respHeaders,
            'raw'     => $raw,
            'json'    => $decoded,
        ];
    }
}
