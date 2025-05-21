# 🚀 InitPay-ck PHP SDK

**Plug. Pay. Done.**  
Librería oficial para integrar pagos seguros con Binance Pay mediante InitPay-ck como intermediario.

---

## 🧩 Características

- 📦 Crear sesiones de pago seguras
- 🔐 Soporte para Webhooks con firma HMAC
- 💡 Generación automática de `webhook_secret`
- ✅ Solo se expone `checkout_url` para máxima seguridad

---

## ⚙️ Instalación

```bash
composer require initpay/ck
```

---

## 🔄 Flujo básico de pago

1. Crea una sesión de pago usando tus claves de Binance y tu API Key de InitPay.
2. Redirige al usuario a la URL de pago (`checkout_url`).
3. Cuando el pago se confirme, InitPay-ck enviará una petición POST a tu `webhook_url` firmado con tu `webhook_secret`.

---

## 🚀 Crear pago

```php
use InitPayCK\InitPay;

$initpay = new InitPay(
    'AldazDev',               // API Key InitPay
    'BINANCE_KEY',            // Binance API Key
    'BINANCE_SECRET'          // Binance Secret
);

$checkoutUrl = $initpay->createPayment([
    'amount' => 9.99,
    'note' => 'ORDER-XYZ789',
    'redirect_url' => 'https://yourdomain.com/thanks',
    'brand' => 'Mi Marca',
    'customer_name' => 'Jane Doe',
    'description' => 'Membresía mensual',
    'image_url' => 'https://yourdomain.com/logo.png',
    'webhook_url' => 'https://yourdomain.com/webhook/initpay',     // Opcional pero recomendado
    // 'webhook_secret' => 'whsec_tu_clave' // Opcional; si no lo defines, se genera automáticamente
]);

header('Location: ' . $checkoutUrl);
```

---

## 📤 Respuesta esperada de la API

La respuesta de la API `/api/create_payment` será un JSON con la URL del checkout:

```json
{
  "checkout_url": "https://pay.bysel.us/checkout.php?id=ck_abc123xyz"
}
```

> Esta es la **única información expuesta** por seguridad. Los secretos y claves permanecen protegidos.

---

## 📩 Webhook de confirmación

Cuando se confirma el pago, InitPay-ck enviará un POST a tu `webhook_url` con la siguiente estructura:

### Headers:
```http
Content-Type: application/json
X-InitPay-Signature: sha256-HMAC
```

### Body (ejemplo):
```json
{
  "status": "PAID",
  "reference": "ORDER-XYZ789",
  "amount": "9.99",
  "currency": "USDT",
  "paid_at": "2025-05-20T22:16:05Z",
  "customer": "Jane Doe",
  "brand": "Mi Marca"
}
```

---

## ✅ Validación del Webhook

```php
$headers = getallheaders();
$rawBody = file_get_contents('php://input');

if (InitPay::isValidWebhook($headers, $rawBody, 'whsec_xxxxxx')) {
    $data = json_decode($rawBody, true);
    // procesar pago exitoso
}
```

---

## 🛡️ Seguridad

- El `webhook_secret` puede ser definido por ti o generado automáticamente.
- Solo se devuelve el `checkout_url`, evitando filtrar claves o secretos.

---

## 📄 Licencia

MIT – desarrollado con 💡 por AldazDev
