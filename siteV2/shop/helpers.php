<?php

$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Значения по умолчанию (только для разработки)
    define('SHOP_KEY',      '');
    define('SERVER_ID',     0);
    define('RCON_HOST',     '127.0.0.1');
    define('RCON_PORT',     25575);
    define('RCON_PASSWORD', '');
    define('RCON_TIMEOUT',  3);
    define('PAYMENTS_FILE', __DIR__ . '/payments.json');
    define('TEST_MODE_FILE', __DIR__ . '/test_mode.json');
    define('ADMIN_PASS',    'admin');
}

function loadPayments(): array
{
    if (!file_exists(PAYMENTS_FILE)) {
        return [];
    }
    $data = @file_get_contents(PAYMENTS_FILE);
    $decoded = json_decode($data, true);
    return is_array($decoded) ? $decoded : [];
}

function savePayments(array $payments): void
{
    $fp = fopen(PAYMENTS_FILE, 'c+');
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($payments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function weightedRandom(array $items): ?array
{
    $total = array_sum(array_column($items, 'weight'));
    if ($total <= 0) return $items[0] ?? null;
    $rand = mt_rand(1, $total);
    foreach ($items as $item) {
        $rand -= $item['weight'];
        if ($rand <= 0) return $item;
    }
    return $items[0] ?? null;
}

function easyDonateRequest(string $url): ?array
{
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Shop-Key: " . SHOP_KEY . "\r\n"
        ],
        "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return null;
    return json_decode($response, true);
}

function isCaseProduct(array $product): bool
{
    if (!empty($product['commands'])) {
        foreach ($product['commands'] as $cmd) {
            if (preg_match('/выбил:/u', $cmd)) {
                return true;
            }
        }
    }
    return false;
}

function parseCaseItems(array $product): array
{
    $items = [];
    foreach ($product['commands'] as $command) {
        preg_match('/выбил:\s+(.+?)(?:\s*\(|$)/u', $command, $nameMatches);
        preg_match('/\(([\d.]+)%\)/', $command, $chanceMatches);
        preg_match('/give\s+\{?\w+\}?\s+(\S+)\s+(\d+)/u', $command, $itemIdMatches);

        $rawId = $itemIdMatches[1] ?? null;
        // Нормализуем item_id: убираем дублирующийся minecraft: префикс
        if ($rawId && str_starts_with($rawId, 'minecraft:')) {
            $rawId = substr($rawId, 10);
        }
        $itemId = $rawId;

        $amount = $itemIdMatches[2] ?? 1;
        $chance = (float)($chanceMatches[1] ?? 0);
        $name = $nameMatches[1] ?? ($itemId ? ucfirst($itemId) : "Предмет");

        $weight = max(1, (int)round($chance * 100));

        $items[] = [
            'name'     => $name,
            'chance'   => $chance,
            'weight'   => $weight,
            'item_id'  => $itemId,
            'amount'   => (int)$amount,
        ];
    }
    return $items;
}

function isTestMode(): bool
{
    if (!file_exists(TEST_MODE_FILE)) {
        return false;
    }
    $data = @file_get_contents(TEST_MODE_FILE);
    $decoded = json_decode($data, true);
    return !empty($decoded['enabled']);
}

function setTestMode(bool $enabled): void
{
    file_put_contents(TEST_MODE_FILE, json_encode(['enabled' => $enabled], JSON_PRETTY_PRINT));
}
