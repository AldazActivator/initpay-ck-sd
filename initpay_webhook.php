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
