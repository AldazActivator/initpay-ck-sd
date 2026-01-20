# InitPayCK (initpay/ck)

Official PHP SDK for **InitPay-ck**: Plug. Pay. Done.

- PHP `>= 7.4`
- Autoload PSR-4: `InitPayCK\\` → `src/`

---

## Installation

```bash
composer require initpay/ck
```

---

## Quick Start

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use InitPayCK\Client;

$initpay = new Client([
  'init_key' => 'YOUR_INIT_KEY',
  'init_secret' => 'YOUR_INIT_SECRET',
  // optional:
  'base_url' => 'https://init-pay.com/api',
  'timeout' => 20,
  'connect_timeout' => 6,
  'verify_ssl' => true,
]);

$payment = $initpay->createPayment([
  "brand" => "InitPay Inc",
  "payer" => [
    "payment_method" => "InitPay"
  ],
  "transactions" => [[
    "amount" => [
      "total" => "4.50",
      "details" => [
        "shipping_discount" => "1.00",
        "insurance" => "1.00",
        "handling_fee" => "1.00"
      ]
    ],
    "description" => "iPhone 15 Pro Max Purchase",
    "payment_options" => [
      "allowed_payment_method" => "IMMEDIATE_PAY"
    ]
  ]],
  "redirect_urls" => [
    "webhook_url" => "https://example.com/webhook.php",
    "return_url"  => "https://example.com/return",
    "cancel_url"  => "https://example.com/cancel"
  ]
]);

print_r($payment);
```

### Authorization (Basic)

The SDK sends:

```
Authorization: Basic base64(init_key:init_secret)
```

You only provide `init_key` and `init_secret`; the SDK builds the header automatically.

---

## createPayment()

### Request fields (example)

```json
{
  "brand": "AldazDev Inc",
  "payer": { "payment_method": "InitPay" },
  "transactions": [{
    "amount": {
      "total": "4.5",
      "details": { "shipping_discount": "1.00", "insurance": "1.00", "handling_fee": "1.00" }
    },
    "description": "iPhone 15 Pro Max Purchase",
    "payment_options": { "allowed_payment_method": "IMMEDIATE_PAY" }
  }],
  "redirect_urls": {
    "webhook_url": "https://example.com/webhook.php",
    "return_url": "https://example.com/return",
    "cancel_url": "https://example.com/cancel"
  }
}
```

### Response (typical)

The API usually returns a JSON object that includes identifiers like `order_id`, links, and/or `payment_data`.
Exact fields depend on your server implementation.

---

## Webhook (payment.completed)

When your server detects the payment and updates the order to `completed`, it should POST a JSON payload to
`redirect_urls.webhook_url`.

### Recommended behavior (send once)

Send the webhook **only once**, only when the payment **transitions** to `completed` (e.g. `pending` → `completed`),
not on every status poll.

Safe pattern:

- `UPDATE ... WHERE status <> 'completed'`
- if `affected_rows === 1` → transition happened → send webhook
- else → already completed → do not send again

---

## Webhook payload (example)

```json
{
  "event": "payment.completed",
  "status": "completed",
  "order_id": "6278775f-559a-4404-a05a-60262819365f",
  "note": 156117,
  "payment_link_id": 2,
  "brand": "InitPay Payment Link",
  "method": "trc20",
  "amount": {
    "db": "10.10",
    "base_amount": "10.00",
    "final_amount": "10.10",
    "fee_amount": "0.10"
  },
  "description": "YOUR_PRODUCT",
  "transaction": {
    "amount": "10.10",
    "network": "TRX"
  },
  "payment_data": {
    "note": 156117,
    "brand": "InitPay Payment Link"
  },
  "completed_at": "2026-01-19T18:22:01Z"
}
```

---

## Webhook receiver (example: `webhook.php`)

```php
<?php
declare(strict_types=1);

// 1) Read raw body
$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);

// 2) Basic validation
if (!is_array($data) || ($data['event'] ?? '') !== 'payment.completed') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid webhook payload']);
  exit;
}

// 3) Use order_id to mark it paid in your system
$orderId = (string)($data['order_id'] ?? '');
if ($orderId === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing order_id']);
  exit;
}

// TODO: lookup order in your DB, confirm amount/status, then fulfill order

http_response_code(200);
echo json_encode(['ok' => true]);
```

---

## Handling errors

The SDK throws exceptions for invalid configuration or HTTP errors:

- `InitPayCK\Exceptions\ValidationException`
- `InitPayCK\Exceptions\HttpException`

Example:

```php
<?php
use InitPayCK\Client;
use InitPayCK\Exceptions\HttpException;
use InitPayCK\Exceptions\ValidationException;

try {
  $client = new Client([
    'init_key' => 'YOUR_INIT_KEY',
    'init_secret' => 'YOUR_INIT_SECRET',
  ]);

  $res = $client->createPayment([ /* ... */ ]);
} catch (ValidationException $e) {
  echo $e->getMessage();
} catch (HttpException $e) {
  echo $e->getMessage();
  // Optional:
  // var_dump($e->context());
}
```

---

## Security notes (recommended)

- Always use HTTPS for `webhook_url`.
- Validate `event`, `order_id`, and amounts before fulfilling.
- If you control both sides, consider adding a signature header (HMAC) to webhooks.

---

## License

MIT
