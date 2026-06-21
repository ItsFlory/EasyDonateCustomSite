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
    // define('ADMIN_PASS', ''); — fallback не задан, доступ будет только через config.php
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

function sslContext(): array {
    $ctx = ["verify_peer" => true, "verify_peer_name" => true];
    $cafile = __DIR__ . '/cacert.pem';
    if (file_exists($cafile)) {
        $ctx['cafile'] = $cafile;
    }
    return $ctx;
}

function apiRequest(string $url, int $timeout = 10): ?array {
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Shop-Key: " . SHOP_KEY . "\r\n",
            "timeout" => $timeout,
        ],
        "ssl" => sslContext()
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $err = error_get_last();
        error_log('EasyDonate API error: ' . ($err['message'] ?? 'unknown') . ' URL: ' . $url);
        return null;
    }
    return json_decode($response, true);
}

function easyDonateRequest(string $url): ?array {
    return apiRequest($url);
}

function isCaseProduct(array $product): bool
{
    if (!empty($product['commands'])) {
        $text = implode(' ', $product['commands']);
        if (preg_match('/выбил:|\(\d+\.?\d*%\)/u', $text)) {
            return true;
        }
    }
    return false;
}

function parseCaseItems(array $product): array
{
    $items = [];

    foreach ($product['commands'] as $command) {
        $segments = explode(';', $command);

        $current = null;

        foreach ($segments as $seg) {
            $seg = trim($seg);
            if (!$seg) continue;

            // give-команда — начало нового предмета
            if (preg_match('/give\s+\{?\w+\}?\s+(\S+)\s+(\d+)/u', $seg, $m)) {
                if ($current) {
                    $current['weight'] = max(1, (int)round($current['chance'] * 100));
                    $items[] = $current;
                }
                $rawId = $m[1];
                if (substr($rawId, 0, 10) === 'minecraft:') {
                    $rawId = substr($rawId, 10);
                }
                $current = [
                    'item_id' => $rawId,
                    'amount'  => (int)$m[2],
                    'name'    => ucfirst($rawId),
                    'chance'  => 0,
                ];
            }

            // выбил: — имя предмета
            if (preg_match('/выбил:\s*(.+?)(?:\s*\(|$)/u', $seg, $m)) {
                if (!$current) $current = ['item_id' => null, 'amount' => 1, 'name' => 'Предмет', 'chance' => 0];
                $current['name'] = trim($m[1]);
            }

            // шанс в любом сегменте
            if (preg_match('/\(([\d.]+)%\)/', $seg, $m)) {
                if (!$current) $current = ['item_id' => null, 'amount' => 1, 'name' => 'Предмет', 'chance' => 0];
                $current['chance'] = (float)$m[1];
            }
        }

        if ($current) {
            $current['weight'] = max(1, (int)round($current['chance'] * 100));
            $items[] = $current;
        }
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

// Транзакционная блокировка payments.json: открывает файл, блокирует, передаёт
// данные в $callback, записывает результат и снимает блокировку.
// $callback получает массив платежей (по ссылке) и должен вернуть true при успехе.
// Возвращает true, если callback успешно выполнен.
function withPaymentsLock(callable $callback): bool
{
    $fp = @fopen(PAYMENTS_FILE, 'c+');
    if (!$fp) {
        error_log('withPaymentsLock: не удалось открыть ' . PAYMENTS_FILE);
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        error_log('withPaymentsLock: не удалось получить блокировку');
        fclose($fp);
        return false;
    }

    rewind($fp);
    $contents = stream_get_contents($fp);
    $payments = json_decode($contents, true);
    if (!is_array($payments)) {
        $payments = [];
    }

    $result = $callback($payments);

    if ($result) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($payments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
    }

    flock($fp, LOCK_UN);
    fclose($fp);
    return $result;
}

// Простой Rate Limiter на файловой основе
function checkRateLimit(string $key, int $maxRequests = 10, int $windowSec = 60): bool
{
    $dir = sys_get_temp_dir() . '/easydonate_ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    $file = $dir . '/' . $safeKey . '.lock';
    $now = time();

    $fp = @fopen($file, 'c+');
    if (!$fp) return true; // если не можем открыть — пропускаем (fail open)

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return true;
    }

    $data = @stream_get_contents($fp);
    $timestamps = json_decode($data, true);
    if (!is_array($timestamps)) {
        $timestamps = [];
    }

    // Удаляем старые записи
    $timestamps = array_values(array_filter($timestamps, fn($t) => $t > $now - $windowSec));

    if (count($timestamps) >= $maxRequests) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }

    $timestamps[] = $now;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($timestamps));
    fflush($fp);

    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}
