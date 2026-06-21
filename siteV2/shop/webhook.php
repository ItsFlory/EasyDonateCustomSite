<?php
require_once __DIR__ . '/helpers.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Проверка цифровой подписи
$hashString = implode('@', [
    $data->payment_id ?? '',
    $data->cost ?? '',
    $data->customer ?? ''
]);
$expectedSignature = hash_hmac('sha256', $hashString, SHOP_KEY);

if (strcasecmp($expectedSignature, $data->signature ?? '') !== 0) {
    http_response_code(403);
    exit('Bad signature');
}

// Ищем среди купленных товаров кейс
$caseId = null;
$caseName = null;
$isCase = false;

foreach ($data->products ?? [] as $product) {
    if (!empty($product->commands)) {
        foreach ($product->commands as $cmd) {
            if (preg_match('/выбил:/u', $cmd)) {
                $isCase = true;
                $caseId = (int)$product->product_id;
                $caseName = $product->name;
                break 2;
            }
        }
    }
}

$payments = loadPayments();
$paymentId = (int)$data->payment_id;

if (isset($payments[$paymentId])) {
    // Обновляем только статус, не затирая multi-spin данные (spins_total, spins_left, won_items)
    $payments[$paymentId]['status'] = 'paid';
    $payments[$paymentId]['customer'] = $data->customer ?? $payments[$paymentId]['customer'];
    if ($isCase) {
        $payments[$paymentId]['case_id'] = $caseId;
        $payments[$paymentId]['case_name'] = $caseName ?? $payments[$paymentId]['case_name'];
    }
} else {
    // Новая запись
    $entry = [
        'customer' => $data->customer ?? '',
        'case_id' => $caseId ?? 0,
        'case_name' => $caseName ?? 'Кейс',
        'status' => 'paid',
        'used' => false,
        'cost' => $data->cost ?? 0,
        'created_at' => $data->created_at ?? date('Y-m-d H:i:s'),
    ];

    if ($isCase) {
        $entry['spins_total'] = 1;
        $entry['spins_left'] = 1;
        $entry['won_items'] = [];
        $entry['pending_item'] = null;
        $entry['pending_item_id'] = null;
        $entry['pending_amount'] = null;
    }

    $payments[$paymentId] = $entry;
}

savePayments($payments);

http_response_code(200);
echo 'OK';
