# 🚀 InitPay-ck PHP SDK

**Plug. Pay. Done.**  
La librería oficial para integrar pagos mediante la API de InitPay-ck, actuando como intermediario seguro entre tu sistema y Binance Pay.

---

## 🧩 Características

- 📦 Crear pagos con facilidad usando tu `Binance Key` y `Secret`.
- 🔐 Validación de Webhooks con HMAC (`X-InitPay-Signature`).
- 🔁 Redirección automática del usuario tras el pago.
- 🛠️ Diseñado para integrarse en bots, tiendas o sistemas personalizados.

---

## ⚙️ Instalación

Vía Composer:

```bash
composer require initpay/ck
O manualmente descarga el archivo InitPay.php y usa PSR-4.

🚀 Uso rápido

use InitPayCK\InitPay;

$initpay = new InitPay(
    'AldazDev',              // Tu API Key pública
    'BINANCE_KEY',           // Tu clave Binance
    'BINANCE_SECRET'         // Tu secreto Binance
);

$response = $initpay->createPayment([
    'amount' => 12.99,
    'note' => 'ORDER-ABC123',
    'redirect_url' => 'https://tudominio.com/thanks',
    'brand' => 'Mi Negocio',
    'customer_name' => 'Juan Pérez',
    'description' => 'Membresía 30 días',
    'image_url' => 'https://tudominio.com/img/qr.png'
]);

echo \"Pago creado: \" . $response['url'];
🛡️ Validación de Webhook

$headers = getallheaders();
$rawBody = file_get_contents('php://input');

if (InitPay::isValidWebhook($headers, $rawBody, 'tu_webhook_secret')) {
    // Procesar evento
}
📤 Requisitos
PHP >= 7.4

Extensión cURL

Clave y secreto válidos de Binance

Servidor con HTTPS para producción

