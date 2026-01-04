<?php

namespace App\Payment;

class InitPayClient
{
    private $gatewayUrl = "https://pay.bysel.us/api/create_payment";
    private $params;
    private $payload = [];
    private $rawResponse = null;
    private $httpCode = null;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Construye el payload de la orden
     */
    public function buildPayload(int $invoiceId, float $total, float $amountBase, float $fee): self
    {
        $currency = strtolower($this->params['currency'] ?? 'usdt');
        $firstName = $this->params['clientdetails']['firstname'] ?? 'undefine';
        $lastName  = $this->params['clientdetails']['lastname'] ?? 'undefine';
        $email     = $this->params['clientdetails']['email'] ?? 'undefine@example.com';

        $this->payload = [
            'order_id'       => md5($invoiceId),
            'invoice_number' => $invoiceId,
            'amount'         => $total,
            'currency'       => $currency,
            'note'           => $invoiceId,
            'brand'          => 'YOUR_BRAND_NAME',
            'customer_name'  => $firstName . ' ' . $lastName,
            'description'    => "Base: $amountBase USDT + Fee: $fee",
            'billing_fname'  => $firstName,
            'billing_lname'  => $lastName,
            'billing_email'  => $email,
            'redirect_url'   => $this->params['redirect_url'] ?? ($this->params['systemurl'] . 'your_redirect_url.php?id=' . $invoiceId),
            'cancel_url'     => $this->params['cancel_url'] ?? ($this->params['systemurl'] . 'your_redirect_url.php?id=' . $invoiceId),
            'webhook_url'    => $this->params['systemurl'] . '/API/initpay_webhook.php',
            'type'           => 'dhru',
            'items'          => [
                [
                    'name'  => 'Invoice #' . $invoiceId,
                    'qty'   => 1,
                    'price' => $total,
                ]
            ]
        ];

        return $this;
    }

    /**
     * Ejecuta la solicitud al gateway
     */
    public function generateLink(): array
    {
        $authEncoded = base64_encode(
            trim($this->params['init_key']) . ':' . trim($this->params['init_secret'])
        );

        $headers = [
            'Content-Type: application/json',
            'X-InitPay-Authorization: ' . $authEncoded
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->gatewayUrl,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => json_encode($this->payload),
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $response = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->rawResponse = $response;

        // Logging (debug)
        //$this->logDebug($this->payload, $response);

        return json_decode($response, true) ?? [];
    }

    /**
     * Genera un identificador único para el campo "note"
     */
    private function generateUniqueNote(): string
    {
        return str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    }


    /**
     * Guarda payload y respuesta en un log
     */
    private function logDebug(array $payload, string $response): void
    {
        $logFile = __DIR__ . '/initpay_debug.log';
        $content = date('Y-m-d H:i:s') . "\nPayload:\n" .
            json_encode($payload, JSON_PRETTY_PRINT) .
            "\nResponse:\n$response\n\n";
        file_put_contents($logFile, $content, FILE_APPEND);
    }

    // --- Getters útiles ---
    public function getRawResponse(): ?string
    {
        return $this->rawResponse;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
