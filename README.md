# 🚀 InitPay PHP Client

**Plug. Pay. Done.**  
Cliente PHP oficial para integrar pagos seguros con USDT (TRC20) y Binance Pay mediante InitPay.

---

## 🧩 Características

- 📦 Crear sesiones de pago seguras
- 🔐 Autenticación mediante API Key y Secret
- 🔔 Soporte para Webhooks con notificaciones en tiempo real
- 💰 Procesamiento de USDT (TRC20) y Binance Pay
- ✅ Generación automática de checkout URLs
- 🛡️ Encriptación y seguridad de primera clase

---

## ⚙️ Instalación

### Requisitos

- PHP 7.4 o superior
- Extensiones: `curl`, `openssl`, `json`

### Instalación manual

```bash
# Descarga o clona la clase InitPayClient.php
# Incluye en tu proyecto

use App\Payment\InitPayClient;
require_once 'src/Payment/InitPayClient.php';
```

---

## 🔄 Flujo básico de pago

1. **Crear sesión de pago** usando tus credenciales InitPay (API Key y Secret)
2. **Redirigir al usuario** a la URL de checkout (`checkout_url`)
3. **Recibir confirmación** vía webhook cuando el pago se complete
4. **Procesar la orden** en tu sistema

---

## 🚀 Crear pago

```php
<?php

use App\Payment\InitPayClient;

// === Configuración ===
$params = [
    'init_key'     => 'YOURKEY',
    'init_secret'  => 'YOUR_SECRET_KEY',
    'systemurl'    => 'https://tu-dominio.com/',
    'clientdetails'=> [
        'firstname' => 'John',
        'lastname'  => 'Doe',
        'email'     => 'cliente@example.com'
    ]
];

// === Generar invoice ID único ===
$invoiceId = '';
for ($i = 0; $i < 5; $i++) {
    $invoiceId .= (string) random_int(1, 9);
}

// === Calcular montos ===
$amountBase = 10.00;  // Precio del servicio/producto
$fee        = 0.20;   // Tarifa de procesamiento
$total      = $amountBase + $fee;

// === Crear pago con InitPay ===
$initPay = new InitPayClient($params);
$response = $initPay
    ->buildPayload($invoiceId, $total, $amountBase, $fee)
    ->generateLink();

// === Redirigir al checkout ===
if (!empty($response['checkout_url'])) {
    header("Location: " . $response['checkout_url']);
    exit;
} else {
    echo "Error: " . json_encode($response);
}
```

---

## 💾 Guardar información de la orden (opcional)

```php
// === Crear directorio de órdenes ===
$ordersDir = __DIR__ . "/orders";
if (!is_dir($ordersDir)) {
    mkdir($ordersDir, 0755, true);
}

// === Guardar datos de la orden ===
$filePath = $ordersDir . "/" . $invoiceId . ".txt";
$data = [
    'invoiceId' => $invoiceId,
    'serial'    => 'ABC123',
    'service'   => 'Premium Plan',
    'price'     => $amountBase,
    'createdAt' => date('c')
];
file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
```

---

## 📤 Respuesta esperada de la API

La respuesta de `/api/create_payment` será:

```json
{
  "success": true,
  "checkout_url": "https://pay.bysel.us/checkout/abc123",
  "transaction_id": "txn_xyz789"
}
```

> Solo se expone el `checkout_url` por seguridad. Las credenciales permanecen protegidas.

---

## 🔌 Endpoints de la API

### Crear Pago

**Endpoint:** `POST https://pay.bysel.us/api/create_payment`

**Headers:**
```http
Content-Type: application/json
X-InitPay-Authorization: base64(API_KEY:API_SECRET)
```

**Payload:**
```json
{
  "order_id": "md5_hash",
  "invoice_number": "12345",
  "amount": 10.20,
  "currency": "usdt",
  "note": "12345",
  "brand": "YOUR_BRAND_NAME",
  "customer_name": "John Doe",
  "description": "Base: 10.00 USDT + Fee: 0.20",
  "billing_fname": "John",
  "billing_lname": "Doe",
  "billing_email": "cliente@example.com",
  "redirect_url": "https://tu-sitio.com/success",
  "cancel_url": "https://tu-sitio.com/cancel",
  "webhook_url": "https://tu-sitio.com/webhook",
  "type": "dhru",
  "items": [
    {
      "name": "Invoice #12345",
      "qty": 1,
      "price": 10.20
    }
  ]
}
```

---

## 📩 Webhook de confirmación

Cuando se confirma el pago, InitPay enviará un POST a tu `webhook_url`:

### Headers:
```http
Content-Type: application/json
```

### Body (ejemplo):
```json
{
  "status": "paid",
  "invoice_number": "12345",
  "amount": 10.20,
  "currency": "USDT",
  "billing_email": "cliente@example.com",
  "customer_name": "John Doe",
  "paid_at": "2025-01-04T15:30:00Z"
}
```

### Procesar webhook:

```php
<?php
// initpay_webhook.php

$logFile = __DIR__ . "/webhook_log.txt";

// === Helper para log ===
function logMsg($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
}

// === Leer payload tipo form-urlencoded ===
$raw = file_get_contents("php://input");
$payload = [];
parse_str($raw, $payload);

if (empty($payload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty payload']);
    logMsg("Payload vacío o inválido: $raw");
    exit;
}

logMsg("Payload recibido: " . json_encode($payload));

// === Identificar campos ===
$orderId = $payload['note'] ?? null;           // ID corto que guardaste
$status  = $payload['status_code'] ?? null;    // Estado (200 = pagado)

if ($status == 200 && $orderId) {

    if (file_exists($filePath)) {

        // TU ACCIONES AQUI
       // echo json_encode(['status' => 'SUCCESS', 'message' => 'Order found']);

    } else {
        logMsg("Archivo de orden $orderId no encontrado en /orders");
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }
}

// Si no es success
logMsg("Orden $orderId recibida con status=$status");
echo json_encode(['status' => 'ignored', 'order_id' => $orderId, 'status_code' => $status]);

```

---

## ✅ Estados de pago

| Estado | Descripción |
|--------|-------------|
| `pending` | Pago creado, esperando confirmación |
| `paid` | Pago recibido y confirmado |
| `completed` | Transacción completada exitosamente |
| `cancelled` | Pago cancelado por el usuario |
| `expired` | Pago expirado sin completar |
| `failed` | Pago fallido o rechazado |

---

## 🎨 Clase InitPayClient

### Métodos disponibles

#### `__construct(array $params)`
Inicializa el cliente con configuración.

**Parámetros:**
```php
[
    'init_key'     => 'tu_api_key',
    'init_secret'  => 'tu_api_secret',
    'systemurl'    => 'https://tu-dominio.com/',
    'clientdetails'=> [
        'firstname' => 'Nombre',
        'lastname'  => 'Apellido',
        'email'     => 'email@example.com'
    ]
]
```

#### `buildPayload(int $invoiceId, float $total, float $amountBase, float $fee): self`
Construye el payload de la transacción.

**Retorna:** `self` (método encadenable)

#### `generateLink(): array`
Ejecuta la solicitud al gateway.

**Retorna:** Array con `checkout_url` y datos de respuesta

#### `getRawResponse(): ?string`
Obtiene la respuesta cruda del servidor.

#### `getHttpCode(): ?int`
Obtiene el código HTTP de la última solicitud.

#### `getPayload(): array`
Obtiene el payload enviado al gateway.

---

## 🔒 Seguridad

### Mejores prácticas

1. ✅ **Nunca expongas** tus API keys en código frontend
2. ✅ **Usa HTTPS** en todos los endpoints
3. ✅ **Valida webhooks** verificando firmas
4. ✅ **Sanitiza entradas** del usuario
5. ✅ **Mantén logs** de todas las transacciones

### Validación de webhook (opcional):

```php
function validateWebhookSignature($payload, $signature, $secret) {
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Uso
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_INITPAY_SIGNATURE'] ?? '';

if (!validateWebhookSignature($payload, $signature, 'TU_SECRET')) {
    http_response_code(401);
    exit('Invalid signature');
}
```

---

## 📝 Logging automático

InitPay guarda logs de debug automáticamente:

**Ubicación:** `src/Payment/initpay_debug.log`

**Formato:**
```
2025-01-04 15:30:45
Payload:
{
  "order_id": "abc123",
  "amount": 10.20,
  ...
}
Response:
{"success": true, "checkout_url": "..."}
```

---

## 🧪 Testing

### Credenciales de prueba

```php
$testParams = [
    'init_key'    => 'test_xxxxx',
    'init_secret' => 'test_xxxxx',
    'systemurl'   => 'http://localhost/'
];
```

### Ejecutar test manual

```bash
php test_payment.php
```

---

## 🤝 Contribuir

¿Encontraste un bug o tienes una mejora?

1. Fork el proyecto
2. Crea una rama (`git checkout -b feature/MiMejora`)
3. Commit tus cambios (`git commit -m 'Agregué una mejora'`)
4. Push a la rama (`git push origin feature/MiMejora`)
5. Abre un Pull Request

---

## 📄 Licencia

MIT License - desarrollado con 💙 por InitPay Team

---

## 💬 Soporte

¿Necesitas ayuda?

- 📧 **Email:** support@initpay.com
- 💬 **Telegram:** [@initpay_support](https://t.me/initpay_support)
- 🐛 **Issues:** [GitHub Issues](https://github.com/initpay/php-client/issues)

---

<div align="center">

**[⬆ Volver arriba](#-initpay-php-client)**

Hecho con ❤️ por InitPay

</div>
