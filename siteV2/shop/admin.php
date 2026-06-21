<?php
session_start();

// Безопасные настройки сессии
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_lifetime', 1800);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

require_once __DIR__ . '/helpers.php';

// Конфигурация читается из config.php (через helpers.php)
$SHOP_KEY   = SHOP_KEY;
$ADMIN_PASS = defined('ADMIN_PASS') ? ADMIN_PASS : '';

// Таймаут сессии 30 минут
if (isset($_SESSION['auth']) && $_SESSION['auth']) {
    if (isset($_SESSION['login_time']) && time() - $_SESSION['login_time'] > 1800) {
        session_destroy();
        header('Location: ?');
        exit;
    }
    $_SESSION['login_time'] = time();
}

// Rate limiting: 5 попыток, бан 15 минут
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = [];
$_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], fn($t) => $t > time() - 900);
if (count($_SESSION['login_attempts']) >= 5) {
    $remaining = 900 - (time() - min($_SESSION['login_attempts']));
    if ($remaining > 0) {
        if (isset($_POST['pass'])) {
            echo '<body style="background:#0f1218;color:white;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;flex-direction:column;">';
            echo '<h2>Слишком много попыток</h2><p style="color:#8b949e;margin-top:10px;">Повторите через ' . ceil($remaining / 60) . ' мин.</p></body>';
            exit;
        }
    } else {
        $_SESSION['login_attempts'] = [];
    }
}

if (isset($_POST['pass'])) {
    if ($_POST['pass'] === $ADMIN_PASS) {
        $_SESSION['auth'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['login_attempts'] = [];
    } else {
        $_SESSION['login_attempts'][] = time();
        header('Location: ?');
        exit;
    }
}
if (!isset($_SESSION['auth'])):
?>
<body style="background:#0f1218;color:white;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;">
    <form method="POST" style="background:#1c222d;padding:40px;border-radius:15px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.5);">
        <h2 style="margin-bottom:20px;">EasyAnalytics Login</h2>
        <input type="password" name="pass" placeholder="Пароль" style="padding:12px;width:200px;border-radius:5px;border:1px solid #333;background:#000;color:white;margin-bottom:20px;"><br>
        <button type="submit" style="padding:10px 30px;background:#2ecc71;border:none;border-radius:5px;color:white;font-weight:bold;cursor:pointer;">Войти</button>
    </form>
</body>
<?php exit; endif;

// --- КЭШИРОВАНИЕ ---
$cacheFile        = sys_get_temp_dir() . '/easydonate_stats_cache.json';
$productCacheFile = sys_get_temp_dir() . '/easydonate_products_cache.json';
$cacheTtl         = 300;  // 5 минут — основная статистика
$productCacheTtl  = 3600; // 1 час — товары меняются редко

// ── Вспомогательная функция: платёжный бейдж ──────────────────────────────
function paymentBadge(string $raw): string {
    static $map = [
        'sbp'       => ['СБП',    '#00b4d8', '⚡'],
        'card'      => ['Карта',  '#4361ee', '💳'],
        'qiwi'      => ['QIWI',   '#ff8500', '🥝'],
        'yoomoney'  => ['ЮМани',  '#8b5cf6', '💜'],
        'youmoney'  => ['ЮМани',  '#8b5cf6', '💜'],
        'tinkoff'   => ['Тинькофф','#f9d54a','🟡'],
        'sber'      => ['Сбер',   '#21a038', '💚'],
        'test'      => ['Тест',   '#888888', '🧪'],
        'other'     => ['Другое', '#555555', '💰'],
    ];
    $key  = strtolower(trim($raw));
    $info = $map[$key] ?? $map['other'];
    [$label, $color, $icon] = $info;
    if ($key !== 'test' && $key !== 'other' && !isset($map[$key])) {
        $label = strtoupper($raw) ?: '?';
    }
    return "<span style='background:{$color}22;color:{$color};border:1px solid {$color}55;"
         . "padding:2px 8px;border-radius:4px;font-size:11px;font-weight:bold;white-space:nowrap;'>"
         . "{$icon} {$label}</span>";
}

// ── Аватар Minecraft-игрока ─────────────────────────────────────────────────
// Ники в fullStats хранятся уже через htmlspecialchars — декодируем перед urlencode
function mcAvatar(string $nick, int $size = 20): string {
    $raw  = html_entity_decode($nick, ENT_QUOTES, 'UTF-8'); // снять HTML-кодирование
    $url  = 'https://mc-heads.net/avatar/' . rawurlencode($raw) . '/' . $size;
    $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    return "<img src='{$safe}' "
         . "width='{$size}' height='{$size}' "
         . "style='border-radius:3px;vertical-align:middle;margin-right:6px;image-rendering:pixelated;' "
         . "loading='lazy' onerror=\"this.style.display='none'\">";
}

function getCachedStats(string $file, int $ttl): ?array {
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        $raw = @file_get_contents($file);
        if ($raw) return json_decode($raw, true);
    }
    return null;
}

// ── Загрузка товаров (с кэшем) ──────────────────────────────────────────────
// Возвращает map: нормализованное_имя => url_картинки
// Нормализация: trim + lowercase — чтобы матч работал даже при разнице пробелов/регистра
function normalizeProductName(string $name): string {
    return strtolower(trim(html_entity_decode($name, ENT_QUOTES, 'UTF-8')));
}

function fetchProducts(): array {
    global $SHOP_KEY, $productCacheFile, $productCacheTtl;
    $cached = getCachedStats($productCacheFile, $productCacheTtl);
    if ($cached !== null) return $cached;

    $map  = [];
    // Сначала пробуем без server_id (некоторые магазины отдают все товары)
    // Потом, если пусто — пробуем с первым найденным server_id из платежей
    $urls = [
        "https://easydonate.ru/api/v3/shop/products",
        // server_id подставляется ниже динамически если первый запрос пуст
    ];

    foreach ($urls as $url) {
        $opts = [
            "http" => ["method" => "GET", "header" => "Shop-Key: $SHOP_KEY\r\n", "timeout" => 10],
            "ssl"  => ["verify_peer" => true, "verify_peer_name" => true],
        ];
        $res  = @file_get_contents($url, false, stream_context_create($opts));
        if (!$res) continue;
        $data = json_decode($res, true);
        if (!isset($data['response']) || !is_array($data['response'])) continue;
        if (empty($data['response'])) continue;

        foreach ($data['response'] as $p) {
            $rawName = (string)($p['name'] ?? '');
            $img     = $p['image'] ?? null;
            if (!$rawName || !$img) continue;
            // Сохраняем по нормализованному ключу — совпадёт даже при разнице пробелов
            $map[normalizeProductName($rawName)] = $img;
            // Также по точному htmlspecialchars-ключу (на случай точного совпадения)
            $map[htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8')] = $img;
        }
        if (!empty($map)) break;  // Данные получены, выходим
    }

    @file_put_contents($productCacheFile, json_encode($map));
    return $map;
}

// Безопасный поиск картинки товара в карте (сначала exact, потом нормализованный ключ)
function getProductImage(array $productImages, string $name): ?string {
    // 1. Точное совпадение (имя уже через htmlspecialchars)
    if (isset($productImages[$name])) return $productImages[$name];
    // 2. Нормализованный ключ
    $norm = normalizeProductName($name);
    if (isset($productImages[$norm])) return $productImages[$norm];
    return null;
}

// ── Основной сбор статистики ─────────────────────────────────────────────────
function getAdvancedStats(): array {
    global $SHOP_KEY;
    set_time_limit(300);
    $globalRevenue = 0;
    $currentPage   = 1;
    $stats = [
        'unique_customers' => [],
        'payment_systems'  => [],
        'hourly_activity'  => array_fill(0, 24, 0),
        'daily_revenue'    => [],
        'payment_counts'   => [],
        'customer_dates'   => [],
        'last_purchase'    => [],
        'heatmap'          => [],
        'product_totals'   => [],
        'product_daily'    => [],
        'product_customers'=> [],
        'product_customers_email'=> [],
        'basket_pairs'     => [],
        'customer_emails'  => [],
        'total_payments'   => 0,
        'pages_fetched'    => 0,
        'all_pages_loaded' => false,
    ];

    do {
        $url  = "https://easydonate.ru/api/v3/shop/payments?paginate=50&page={$currentPage}";
        $opts = [
            "http" => ["method" => "GET", "header" => "Shop-Key: $SHOP_KEY\r\n", "timeout" => 30],
            "ssl"  => ["verify_peer" => true, "verify_peer_name" => true],
        ];
        $res  = @file_get_contents($url, false, stream_context_create($opts));
        if (!$res) break;
        $data = json_decode($res, true);
        if (!isset($data['response'])) break;
        $resp = isset($data['response'][0]) ? $data['response'][0] : $data['response'];

        if (!isset($resp['data']) || !is_array($resp['data']) || empty($resp['data'])) break;

        $stats['pages_fetched']++;

        foreach ($resp['data'] as $p) {
            if (strtolower($p['payment_type'] ?? '') === 'test') continue;
            if ((int)($p['status'] ?? 0) !== 2) continue;

            $enrolled = (float)($p['enrolled'] ?? 0);
            $cost     = (float)($p['cost'] ?? $enrolled);
            $user     = htmlspecialchars((string)($p['customer'] ?? 'Ghost'), ENT_QUOTES, 'UTF-8');
            $ts       = strtotime($p['created_at']);
            $hour     = (int)date('G', $ts);
            $day      = date('Y-m-d', $ts);
            $dow      = (int)date('w', $ts);

            $globalRevenue += $enrolled;
            $stats['total_payments']++;
            $stats['unique_customers'][$user]  = ($stats['unique_customers'][$user]  ?? 0) + $enrolled;
            $stats['payment_counts'][$user]    = ($stats['payment_counts'][$user]    ?? 0) + 1;
            $stats['customer_dates'][$user][]  = $ts;

            if (!isset($stats['customer_emails'][$user]) && !empty($p['email'])) {
                $stats['customer_emails'][$user] = htmlspecialchars((string)$p['email'], ENT_QUOTES, 'UTF-8');
            }

            if (!isset($stats['last_purchase'][$user]) || $ts > $stats['last_purchase'][$user]) {
                $stats['last_purchase'][$user] = $ts;
            }
            $stats['heatmap'][$dow . '_' . $hour] = ($stats['heatmap'][$dow . '_' . $hour] ?? 0) + 1;
            $stats['daily_revenue'][$day]         = ($stats['daily_revenue'][$day] ?? 0) + $enrolled;

            $rawSys = $p['payment_type'] ?? ($p['payment_system'] ?? '');
            $sys    = strtoupper(trim($rawSys)) ?: 'ДРУГОЕ';
            $stats['payment_systems'][$sys] = ($stats['payment_systems'][$sys] ?? 0) + 1;
            $stats['hourly_activity'][$hour]++;

            $productNamesInOrder = [];
            if (isset($p['products']) && is_array($p['products'])) {
                foreach ($p['products'] as $pr) {
                    $name  = htmlspecialchars((string)($pr['name'] ?? 'Товар'), ENT_QUOTES, 'UTF-8');
                    $price = (float)($pr['price'] ?? 0);
                    if (!isset($stats['product_totals'][$name])) {
                        $stats['product_totals'][$name] = ['transactions' => 0, 'revenue' => 0];
                    }
                    $lineTotalCost   = (float)($pr['total_cost'] ?? $price);
                    $commissionRatio = ($cost > 0) ? ($enrolled / $cost) : 1;
                    $productRevenue  = $lineTotalCost * $commissionRatio;
                    $stats['product_totals'][$name]['transactions'] += 1;
                    $stats['product_totals'][$name]['revenue']      += $productRevenue;
                    $stats['product_daily'][$name][$day] = ($stats['product_daily'][$name][$day] ?? 0) + $productRevenue;
                    $stats['product_customers'][$name][$user] = true;
                    $pEmail = !empty($p['email']) ? strtolower(trim((string)$p['email'])) : $user;
                    $stats['product_customers_email'][$name][$pEmail] = true;
                    $productNamesInOrder[] = $name;
                }
            }
            $unique = array_unique($productNamesInOrder);
            sort($unique);
            $uc = count($unique);
            for ($i = 0; $i < $uc; $i++) {
                for ($j = $i + 1; $j < $uc; $j++) {
                    $key = $unique[$i] . '|||' . $unique[$j];
                    $stats['basket_pairs'][$key] = ($stats['basket_pairs'][$key] ?? 0) + 1;
                }
            }
        }

        $hasNext = $resp['next_page_url'] ?? null;
        $currentPage++;
        usleep(200000);
    } while ($hasNext !== null);

    $stats['all_pages_loaded'] = true;
    $stats['global_revenue']   = $globalRevenue;
    return $stats;
}

// Сброс кэша
if (isset($_GET['refresh'])) {
    @unlink($cacheFile);
    header('Location: ?page=1');
    exit;
}
if (isset($_GET['refresh_products'])) {
    @unlink($productCacheFile);
    header('Location: ?page=1');
    exit;
}

// ТЕСТОВЫЙ РЕЖИМ
if (isset($_GET['toggle_test'])) {
    setTestMode(!isTestMode());
    header('Location: ?');
    exit;
}

// Загружаем/обновляем кэш
$fullStats = getCachedStats($cacheFile, $cacheTtl);
$needsRebuild = !$fullStats
    || !isset($fullStats['payment_counts'])
    || !isset($fullStats['daily_revenue'])
    || !isset($fullStats['customer_dates'])
    || !isset($fullStats['heatmap'])
    || !isset($fullStats['last_purchase'])
    || !isset($fullStats['basket_pairs'])
    || !isset($fullStats['customer_emails'])
    || !isset($fullStats['product_daily'])
    || !isset($fullStats['product_customers'])
    || !isset($fullStats['product_customers_email'])
    || !isset($fullStats['all_pages_loaded']) || !$fullStats['all_pages_loaded'];

if ($needsRebuild) {
    @unlink($cacheFile);
    $fullStats = getAdvancedStats();
    @file_put_contents($cacheFile, json_encode($fullStats));
}

// Товары (иконки)
$productImages = fetchProducts();

// ── Базовые метрики ────────────────────────────────────────────────────────
$globalRevenue       = $fullStats['global_revenue'];
$totalUnique         = count($fullStats['unique_customers']);
$arpu                = ($totalUnique > 0) ? ($globalRevenue / $totalUnique) : 0;
$avgPaymentsPerUser  = ($totalUnique > 0) ? ($fullStats['total_payments'] / $totalUnique) : 0;

// Анализ ценообразования
$productDaily  = $fullStats['product_daily'] ?? [];
$productCust   = $fullStats['product_customers'] ?? [];
$productCustEmail = $fullStats['product_customers_email'] ?? [];
$cutoff30      = date('Y-m-d', time() - 30 * 86400);
$cutoff60      = date('Y-m-d', time() - 60 * 86400);
$cutoff7       = date('Y-m-d', time() - 7 * 86400);
$cutoff7prev   = date('Y-m-d', time() - 14 * 86400);
$priceEffectiveness = [];
foreach ($fullStats['product_totals'] as $name => $data) {
    $avgCheck = ($data['transactions'] > 0) ? $data['revenue'] / $data['transactions'] : 0;
    $share    = ($globalRevenue > 0) ? ($data['revenue'] / $globalRevenue) : 0;

    $recent30Rev = 0;
    $recent60Rev = 0;
    $recent7Rev  = 0;
    $prev7Rev    = 0;
    if (isset($productDaily[$name])) {
        foreach ($productDaily[$name] as $day => $rev) {
            if ($day >= $cutoff30)                       $recent30Rev += $rev;
            if ($day >= $cutoff60)                       $recent60Rev += $rev;
            if ($day >= $cutoff7)                        $recent7Rev  += $rev;
            if ($day >= $cutoff7prev && $day < $cutoff7) $prev7Rev    += $rev;
        }
    }

    $totalRev  = $data['revenue'];
    $relPct    = $totalRev > 0 ? min(100, round($recent30Rev / $totalRev * 100, 1)) : 0;

    $uniqueCust = count($productCust[$name] ?? []);
    $avgPerCust = $uniqueCust > 0 ? round($data['transactions'] / $uniqueCust, 1) : 0;
    $uniqueCustEmail = count($productCustEmail[$name] ?? []);

    if ($recent60Rev <= 0 && $totalRev > 100) {
        $relStatus = 'ghost';
        $relLabel  = '💤 Устарел';
    } elseif ($relPct > 50) {
        $relStatus = 'hot';
        $relLabel  = '✅ Актуален';
    } elseif ($relPct > 20) {
        $relStatus = 'normal';
        $relLabel  = '📊 Нормально';
    } elseif ($relPct > 5) {
        $relStatus = 'dying';
        $relLabel  = '⏳ Угасает';
    } elseif ($totalRev > 100) {
        $relStatus = 'ghost';
        $relLabel  = '💤 Устарел';
    } else {
        $relStatus = 'unknown';
        $relLabel  = '⚪ Мало данных';
    }

    $trend7 = 0;
    if ($prev7Rev > 0) {
        $trend7 = round(($recent7Rev - $prev7Rev) / $prev7Rev * 100, 1);
    }

    $priceEffectiveness[] = [
        'name'       => $name,
        'purchases'  => $data['transactions'],
        'avg_check'  => $avgCheck,
        'revenue'    => $data['revenue'],
        'score'      => $share,
        'rel_pct'    => $relPct,
        'rel_status' => $relStatus,
        'rel_label'  => $relLabel,
        'trend7'     => $trend7,
        'customers'  => $uniqueCust,
        'per_cust'   => $avgPerCust,
        'customers_email' => $uniqueCustEmail,
    ];
}
usort($priceEffectiveness, fn($a, $b) => $b['score'] <=> $a['score']);

// Медиана трат
$customerSpends = array_values($fullStats['unique_customers']);
sort($customerSpends);
$n           = count($customerSpends);
$medianSpend = $n === 0 ? 0 : ($n % 2 === 1 ? $customerSpends[(int)($n/2)] : ($customerSpends[$n/2-1] + $customerSpends[$n/2]) / 2);

// Киты
$whaleThreshold = $medianSpend * 2;
$whalesCount    = count(array_filter($customerSpends, fn($v) => $v >= $whaleThreshold));
$whalesRevenue  = array_sum(array_filter($customerSpends, fn($v) => $v >= $whaleThreshold));

// Повторные покупки
$paymentCounts  = $fullStats['payment_counts'] ?? [];
$repeatBuyers   = count(array_filter($paymentCounts, fn($v) => $v > 1));
$repeatRate     = ($n > 0) ? ($repeatBuyers / $n * 100) : 0;

$allCounts      = array_values($paymentCounts);
sort($allCounts);
$rn             = count($allCounts);
$medianRepeatCount = $rn === 0 ? 0 : ($rn % 2 === 1 ? $allCounts[(int)($rn/2)] : ($allCounts[$rn/2-1] + $allCounts[$rn/2]) / 2);

// Retention
$customerDates = $fullStats['customer_dates'] ?? [];
$cohortSize = $ret1Count = $ret3Count = 0;
$now = time();
foreach ($customerDates as $user => $timestamps) {
    sort($timestamps);
    $firstTs = $timestamps[0];
    if (($now - $firstTs) < 76 * 3600) continue;
    $cohortSize++;
    foreach (array_slice($timestamps, 1) as $ts) {
        $diff = $ts - $firstTs;
        if ($diff >= 20*3600 && $diff <= 28*3600) $ret1Count++;
        if ($diff >= 68*3600 && $diff <= 76*3600) $ret3Count++;
    }
}
$retention1 = ($cohortSize > 0) ? round($ret1Count / $cohortSize * 100, 1) : 0;
$retention3 = ($cohortSize > 0) ? round($ret3Count / $cohortSize * 100, 1) : 0;

$medianArpuRatio = ($arpu > 0) ? ($medianSpend / $arpu) : 0;

// ── Скорость "смерти" ────────────────────────────────────────────────────────
// Для каждого игрока с ≥2 покупками: кол-во дней от первой до последней покупки
$lifespans = [];
foreach ($customerDates as $user => $timestamps) {
    sort($timestamps);
    if (count($timestamps) >= 2) {
        $lifespans[] = ($timestamps[count($timestamps)-1] - $timestamps[0]) / 86400;
    }
}
sort($lifespans);
$ln = count($lifespans);
$medianLifespan = $ln === 0 ? 0
    : ($ln % 2 === 1 ? $lifespans[(int)($ln/2)] : ($lifespans[$ln/2-1] + $lifespans[$ln/2]) / 2);
// Быстрые покупатели — те у кого lifespan ≤ 1 дня (купил дважды за сутки и замолчал)
$fastDead = $ln > 0 ? round(count(array_filter($lifespans, fn($v) => $v <= 1)) / $ln * 100, 1) : 0;

// ── Market basket — топ пар ──────────────────────────────────────────────────
$basketPairs = $fullStats['basket_pairs'] ?? [];
arsort($basketPairs);
$topBasket = array_slice($basketPairs, 0, 8, true);

// ── Тренд выручки (7д vs прошлые 7д) ────────────────────────────────────────
$revenue7     = 0;
$revenue7prev = 0;
$revenue30    = 0;
foreach (($fullStats['daily_revenue'] ?? []) as $day => $rev) {
    $daysAgo = (time() - strtotime($day)) / 86400;
    if ($daysAgo <= 7)          $revenue7     += $rev;
    if ($daysAgo > 7 && $daysAgo <= 14) $revenue7prev += $rev;
    if ($daysAgo <= 30)         $revenue30    += $rev;
}
$trend7 = ($revenue7prev > 0)
    ? round(($revenue7 - $revenue7prev) / $revenue7prev * 100, 1)
    : null;
$trendArrow  = $trend7 === null ? '—' : ($trend7 >= 0 ? '▲' : '▼');
$trendColor  = $trend7 === null ? '#8b949e' : ($trend7 >= 0 ? '#2ecc71' : '#e74c3c');

// ── Рекорд дня ───────────────────────────────────────────────────────────────
$bestDay        = '';
$bestDayRevenue = 0;
$todayKey       = date('Y-m-d');
$todayRevenue   = $fullStats['daily_revenue'][$todayKey] ?? 0;
foreach (($fullStats['daily_revenue'] ?? []) as $day => $rev) {
    if ($day === $todayKey) continue; // Сравниваем только с завершёнными днями
    if ($rev > $bestDayRevenue) { $bestDayRevenue = $rev; $bestDay = $day; }
}
$isNewRecord       = $todayRevenue > 0 && $todayRevenue > $bestDayRevenue;
$recordProgress    = $bestDayRevenue > 0 ? min(100, round($todayRevenue / $bestDayRevenue * 100, 1)) : 0;

// ── Цель месяца ──────────────────────────────────────────────────────────────
$monthGoalMin  = 1316;
$monthGoal     = 8000;
$currentMonth  = date('Y-m');
$revenueMonth  = 0;
foreach (($fullStats['daily_revenue'] ?? []) as $day => $rev) {
    if (substr($day, 0, 7) === $currentMonth) $revenueMonth += $rev;
}
$monthPct      = min(100, ($monthGoal > 0) ? ($revenueMonth / $monthGoal * 100) : 0);
$monthMinPct   = min(100, round($monthGoalMin / $monthGoal * 100, 1));
$monthDaysLeft = (int)date('t') - (int)date('j');
$monthDailyAvg = ((int)date('j') > 0) ? ($revenueMonth / (int)date('j')) : 0;
$monthForecast = $revenueMonth + $monthDailyAvg * $monthDaysLeft;
if ($revenueMonth >= $monthGoal)         $monthStatus = 'goal';
elseif ($revenueMonth >= $monthGoalMin)  $monthStatus = 'min';
else                                     $monthStatus = 'none';

$avgOrderValue = ($fullStats['total_payments'] > 0) ? ($globalRevenue / $fullStats['total_payments']) : 0;

// Рекомендации
$shares = array_column($priceEffectiveness, 'score');
sort($shares);
$medianShare   = count($shares) > 0 ? $shares[(int)(count($shares) / 2)] : 0;
$highThreshold = $medianShare * 3;
$lowPriceMax   = 100;
$hotSalesMin   = 10;

function getRecommendation(array $item, float $highThreshold, float $lowPriceMax, int $hotSalesMin): string {
    if ($item['score'] >= $highThreshold)
        return '<span style="color:#2ecc71;font-weight:bold;">★ Локомотив</span>';
    if ($item['avg_check'] <= $lowPriceMax && $item['purchases'] >= $hotSalesMin)
        return '<span style="color:#f1c40f;">Дешёвый хит</span>';
    if ($item['score'] < 0.01 && $item['purchases'] < 3)
        return '<span style="color:#e74c3c;">Аутсайдер</span>';
    return '<span style="color:#8b949e;">Стабильно</span>';
}

// Мёртвые игроки
$deadPlayers  = [];
$deadRevenue  = 0;
$threshold30  = time() - 30 * 86400;
$lastPurchase = $fullStats['last_purchase'] ?? [];
foreach ($lastPurchase as $user => $ts) {
    if ($ts < $threshold30) {
        $deadPlayers[] = [
            'user'        => $user,
            'last_ts'     => $ts,
            'days_ago'    => (int)((time() - $ts) / 86400),
            'total_spent' => $fullStats['unique_customers'][$user] ?? 0,
        ];
        $deadRevenue += $fullStats['unique_customers'][$user] ?? 0;
    }
}
usort($deadPlayers, fn($a, $b) => $b['total_spent'] <=> $a['total_spent']);
$deadCount = count($deadPlayers);

// Инсайты (без изменений — логика прежняя)
$insights = [];
foreach ($priceEffectiveness as $item) {
    if ($item['score'] > 0.5) {
        $insights[] = ['type' => 'danger', 'icon' => '⚠️', 'title' => 'Высокий риск концентрации',
            'text' => '«' . $item['name'] . '» даёт ' . round($item['score']*100,1) . '% всей выручки. Если этот товар перестанет продаваться — магазин потеряет больше половины дохода. Рекомендуется развивать другие товары.'];
    }
}
$avgPurchasesPerProduct = $fullStats['total_payments'] > 0 ? $fullStats['total_payments'] / max(1, count($priceEffectiveness)) : 0;
foreach ($priceEffectiveness as $item) {
    if ($item['avg_check'] > $avgOrderValue * 1.5 && $item['purchases'] < $avgPurchasesPerProduct * 0.5 && $item['purchases'] >= 1) {
        $insights[] = ['type' => 'opportunity', 'icon' => '💎', 'title' => 'Недооценённый товар: «' . $item['name'] . '»',
            'text' => 'Средний чек ' . number_format($item['avg_check'], 0) . ' ₽ — в ' . round($item['avg_check'] / max(1,$avgOrderValue), 1) . 'x выше среднего по магазину, но куплен всего ' . $item['purchases'] . ' раз. Стоит добавить баннер или упомянуть в описании сервера.'];
    }
}
foreach ($priceEffectiveness as $item) {
    if ($item['score'] > 0.3 && $item['avg_check'] < $avgOrderValue * 0.6) {
        $insights[] = ['type' => 'tip', 'icon' => '📈', 'title' => 'Потенциал роста цены: «' . $item['name'] . '»',
            'text' => 'Товар даёт ' . round($item['score']*100,1) . '% выручки при цене ' . number_format($item['avg_check'], 0) . ' ₽ — ниже среднего чека (' . number_format($avgOrderValue, 0) . ' ₽). Тест: поднять цену на 15–20% и отследить динамику за неделю.'];
        break;
    }
}
if ($cohortSize >= 5 && $retention1 < 10) {
    $insights[] = ['type' => 'danger', 'icon' => '📉', 'title' => 'Низкий Day-1 Retention (' . $retention1 . '%)',
        'text' => 'Менее 1 из 10 покупателей возвращается на следующий день. Варианты: ежедневные бонусы за вход, ограниченные по времени акции, «пополняемые» товары (расходники).'];
} elseif ($cohortSize >= 5 && $retention1 >= 25) {
    $insights[] = ['type' => 'positive', 'icon' => '🔥', 'title' => 'Отличный Day-1 Retention (' . $retention1 . '%)',
        'text' => 'Каждый четвёртый покупатель возвращается уже на следующий день. Хороший момент чтобы ввести накопительную систему лояльности или подписочную модель.'];
}
if ($medianArpuRatio < 0.4 && $whalesCount > 0) {
    $insights[] = ['type' => 'danger', 'icon' => '🐋', 'title' => 'Доход зависит от ' . $whalesCount . ' китов',
        'text' => 'Медиана трат (' . number_format($medianSpend, 0) . ' ₽) составляет лишь ' . round($medianArpuRatio*100, 0) . '% от ARPU (' . number_format($arpu, 0) . ' ₽). Стоит добавить товары в среднем ценовом диапазоне.'];
}
if ($repeatRate < 20 && $totalUnique >= 5) {
    $insights[] = ['type' => 'tip', 'icon' => '🔄', 'title' => 'Мало повторных покупок (' . round($repeatRate, 1) . '%)',
        'text' => 'Лишь каждый пятый игрок покупает больше одного раза. Идеи: расходные товары, сезонные обновления кейсов, скидки для вернувшихся.'];
}

// ── Товары-призраки (были хитами, теперь мёртвы) ────────────────────────────
$ghostProducts = [];
foreach ($priceEffectiveness as $item) {
    if ($item['rel_status'] === 'ghost' && $item['revenue'] > 200 && $item['purchases'] >= 5) {
        $ghostProducts[] = $item;
    }
}
if (count($ghostProducts) > 0) {
    $ghostNames = array_slice(array_column($ghostProducts, 'name'), 0, 3);
    $ghostList  = '«' . implode('», «', $ghostNames) . '»';
    $ghostCount = count($ghostProducts);
    $insights[] = ['type' => 'danger', 'icon' => '👻', 'title' => $ghostCount . ' товар' . ($ghostCount === 1 ? '' : 'а') . '-призрак' . ($ghostCount === 1 ? '' : 'а'),
        'text' => $ghostList . ' — были популярны раньше, но за последние 60 дней не принесли значимого дохода. Стоит убрать из витрины или перезапустить с новой ценой/описанием.'];
}

// Текущая страница транзакций
function getPayments(int $page = 1): ?array {
    global $SHOP_KEY;
    $url  = "https://easydonate.ru/api/v3/shop/payments?paginate=50&page={$page}";
    $opts = [
        "http" => ["method" => "GET", "header" => "Shop-Key: $SHOP_KEY\r\n", "timeout" => 10],
        "ssl"  => ["verify_peer" => true, "verify_peer_name" => true],
    ];
    $res  = @file_get_contents($url, false, stream_context_create($opts));
    $data = json_decode($res, true);
    return isset($data['response'][0]) ? $data['response'][0] : ($data['response'] ?? null);
}

$page        = max(1, (int)($_GET['page'] ?? 1));
$paymentData = getPayments($page);
$payments    = $paymentData['data']      ?? [];
$lastPage    = $paymentData['last_page'] ?? 1;
$pageRevenue = 0;
foreach ($payments as $p) {
    if (strtolower($p['payment_type'] ?? '') !== 'test' && (int)($p['status'] ?? 0) === 2) {
        $pageRevenue += (float)($p['enrolled'] ?? 0);
    }
}

$cacheAge = file_exists($cacheFile) ? (time() - filemtime($cacheFile)) : 0;

// --- Навигация по вкладкам и сортировка ---
$tab = $_GET['tab'] ?? 'dashboard';
$selectedProductName = isset($_GET['product']) ? trim($_GET['product']) : null;
$selectedProductItem = null;
$selectedProductDaily = [];
$selectedProductCustomers = [];
$selectedProductCustSpends = [];

$sortBy    = $_GET['sort'] ?? 'score';
$sortOrder = $_GET['order'] ?? 'desc';

$sortMap = [
    'score'     => 'Доле',
    'revenue'   => 'Выручке',
    'purchases' => 'Продажам',
    'avg_check' => 'Ср. чеку',
    'customers' => 'Игрокам',
    'trend7'    => 'Тренду 7д',
    'rel_pct'   => 'Актуальности',
    'name'      => 'Названию',
];

$priceEffectivenessSorted = $priceEffectiveness;
usort($priceEffectivenessSorted, function ($a, $b) use ($sortBy, $sortOrder) {
    if ($sortBy === 'name') {
        $cmp = strcasecmp($a['name'], $b['name']);
    } else {
        $cmp = $a[$sortBy] <=> $b[$sortBy];
    }
    return $sortOrder === 'desc' ? -$cmp : $cmp;
});

if ($selectedProductName) {
    foreach ($priceEffectiveness as $item) {
        if ($item['name'] === $selectedProductName) {
            $selectedProductItem = $item;
            $selectedProductDaily = $productDaily[$selectedProductName] ?? [];
            $selectedProductCustomers = array_keys($productCust[$selectedProductName] ?? []);
            foreach ($selectedProductCustomers as $cust) {
                $selectedProductCustSpends[$cust] = $fullStats['unique_customers'][$cust] ?? 0;
            }
            arsort($selectedProductCustSpends);
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Аналитика</title>
    <style>
        * { box-sizing:border-box; }
        body { background:#0b0e14; color:#ced4da; font-family:'Inter',sans-serif; margin:0; padding:20px; }
        .container { max-width:1200px; margin:0 auto; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-bottom:25px; }
        .card { background:#161b22; border:1px solid #30363d; padding:20px; border-radius:12px; }
        .card h3 { margin:0 0 10px 0; font-size:11px; color:#8b949e; text-transform:uppercase; letter-spacing:1px; }
        .card .value { font-size:22px; font-weight:bold; color:#ffffff; }
        .card .sub { font-size:12px; color:#8b949e; margin-top:4px; }
        table { width:100%; border-collapse:collapse; background:#161b22; border-radius:12px; overflow:hidden; border:1px solid #30363d; }
        th { background:#21262d; padding:12px; text-align:left; font-size:11px; color:#8b949e; }
        td { padding:10px 12px; border-top:1px solid #30363d; font-size:13px; vertical-align:middle; }
        tr:hover td { background:#1c2330; }
        .status-paid { color:#2ecc71; font-weight:bold; font-size:10px; border:1px solid #2ecc71; padding:2px 6px; border-radius:4px; }
        .pagination { margin-top:20px; display:flex; gap:10px; align-items:center; }
        .pagination a { padding:8px 16px; background:#161b22; border:1px solid #30363d; border-radius:6px; color:#ced4da; text-decoration:none; }
        .pagination a:hover { background:#21262d; }
        .pagination .current { padding:8px 16px; color:#8b949e; }
        .cache-bar { background:#161b22; border:1px solid #30363d; border-radius:8px; padding:10px 16px; margin-bottom:20px; font-size:12px; color:#8b949e; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
        .cache-bar a { color:#3498db; text-decoration:none; }
        .warn { color:#f1c40f; font-size:11px; margin-left:8px; }
        .prod-img { width:24px; height:24px; border-radius:4px; object-fit:cover; vertical-align:middle; margin-right:6px; image-rendering:pixelated; }
        .trend-badge { font-size:13px; font-weight:bold; padding:2px 8px; border-radius:6px; }
        /* Pulse animation для рекорда */
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.6} }
        .record-pulse { animation:pulse 1.5s ease-in-out infinite; }
    </style>
</head>
<body>
<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h1 style="margin:0;"><?= $tab === 'catalog' ? 'Каталог товаров' : 'Аналитика магазина' ?></h1>
        <div style="display:flex;gap:8px;">
            <a href="?tab=dashboard" style="padding:8px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;<?= $tab === 'dashboard' ? 'background:#2ecc71;color:#0b0e14;' : 'background:#21262d;color:#8b949e;' ?>">Dashboard</a>
            <a href="?tab=catalog" style="padding:8px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;<?= $tab === 'catalog' ? 'background:#2ecc71;color:#0b0e14;' : 'background:#21262d;color:#8b949e;' ?>">Каталог</a>
        </div>
    </div>

    <?php if ($tab === 'dashboard'): ?>
    <div class="cache-bar">
        <span>
            Статистика обновлена: <b><?= $cacheAge < 10 ? 'только что' : gmdate('i:s', $cacheAge) . ' назад' ?></b>
            <?php if (!$fullStats['all_pages_loaded']): ?>
                <span class="warn">⚠ Загружены не все страницы, данные могут быть неполными</span>
            <?php else: ?>
                <span style="color:#2ecc71;margin-left:8px;">✓ Все данные загружены</span>
            <?php endif; ?>
        </span>
        <span style="display:flex;gap:12px;align-items:center;">
            <a href="?refresh=1">↻ Обновить статистику</a>
            <a href="?refresh_products=1">↻ Обновить товары</a>
            <span style="width:1px;height:20px;background:#30363d;display:inline-block;"></span>
            <?php $testMode = isTestMode(); ?>
            <a href="?toggle_test=1" style="display:flex;align-items:center;gap:6px;text-decoration:none;padding:4px 12px;border-radius:6px;<?= $testMode ? 'background:rgba(46,204,113,0.12);border:1px solid rgba(46,204,113,0.2);' : 'background:#21262d;border:1px solid #30363d;' ?>">
                <span style="width:10px;height:10px;border-radius:50%;display:inline-block;<?= $testMode ? 'background:#2ecc71;box-shadow:0 0 8px rgba(46,204,113,0.4);' : 'background:#555;' ?>"></span>
                <span style="font-size:13px;font-weight:600;<?= $testMode ? 'color:#2ecc71;' : 'color:#8b949e;' ?>"><?= $testMode ? 'Тестовый режим ВКЛ' : 'Тестовый режим ВЫКЛ' ?></span>
            </a>
        </span>
    </div>

    <!-- Цель месяца -->
    <div class="card" style="margin-bottom:25px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
            <div>
                <h3 style="margin:0 0 6px;">Цель месяца — <?= date('m.Y') ?></h3>
                <div style="font-size:26px;font-weight:bold;color:#fff;"><?= number_format($revenueMonth, 2, '.', ' ') ?> ₽</div>
            </div>
            <div style="text-align:right;">
                <?php if ($monthStatus === 'goal'): ?>
                    <div style="font-size:22px;font-weight:bold;color:#2ecc71;">🎉 Цель!</div>
                <?php elseif ($monthStatus === 'min'): ?>
                    <div style="font-size:22px;font-weight:bold;color:#f1c40f;">✓ Расходы покрыты</div>
                <?php else: ?>
                    <div style="font-size:22px;font-weight:bold;color:#e74c3c;">✗ Расходы не покрыты</div>
                <?php endif; ?>
                <div style="font-size:11px;color:#8b949e;margin-top:2px;">осталось <?= $monthDaysLeft ?> дн.</div>
            </div>
        </div>
        <div style="position:relative;margin-bottom:28px;">
            <div style="background:#21262d;border-radius:8px;height:16px;overflow:visible;position:relative;">
                <div style="width:<?= $monthPct ?>%;height:100%;border-radius:8px;background:<?= $monthStatus === 'goal' ? '#2ecc71' : ($monthStatus === 'min' ? '#f1c40f' : '#e74c3c') ?>;transition:width .5s;position:relative;"></div>
                <div style="position:absolute;top:-4px;left:<?= $monthMinPct ?>%;transform:translateX(-50%);width:3px;height:24px;background:#fff;border-radius:2px;box-shadow:0 0 6px rgba(0,0,0,0.8);z-index:2;"></div>
            </div>
            <div style="position:relative;height:20px;margin-top:4px;">
                <div style="position:absolute;left:<?= $monthMinPct ?>%;transform:translateX(-50%);font-size:10px;color:#8b949e;white-space:nowrap;text-align:center;">
                    <?= number_format($monthGoalMin, 0) ?> ₽<br><span style="color:#aaa;">расходы</span>
                </div>
                <div style="position:absolute;right:0;font-size:10px;color:#8b949e;text-align:right;white-space:nowrap;">
                    <?= number_format($monthGoal, 0) ?> ₽<br><span style="color:#aaa;">желаемая</span>
                </div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;border-top:1px solid #30363d;padding-top:14px;">
            <div>
                <div style="font-size:11px;color:#8b949e;margin-bottom:3px;">Среднее в день</div>
                <div style="font-size:15px;font-weight:bold;color:#fff;"><?= number_format($monthDailyAvg, 2, '.', ' ') ?> ₽</div>
            </div>
            <div>
                <div style="font-size:11px;color:#8b949e;margin-bottom:3px;">Прогноз к концу месяца</div>
                <div style="font-size:15px;font-weight:bold;color:<?= $monthForecast >= $monthGoal ? '#2ecc71' : ($monthForecast >= $monthGoalMin ? '#f1c40f' : '#e74c3c') ?>"><?= number_format($monthForecast, 2, '.', ' ') ?> ₽</div>
            </div>
            <div>
                <?php if ($revenueMonth < $monthGoalMin): ?>
                    <div style="font-size:11px;color:#8b949e;margin-bottom:3px;">До покрытия расходов</div>
                    <div style="font-size:15px;font-weight:bold;color:#e74c3c;"><?= number_format($monthGoalMin - $revenueMonth, 2, '.', ' ') ?> ₽</div>
                <?php elseif ($revenueMonth < $monthGoal): ?>
                    <div style="font-size:11px;color:#8b949e;margin-bottom:3px;">До желаемой цели</div>
                    <div style="font-size:15px;font-weight:bold;color:#f1c40f;"><?= number_format($monthGoal - $revenueMonth, 2, '.', ' ') ?> ₽</div>
                <?php else: ?>
                    <div style="font-size:11px;color:#8b949e;margin-bottom:3px;">Сверх цели</div>
                    <div style="font-size:15px;font-weight:bold;color:#2ecc71;">+<?= number_format($revenueMonth - $monthGoal, 2, '.', ' ') ?> ₽</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Основные метрики с трендом -->
    <div class="grid">
        <div class="card" style="border-left:4px solid #2ecc71;">
            <h3>Общая выручка</h3>
            <div class="value"><?= number_format($globalRevenue, 2, '.', ' ') ?> ₽</div>
            <div class="sub"><?= $fullStats['total_payments'] ?> платежей · <?= $totalUnique ?> игроков</div>
        </div>
        <div class="card" style="border-left:4px solid #9b59b6;">
            <h3>ARPU</h3>
            <div class="value"><?= number_format($arpu, 2, '.', ' ') ?> ₽</div>
            <div class="sub">Средний доход с покупателя</div>
        </div>
        <div class="card" style="border-left:4px solid #3498db;">
            <h3>Медиана трат</h3>
            <div class="value"><?= number_format($medianSpend, 2, '.', ' ') ?> ₽</div>
            <div class="sub">Типичный игрок тратит столько</div>
        </div>
        <div class="card" style="border-left:4px solid #e67e22;">
            <h3>Средний чек</h3>
            <div class="value"><?= number_format($avgOrderValue, 2, '.', ' ') ?> ₽</div>
            <div class="sub">Выручка / кол-во платежей</div>
        </div>
    </div>

    <!-- Новые метрики: тренд + рекорд дня + скорость смерти -->
    <div class="grid">
        <!-- Тренд 7д -->
        <div class="card" style="border-left:4px solid <?= $trendColor ?>;">
            <h3>Тренд выручки (7 дней)</h3>
            <div class="value" style="color:<?= $trendColor ?>;">
                <?= $trendArrow ?>
                <?= $trend7 !== null ? abs($trend7) . '%' : '—' ?>
            </div>
            <div class="sub">
                Эта неделя: <?= number_format($revenue7, 0, '.', ' ') ?> ₽<br>
                Прошлая: <?= number_format($revenue7prev, 0, '.', ' ') ?> ₽
            </div>
        </div>

        <!-- Рекорд дня -->
        <div class="card" style="border-left:4px solid <?= $isNewRecord ? '#f1c40f' : '#30363d' ?>;">
            <h3>Рекорд дня <?= $isNewRecord ? '<span class="record-pulse">🏆</span>' : '' ?></h3>
            <?php if ($isNewRecord): ?>
                <div class="value" style="color:#f1c40f;">🔥 Новый рекорд!</div>
                <div class="sub">Сегодня <?= number_format($todayRevenue, 0, '.', ' ') ?> ₽ · предыдущий <?= number_format($bestDayRevenue, 0, '.', ' ') ?> ₽</div>
            <?php else: ?>
                <div class="value" style="font-size:16px;"><?= $bestDay ? date('d.m.Y', strtotime($bestDay)) : 'N/A' ?></div>
                <div style="background:#21262d;border-radius:6px;height:6px;margin:8px 0;">
                    <div style="width:<?= $recordProgress ?>%;background:#f1c40f;height:6px;border-radius:6px;"></div>
                </div>
                <div class="sub">Рекорд <?= number_format($bestDayRevenue, 0, '.', ' ') ?> ₽ · сегодня <?= $recordProgress ?>%</div>
            <?php endif; ?>
        </div>

        <!-- Скорость "смерти" -->
        <div class="card" style="border-left:4px solid #e74c3c;">
            <h3>Скорость «смерти» игрока</h3>
            <div class="value"><?= $medianLifespan > 0 ? round($medianLifespan) . ' дн.' : 'N/A' ?></div>
            <div class="sub">
                Медиана активного периода<br>
                <?= $fastDead ?>% уходят в первые сутки
                <?php if ($ln > 0): ?>· выборка <?= $ln ?> игр.<?php endif; ?>
            </div>
        </div>

        <!-- Выручка за 30 дней -->
        <div class="card">
            <h3>Выручка за 30 дней</h3>
            <div class="value"><?= number_format($revenue30, 2, '.', ' ') ?> ₽</div>
            <div class="sub">За 7 дней: <?= number_format($revenue7, 2, '.', ' ') ?> ₽</div>
        </div>
    </div>

    <!-- Удержание и киты -->
    <div class="grid">
        <div class="card" style="border-left:4px solid #e74c3c;">
            <h3>Киты 🐋</h3>
            <div class="value"><?= $whalesCount ?> чел.</div>
            <div class="sub"><?= number_format($whalesRevenue, 2, '.', ' ') ?> ₽ · порог <?= number_format($whaleThreshold, 0) ?> ₽+</div>
        </div>
        <div class="card" style="border-left:4px solid #1abc9c;">
            <h3>Повторные покупки</h3>
            <div class="value"><?= round($repeatRate, 1) ?>%</div>
            <div class="sub"><?= $repeatBuyers ?> из <?= $totalUnique ?> · медиана: <?= number_format($medianRepeatCount, 1) ?> пок.</div>
        </div>
        <div class="card">
            <h3>Час пик</h3>
            <?php
            $hourlyData = $fullStats['hourly_activity'];
            arsort($hourlyData);
            $peakHour = key($hourlyData);
            $peakCount = current($hourlyData);
            ?>
            <div class="value"><?= $peakHour ?>:00</div>
            <div class="sub"><?= $peakCount ?> покупок в этот час</div>
        </div>
        <div class="card">
            <h3>Топ метод оплаты</h3>
            <?php
            arsort($fullStats['payment_systems']);
            $topSys = key($fullStats['payment_systems']);
            $topSysRaw = strtolower($topSys);
            ?>
            <div style="margin-top:6px;"><?= paymentBadge($topSysRaw) ?></div>
            <div class="sub" style="margin-top:8px;"><?= current($fullStats['payment_systems']) ?> транзакций</div>
        </div>
    </div>

<!-- Топ меценатов + распределение трат -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
        <div class="card">
            <h3>Топ-7 меценатов</h3>
            <?php
            $customers = $fullStats['unique_customers'];
            arsort($customers);
            $maxSpend = reset($customers) ?: 1;
            foreach (array_slice($customers, 0, 7, true) as $u => $s):
            ?>
                <div style="margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px;align-items:center;">
                        <span><?= mcAvatar($u, 20) ?><b><?= $u ?></b></span>
                        <span style="color:#2ecc71;"><?= number_format($s, 2, '.', ' ') ?> ₽ · <?= $paymentCounts[$u] ?? 1 ?> пл.</span>
                    </div>
                    <div style="background:#21262d;border-radius:4px;height:4px;">
                        <div style="width:<?= round($s / $maxSpend * 100) ?>%;background:#2ecc71;height:4px;border-radius:4px;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="card">
            <h3>Распределение игроков по тратам</h3>
            <?php
            $buckets = ['< 100₽' => 0, '100–300₽' => 0, '300–500₽' => 0, '500–1000₽' => 0, '1000₽+' => 0];
            foreach ($fullStats['unique_customers'] as $s) {
                if ($s < 100)       $buckets['< 100₽']++;
                elseif ($s < 300)   $buckets['100–300₽']++;
                elseif ($s < 500)   $buckets['300–500₽']++;
                elseif ($s < 1000)  $buckets['500–1000₽']++;
                else                $buckets['1000₽+']++;
            }
            $maxBucket = max($buckets) ?: 1;
            $colors = ['#3498db','#2ecc71','#f1c40f','#e67e22','#e74c3c'];
            $bi = 0;
            foreach ($buckets as $label => $cnt):
            ?>
                <div style="margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px;">
                        <span><?= $label ?></span>
                        <span style="color:<?= $colors[$bi] ?>;"><?= $cnt ?> игр. · <?= $n > 0 ? round($cnt/$n*100,1) : 0 ?>%</span>
                    </div>
                    <div style="background:#21262d;border-radius:4px;height:6px;">
                        <div style="width:<?= $maxBucket > 0 ? round($cnt/$maxBucket*100) : 0 ?>%;background:<?= $colors[$bi] ?>;height:6px;border-radius:4px;"></div>
                    </div>
                </div>
            <?php $bi++; endforeach; ?>
            <div style="font-size:11px;color:#8b949e;margin-top:8px;">
                Медиана: <b style="color:#fff;"><?= number_format($medianSpend, 2, '.', ' ') ?> ₽</b> ·
                Китов (≥<?= number_format($whaleThreshold, 0) ?>₽): <b style="color:#e74c3c;"><?= $whalesCount ?></b>
            </div>
        </div>
    </div>

<!-- Аналитика по Email -->
    <?php
    // Считаем метрики по email
    $emailSpends  = [];
    $emailCounts  = [];
    $emailNicks   = [];

    foreach ($fullStats['customer_emails'] as $user => $email) {
        if (!$email) continue;
        $spent = $fullStats['unique_customers'][$user] ?? 0;
        $emailSpends[$email] = ($emailSpends[$email] ?? 0) + $spent;
        $emailCounts[$email] = ($emailCounts[$email] ?? 0) + ($fullStats['payment_counts'][$user] ?? 1);
        $emailNicks[$email][] = $user;
    }
    arsort($emailSpends);

    $emailValues       = array_values($emailSpends);
    $emailTotal        = count($emailValues);
    $emailTotalRevenue = array_sum($emailValues);
    $emailTotalPayments= array_sum($emailCounts);

    // ARPU по email
    $emailArpu = $emailTotal > 0 ? $emailTotalRevenue / $emailTotal : 0;

    // Медиана по email
    sort($emailValues);
    $em = count($emailValues);
    $emailMedian = $em === 0 ? 0 : ($em % 2 === 1 ? $emailValues[(int)($em/2)] : ($emailValues[$em/2-1] + $emailValues[$em/2]) / 2);

    // Средний чек по email
    $emailAvgCheck = $emailTotalPayments > 0 ? $emailTotalRevenue / $emailTotalPayments : 0;

    // Киты по email
    $emailWhaleThreshold = $emailMedian * 2;
    $emailWhalesCount    = count(array_filter($emailValues, fn($v) => $v >= $emailWhaleThreshold));
    $emailWhalesRevenue  = array_sum(array_filter($emailValues, fn($v) => $v >= $emailWhaleThreshold));

    // Повторные покупки по email
    $emailRepeatBuyers = count(array_filter($emailCounts, fn($v) => $v > 1));
    $emailRepeatRate   = $emailTotal > 0 ? round($emailRepeatBuyers / $emailTotal * 100, 1) : 0;

    $emailArpuRatio = $emailArpu > 0 ? $emailMedian / $emailArpu : 0;
    $maxEmailSpend  = max($emailValues ?: [1]);
    ?>

    <?php if (!empty($emailSpends)): ?>
    <div style="margin-bottom:25px;">
        <h3 style="margin-bottom:14px;">📧 Аналитика по Email <span style="font-size:12px;color:#8b949e;font-weight:normal;">— реальные люди, учитывая подарки</span></h3>

        <!-- Метрики -->
        <div class="grid" style="margin-bottom:20px;">
            <div class="card" style="border-left:4px solid #9b59b6;">
                <h3>ARPU по Email</h3>
                <div class="value"><?= number_format($emailArpu, 2, '.', ' ') ?> ₽</div>
                <div class="sub">Средний доход с реального человека</div>
            </div>
            <div class="card" style="border-left:4px solid #3498db;">
                <h3>Медиана трат по Email</h3>
                <div class="value"><?= number_format($emailMedian, 2, '.', ' ') ?> ₽</div>
                <div class="sub">Типичный человек тратит столько</div>
            </div>
            <div class="card" style="border-left:4px solid #e67e22;">
                <h3>Средний чек по Email</h3>
                <div class="value"><?= number_format($emailAvgCheck, 2, '.', ' ') ?> ₽</div>
                <div class="sub">Выручка / кол-во платежей</div>
            </div>
            <div class="card" style="border-left:4px solid #e74c3c;">
                <h3>Киты по Email 🐋</h3>
                <div class="value"><?= $emailWhalesCount ?> чел.</div>
                <div class="sub"><?= number_format($emailWhalesRevenue, 2, '.', ' ') ?> ₽ · порог <?= number_format($emailWhaleThreshold, 0) ?> ₽+</div>
            </div>
            <div class="card" style="border-left:4px solid #1abc9c;">
                <h3>Повторные покупки по Email</h3>
                <div class="value"><?= $emailRepeatRate ?>%</div>
                <div class="sub"><?= $emailRepeatBuyers ?> из <?= $emailTotal ?> людей</div>
            </div>
        </div>

        <!-- Топ + Распределение -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="card">
                <h3>Топ-5 донатеров по Email</h3>
                <?php foreach (array_slice($emailSpends, 0, 5, true) as $email => $s):
                    $nicks = array_unique($emailNicks[$email] ?? []);
                ?>
                    <div style="margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px;align-items:center;">
                            <span style="color:#ced4da;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;" title="<?= htmlspecialchars($email) ?>">
                                ✉ <b><?= htmlspecialchars($email) ?></b>
                            </span>
                            <span style="color:#2ecc71;white-space:nowrap;margin-left:8px;"><?= number_format($s, 2, '.', ' ') ?> ₽ · <?= $emailCounts[$email] ?> пл.</span>
                        </div>
                        <div style="background:#21262d;border-radius:4px;height:4px;margin-bottom:4px;">
                            <div style="width:<?= round($s / $maxEmailSpend * 100) ?>%;background:#2ecc71;height:4px;border-radius:4px;"></div>
                        </div>
                        <div style="font-size:11px;color:#8b949e;">
                            <?php foreach ($nicks as $nick): ?>
                                <?= mcAvatar($nick, 14) ?><?= htmlspecialchars($nick) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div style="font-size:11px;color:#8b949e;margin-top:8px;border-top:1px solid #30363d;padding-top:8px;">
                    Всего уникальных email: <b style="color:#fff;"><?= $emailTotal ?></b>
                </div>
            </div>

            <div class="card">
                <h3>Распределение людей по тратам по Email</h3>
                <?php
                $emailBuckets = ['< 100₽' => 0, '100–300₽' => 0, '300–500₽' => 0, '500–1000₽' => 0, '1000₽+' => 0];
                foreach ($emailSpends as $s) {
                    if ($s < 100)       $emailBuckets['< 100₽']++;
                    elseif ($s < 300)   $emailBuckets['100–300₽']++;
                    elseif ($s < 500)   $emailBuckets['300–500₽']++;
                    elseif ($s < 1000)  $emailBuckets['500–1000₽']++;
                    else                $emailBuckets['1000₽+']++;
                }
                $maxEmailBucket = max($emailBuckets) ?: 1;
                $ebColors = ['#3498db','#2ecc71','#f1c40f','#e67e22','#e74c3c'];
                $ebi = 0;
                foreach ($emailBuckets as $label => $cnt):
                ?>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px;">
                            <span><?= $label ?></span>
                            <span style="color:<?= $ebColors[$ebi] ?>;"><?= $cnt ?> чел. · <?= $emailTotal > 0 ? round($cnt / $emailTotal * 100, 1) : 0 ?>%</span>
                        </div>
                        <div style="background:#21262d;border-radius:4px;height:6px;">
                            <div style="width:<?= round($cnt / $maxEmailBucket * 100) ?>%;background:<?= $ebColors[$ebi] ?>;height:6px;border-radius:4px;"></div>
                        </div>
                    </div>
                <?php $ebi++; endforeach; ?>
                <div style="font-size:11px;color:#8b949e;margin-top:8px;">
                    Медиана: <b style="color:#fff;"><?= number_format($emailMedian, 2, '.', ' ') ?> ₽</b> ·
                    Китов (≥<?= number_format($emailWhaleThreshold, 0) ?>₽): <b style="color:#e74c3c;"><?= $emailWhalesCount ?></b>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Графики -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
        <div class="card">
            <h3>Тепловая карта активности (день × час)</h3>
            <div id="heatmap" style="margin-top:8px;overflow-x:auto;"></div>
        </div>
        <div class="card">
            <h3>Динамика выручки (последние 30 дней)</h3>
            <canvas id="revenueChart" style="max-height:220px;"></canvas>
        </div>
    </div>

    <!-- Здоровье аудитории + Retention -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
        <div class="card">
            <h3>Здоровье аудитории (Медиана / ARPU)</h3>
            <?php
            $ratio    = $medianArpuRatio;
            $pct      = min(100, round($ratio * 100));
            $barColor = $ratio < 0.40 ? '#e74c3c' : ($ratio < 0.65 ? '#f1c40f' : '#2ecc71');
            $label    = $ratio < 0.40 ? 'Критично — доход держат киты' : ($ratio < 0.65 ? 'Умеренно — есть перекос к китам' : 'Хорошо — равномерное распределение');
            ?>
            <div style="margin:14px 0 8px;">
                <div style="position:relative;height:20px;border-radius:10px;overflow:hidden;background:linear-gradient(to right,#e74c3c 0%,#e74c3c 40%,#f1c40f 40%,#f1c40f 65%,#2ecc71 65%,#2ecc71 100%);">
                    <div style="position:absolute;top:0;left:<?= $pct ?>%;transform:translateX(-50%);width:4px;height:100%;background:#fff;border-radius:2px;box-shadow:0 0 6px rgba(0,0,0,0.8);"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:10px;color:#8b949e;margin-top:4px;">
                    <span>0%</span><span>Опасно</span><span>↑40%</span><span>Норма</span><span>↑65%</span><span>Отлично</span><span>100%</span>
                </div>
            </div>
            <div style="font-size:22px;font-weight:bold;color:<?= $barColor ?>;"><?= $pct ?>%</div>
            <div class="sub"><?= $label ?><br>Медиана <?= number_format($medianSpend, 2, '.', ' ') ?> ₽ · ARPU <?= number_format($arpu, 2, '.', ' ') ?> ₽</div>
        </div>

        <div class="card">
            <h3>Retention (когорта: <?= $cohortSize ?> игр. · ≥76ч назад)</h3>
            <?php
            $ret1color = $retention1 < 10 ? '#e74c3c' : ($retention1 < 25 ? '#f1c40f' : '#2ecc71');
            $ret3color = $retention3 < 5  ? '#e74c3c' : ($retention3 < 15 ? '#f1c40f' : '#2ecc71');
            ?>
            <div style="margin-top:16px;">
                <div style="margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <span style="font-size:13px;">Day-1 Retention</span>
                        <span style="font-size:18px;font-weight:bold;color:<?= $ret1color ?>"><?= $retention1 ?>%</span>
                    </div>
                    <div style="background:#21262d;border-radius:6px;height:10px;">
                        <div style="width:<?= min(100,$retention1) ?>%;background:<?= $ret1color ?>;height:10px;border-radius:6px;"></div>
                    </div>
                    <div style="font-size:11px;color:#8b949e;margin-top:3px;"><?= $ret1Count ?> из <?= $cohortSize ?> · окно +20..+28ч</div>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <span style="font-size:13px;">Day-3 Retention</span>
                        <span style="font-size:18px;font-weight:bold;color:<?= $ret3color ?>"><?= $retention3 ?>%</span>
                    </div>
                    <div style="background:#21262d;border-radius:6px;height:10px;">
                        <div style="width:<?= min(100,$retention3) ?>%;background:<?= $ret3color ?>;height:10px;border-radius:6px;"></div>
                    </div>
                    <div style="font-size:11px;color:#8b949e;margin-top:3px;"><?= $ret3Count ?> из <?= $cohortSize ?> · окно +68..+76ч</div>
                </div>
                <div style="font-size:11px;color:#8b949e;margin-top:12px;border-top:1px solid #30363d;padding-top:8px;">
                    Day-1 &gt;25% отлично, 10–25% норма, &lt;10% плохо<br>
                    Day-3 &gt;15% отлично, 5–15% норма, &lt;5% плохо
                </div>
            </div>
        </div>
    </div>

    <!-- Market Basket Analysis -->
    <?php if (!empty($topBasket)): ?>
    <div class="card" style="margin-bottom:25px;">
        <h3>🛒 Часто покупают вместе (Market Basket)</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;margin-top:14px;">
        <?php
        $maxBasketCount = max(array_values($topBasket)) ?: 1;
        foreach ($topBasket as $pair => $count):
            [$a, $b] = explode('|||', $pair);
            $imgA = getProductImage($productImages, $a);
            $imgB = getProductImage($productImages, $b);
            $pct  = round($count / $maxBasketCount * 100);
        ?>
            <div style="background:#21262d;border-radius:8px;padding:12px;border:1px solid #30363d;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:13px;">
                    <?php if ($imgA): ?><img src="<?= htmlspecialchars($imgA) ?>" class="prod-img"><?php endif; ?>
                    <span style="color:#ced4da;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;" title="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></span>
                    <span style="color:#8b949e;flex-shrink:0;">+</span>
                    <?php if ($imgB): ?><img src="<?= htmlspecialchars($imgB) ?>" class="prod-img"><?php endif; ?>
                    <span style="color:#ced4da;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;" title="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="flex:1;background:#161b22;border-radius:4px;height:5px;">
                        <div style="width:<?= $pct ?>%;background:#3498db;height:5px;border-radius:4px;"></div>
                    </div>
                    <span style="font-size:12px;color:#3498db;font-weight:bold;white-space:nowrap;"><?= $count ?>×</span>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="font-size:11px;color:#8b949e;margin-top:12px;">
            Показаны только пары из одного чека. Используй для создания бандлов/скидок.
        </div>
    </div>
    <?php endif; ?>

    <!-- Анализ ценообразования -->
    <div class="card" style="margin-bottom:25px;">
        <h3>Анализ ценообразования (все данные)</h3>
        <table>
            <thead>
                <tr>
                    <th>Товар</th>
                    <th title="Количество чеков">Чеков</th>
                    <th title="Уникальных игроков">Игроков</th>
                    <th title="Уникальных имейлов">По почтам</th>
                    <th title="Средний доход за один факт покупки">Сред. чек</th>
                    <th>Доля</th>
                    <th>Итого</th>
                    <th>Актуальность</th>
                    <th>Рекомендация</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (array_slice($priceEffectiveness, 0, 15) as $item):
                $img = getProductImage($productImages, $item['name']);
                $relColor = match($item['rel_status'] ?? 'unknown') {
                    'hot'    => '#2ecc71',
                    'normal' => '#3498db',
                    'dying'  => '#f1c40f',
                    'ghost'  => '#e74c3c',
                    default  => '#8b949e',
                };
            ?>
                <tr>
                    <td>
                        <?php if ($img): ?><img src="<?= htmlspecialchars($img) ?>" class="prod-img"><?php endif; ?>
                        <b><?= $item['name'] ?></b>
                    </td>
                    <td><?= $item['purchases'] ?></td>
                    <td><?= $item['customers'] ?>
                        <?php if ($item['per_cust'] >= 3): ?>
                            <span style="color:#f1c40f;font-size:10px;" title="В среднем <?= $item['per_cust'] ?> покупки на игрока">⚡</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $item['customers_email'] ?>
                        <?php if ($item['customers'] > $item['customers_email']): ?>
                            <span style="color:#8b949e;font-size:10px;" title="На <?= $item['customers'] - $item['customers_email'] ?> ников больше, чем почт">↗</span>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($item['avg_check'], 2, '.', ' ') ?> ₽</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;background:#21262d;border-radius:4px;height:6px;">
                                <div style="width:<?= min(100, round($item['score']*100)) ?>%;background:#3498db;height:6px;border-radius:4px;"></div>
                            </div>
                            <?= round($item['score'] * 100, 1) ?>%
                        </div>
                    </td>
                    <td><?= number_format($item['revenue'], 2, '.', ' ') ?> ₽</td>
                    <td style="white-space:nowrap;">
                        <span style="color:<?= $relColor ?>;font-weight:bold;font-size:12px;"><?= $item['rel_label'] ?></span>
                        <?php if ($item['rel_status'] !== 'unknown' && $item['rel_status'] !== 'ghost'): ?>
                            <span style="font-size:10px;color:#8b949e;display:block;">
                                <?= $item['rel_pct'] ?>% за 30д
                                <?php if ($item['trend7'] != 0): ?>
                                    · <?= $item['trend7'] > 0 ? '▲' : '▼' ?><?= abs($item['trend7']) ?>%
                                <?php endif; ?>
                            </span>
                        <?php elseif ($item['rel_status'] === 'ghost'): ?>
                            <span style="font-size:10px;color:#8b949e;display:block;">0% за 60д</span>
                        <?php endif; ?>
                    </td>
                    <td><?= getRecommendation($item, $highThreshold, $lowPriceMax, $hotSalesMin) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="font-size:11px;color:#8b949e;margin-top:12px;">
            <b>Сред. чек</b> — реальный доход (из enrolled) на один чек с этим товаром. Если в чеке несколько товаров — сумма делится пропорционально ценам.
        </div>
    </div>

    <!-- Мёртвые игроки -->
    <?php if ($deadCount > 0): ?>
    <div class="card" style="margin-bottom:25px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;">💀 Мёртвые игроки (не покупали >30 дней)</h3>
            <span style="font-size:13px;color:#8b949e;"><?= $deadCount ?> игр. · <?= number_format($deadRevenue, 2, '.', ' ') ?> ₽ суммарно потрачено</span>
        </div>
        <table>
            <thead>
                <tr><th>Игрок</th><th>Последняя покупка</th><th>Дней назад</th><th>Потрачено всего</th><th>Приоритет реактивации</th></tr>
            </thead>
            <tbody>
            <?php foreach (array_slice($deadPlayers, 0, 10) as $dp): ?>
                <tr>
                    <td><?= mcAvatar($dp['user'], 20) ?><b><?= htmlspecialchars($dp['user']) ?></b></td>
                    <td><?= date('d.m.Y', $dp['last_ts']) ?></td>
                    <td style="color:<?= $dp['days_ago'] > 60 ? '#e74c3c' : '#f1c40f' ?>"><?= $dp['days_ago'] ?> дн.</td>
                    <td><?= number_format($dp['total_spent'], 2, '.', ' ') ?> ₽</td>
                    <td>
                        <?php if ($dp['total_spent'] > $medianSpend * 2): ?>
                            <span style="color:#e74c3c;font-weight:bold;">🔴 Высокий (кит)</span>
                        <?php elseif ($dp['total_spent'] > $medianSpend): ?>
                            <span style="color:#f1c40f;">🟡 Средний</span>
                        <?php else: ?>
                            <span style="color:#8b949e;">⚪ Низкий</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($deadCount > 10): ?>
        <div style="font-size:12px;color:#8b949e;margin-top:10px;">... и ещё <?= $deadCount - 10 ?> игроков</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Инсайты -->
    <?php if (!empty($insights)): ?>
    <div class="card" style="margin-bottom:25px;">
        <h3>Риски и возможности</h3>
        <div style="display:flex;flex-direction:column;gap:12px;margin-top:12px;">
        <?php
        $typeStyles = [
            'danger'      => ['#e74c3c', 'rgba(231,76,60,0.08)'],
            'opportunity' => ['#9b59b6', 'rgba(155,89,182,0.08)'],
            'tip'         => ['#f1c40f', 'rgba(241,196,15,0.08)'],
            'positive'    => ['#2ecc71', 'rgba(46,204,113,0.08)'],
        ];
        foreach ($insights as $ins):
            [$bc, $bg] = $typeStyles[$ins['type']] ?? $typeStyles['tip'];
        ?>
            <div style="border-left:4px solid <?= $bc ?>;background:<?= $bg ?>;padding:14px 16px;border-radius:0 8px 8px 0;">
                <div style="font-weight:bold;font-size:14px;margin-bottom:6px;color:#fff;"><?= $ins['icon'] ?> <?= htmlspecialchars($ins['title']) ?></div>
                <div style="font-size:13px;color:#ced4da;line-height:1.6;"><?= htmlspecialchars($ins['text']) ?></div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Таблица транзакций -->
    <h3>Последние транзакции (стр. <?= $page ?> из <?= $lastPage ?>)</h3>
    <table>
        <thead>
            <tr>
                <th>Игрок</th>
                <th>Сумма</th>
                <th>Оплата</th>
                <th>Товары</th>
                <th>Дата</th>
                <th>Статус</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $shown = 0;
        foreach ($payments as $p):
            if (strtolower($p['payment_type'] ?? '') === 'test') continue;
            if ((int)($p['status'] ?? 0) !== 2) continue;
            $shown++;
            $nick        = htmlspecialchars($p['customer'] ?? '', ENT_QUOTES, 'UTF-8');
            $productParts = [];
            if (!empty($p['products']) && is_array($p['products'])) {
                foreach ($p['products'] as $pr) {
                    $pname  = htmlspecialchars((string)($pr['name'] ?? 'Товар'), ENT_QUOTES, 'UTF-8');
                    $qty    = (int)($pr['number'] ?? 1);
                    $pimg   = getProductImage($productImages, $pname);
                    $imgTag = $pimg ? "<img src='" . htmlspecialchars($pimg, ENT_QUOTES) . "' class='prod-img'>" : '';
                    $productParts[] = $imgTag . $pname . ($qty > 1 ? " ×{$qty}" : '');
                }
            }
        ?>
        <tr>
            <td><?= mcAvatar($nick, 22) ?><b><?= $nick ?></b></td>
            <td><b><?= number_format((float)($p['enrolled'] ?? 0), 2, '.', ' ') ?> ₽</b></td>
            <td><?= paymentBadge($p['payment_type'] ?? '') ?></td>
            <td style="color:#8b949e;"><?= implode(', ', $productParts) ?></td>
            <td><?= date('d.m.Y H:i', strtotime($p['created_at'])) ?></td>
            <td><span class="status-paid">ОПЛАЧЕНО</span></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($shown === 0): ?>
        <tr><td colspan="6" style="text-align:center;color:#8b949e;padding:30px;">Нет оплаченных транзакций на этой странице</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">← Назад</a><?php endif; ?>
        <span class="current">Страница <?= $page ?> из <?= $lastPage ?></span>
        <?php if ($page < $lastPage): ?><a href="?page=<?= $page + 1 ?>">Вперёд →</a><?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($tab === 'catalog'): ?>

<?php if ($selectedProductItem): $sp = $selectedProductItem; ?>
    <!-- Детальная аналитика товара -->
    <div style="margin-bottom:20px;">
        <a href="?tab=catalog" style="color:#8b949e;text-decoration:none;font-size:13px;">← Назад к каталогу</a>
    </div>
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <?php $spImg = getProductImage($productImages, $sp['name']); ?>
        <?php if ($spImg): ?>
            <img src="<?= htmlspecialchars($spImg) ?>" style="width:64px;height:64px;border-radius:12px;object-fit:cover;border:2px solid #30363d;">
        <?php endif; ?>
        <div>
            <h2 style="margin:0 0 4px;"><?= $sp['name'] ?></h2>
            <div style="display:flex;gap:10px;align-items:center;font-size:13px;">
                <?php
                $relColor = match($sp['rel_status'] ?? 'unknown') {
                    'hot'    => '#2ecc71',
                    'normal' => '#3498db',
                    'dying'  => '#f1c40f',
                    'ghost'  => '#e74c3c',
                    default  => '#8b949e',
                };
                ?>
                <span style="color:<?= $relColor ?>;font-weight:bold;"><?= $sp['rel_label'] ?></span>
                <span style="color:#8b949e;">·</span>
                <span><?= getRecommendation($sp, $highThreshold, $lowPriceMax, $hotSalesMin) ?></span>
            </div>
        </div>
    </div>

    <!-- KPI карточки -->
    <div class="grid" style="margin-bottom:24px;">
        <div class="card" style="border-left:4px solid #2ecc71;">
            <h3>Выручка</h3>
            <div class="value"><?= number_format($sp['revenue'], 2, '.', ' ') ?> ₽</div>
            <div class="sub">Доля: <?= round($sp['score'] * 100, 1) ?>% от всей выручки</div>
        </div>
        <div class="card" style="border-left:4px solid #3498db;">
            <h3>Продажи</h3>
            <div class="value"><?= $sp['purchases'] ?></div>
            <div class="sub">Транзакций с этим товаром</div>
        </div>
        <div class="card" style="border-left:4px solid #9b59b6;">
            <h3>Средний чек</h3>
            <div class="value"><?= number_format($sp['avg_check'], 2, '.', ' ') ?> ₽</div>
            <div class="sub">Реальный доход на одну покупку</div>
        </div>
        <div class="card" style="border-left:4px solid #e67e22;">
            <h3>Уникальные игроки</h3>
            <div class="value"><?= $sp['customers'] ?></div>
            <div class="sub">По никам: <?= $sp['customers'] ?> · По email: <?= $sp['customers_email'] ?></div>
        </div>
    </div>

    <!-- График выручки за 30 дней -->
    <div class="card" style="margin-bottom:24px;">
        <h3>Динамика выручки (последние 30 дней)</h3>
        <canvas id="productRevenueChart" style="max-height:200px;"></canvas>
    </div>

    <!-- Покупатели -->
    <div class="card" style="margin-bottom:24px;">
        <h3>Покупатели (<?= count($selectedProductCustSpends) ?>)</h3>
        <?php if (empty($selectedProductCustSpends)): ?>
            <div style="color:#8b949e;padding:20px;text-align:center;">Нет данных о покупателях</div>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>Игрок</th><th>Потрачено всего</th></tr>
            </thead>
            <tbody>
            <?php $maxCustSpend = max($selectedProductCustSpends) ?: 1; ?>
            <?php foreach ($selectedProductCustSpends as $cust => $spent): ?>
                <tr>
                    <td><?= mcAvatar($cust, 20) ?><b><?= $cust ?></b></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;background:#21262d;border-radius:4px;height:5px;max-width:120px;">
                                <div style="width:<?= round($spent / $maxCustSpend * 100) ?>%;background:#2ecc71;height:5px;border-radius:4px;"></div>
                            </div>
                            <?= number_format($spent, 2, '.', ' ') ?> ₽
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

<?php else:
    // ── Статистика по каталогу ─────────────────────────────────────────────
    $totalProd       = count($priceEffectiveness);
    $totalSales      = array_sum(array_column($priceEffectiveness, 'purchases'));
    $totalCatRevenue = array_sum(array_column($priceEffectiveness, 'revenue'));
    $avgRevPerProd   = $totalProd > 0 ? $totalCatRevenue / $totalProd : 0;
    $avgChecks       = array_column($priceEffectiveness, 'avg_check');
    sort($avgChecks);
    $acN = count($avgChecks);
    $medianProdPrice = $acN > 0 ? ($acN % 2 === 1 ? $avgChecks[(int)($acN/2)] : ($avgChecks[$acN/2-1] + $avgChecks[$acN/2]) / 2) : 0;

    $statusCounts = ['hot' => 0, 'normal' => 0, 'dying' => 0, 'ghost' => 0, 'unknown' => 0];
    foreach ($priceEffectiveness as $item) { $statusCounts[$item['rel_status']]++; }

    $byRev = $priceEffectiveness;
    usort($byRev, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
    $top5       = array_slice($byRev, 0, 5);
    $otherTotal = 0;
    foreach (array_slice($byRev, 5) as $item) $otherTotal += $item['revenue'];

    $priceBuckets = [0, 0, 0, 0, 0];
    $bucketLabels = ['0–50 ₽', '50–100 ₽', '100–200 ₽', '200–500 ₽', '500+ ₽'];
    foreach ($priceEffectiveness as $item) {
        $ac = $item['avg_check'];
        if      ($ac <= 50)   $priceBuckets[0]++;
        elseif  ($ac <= 100)  $priceBuckets[1]++;
        elseif  ($ac <= 200)  $priceBuckets[2]++;
        elseif  ($ac <= 500)  $priceBuckets[3]++;
        else                  $priceBuckets[4]++;
    }

    $paretoData = [];
    $running    = 0;
    foreach ($byRev as $item) {
        $running += $item['revenue'];
        $paretoData[] = round($running / max($totalCatRevenue, 1) * 100, 1);
    }

    $loco = array_filter($priceEffectiveness, fn($i) => $i['score'] >= $highThreshold);
?>
    <!-- Сортировка -->
    <div style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
        <span style="font-size:12px;color:#8b949e;margin-right:6px;">Сортировать по:</span>
        <?php $dirArrow = $sortOrder === 'desc' ? '↓' : '↑'; ?>
        <?php foreach ($sortMap as $key => $label): ?>
            <?php $active = $key === $sortBy; ?>
            <a href="?tab=catalog&sort=<?= $key ?>&order=<?= $active && $sortOrder === 'desc' ? 'asc' : 'desc' ?>"
               style="padding:5px 12px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:600;white-space:nowrap;<?= $active ? 'background:#2ecc71;color:#0b0e14;' : 'background:#21262d;color:#8b949e;' ?>">
                <?= $label ?>
                <?php if ($active): ?><?= $dirArrow ?><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ── Общая статистика каталога ──────────────────────────────────── -->
    <div class="grid" style="margin-bottom:20px;">
        <div class="card" style="border-left:4px solid #2ecc71;">
            <h3>Всего товаров</h3>
            <div class="value"><?= $totalProd ?></div>
            <div class="sub">В каталоге магазина</div>
        </div>
        <div class="card" style="border-left:4px solid #3498db;">
            <h3>Всего продаж</h3>
            <div class="value"><?= $totalSales ?></div>
            <div class="sub">Транзакций с товарами</div>
        </div>
        <div class="card" style="border-left:4px solid #9b59b6;">
            <h3>Средняя выручка</h3>
            <div class="value"><?= number_format($avgRevPerProd, 0, '.', ' ') ?> ₽</div>
            <div class="sub">На один товар</div>
        </div>
        <div class="card" style="border-left:4px solid #e67e22;">
            <h3>Медианная цена</h3>
            <div class="value"><?= number_format($medianProdPrice, 0, '.', ' ') ?> ₽</div>
            <div class="sub">Типичная цена товара</div>
        </div>
    </div>

    <!-- Статусы товаров -->
    <div class="card" style="margin-bottom:20px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <span style="font-size:13px;font-weight:600;color:#fff;margin-right:8px;">Состояние товаров:</span>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php
                $statusMeta = [
                    'hot'    => ['🔥', 'Актуальны',   '#2ecc71'],
                    'normal' => ['📊', 'Нормально',    '#3498db'],
                    'dying'  => ['⏳', 'Угасают',      '#f1c40f'],
                    'ghost'  => ['👻', 'Устарели',     '#e74c3c'],
                    'unknown'=> ['⚪', 'Мало данных',  '#8b949e'],
                ];
                foreach ($statusMeta as $key => [$icon, $label, $color]):
                    $cnt = $statusCounts[$key] ?? 0;
                    $pct = round($cnt / max($totalProd, 1) * 100);
                ?>
                <div style="display:flex;align-items:center;gap:6px;background:#21262d;padding:6px 12px;border-radius:8px;border:1px solid <?= $color ?>33;">
                    <span style="font-size:16px;"><?= $icon ?></span>
                    <div>
                        <div style="font-size:18px;font-weight:bold;color:<?= $color ?>;"><?= $cnt ?></div>
                        <div style="font-size:10px;color:#8b949e;"><?= $label ?> · <?= $pct ?>%</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (count($loco) > 0): ?>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #21262d;font-size:12px;color:#8b949e;">
            🚂 Локомотивы: <?php foreach ($loco as $l): ?><span style="color:#2ecc71;font-weight:600;">«<?= $l['name'] ?>» (<?= round($l['score']*100,1) ?>%)</span> · <?php endforeach; ?>дают основную долю выручки
        </div>
        <?php endif; ?>
    </div>

    <!-- Диаграммы -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
        <div class="card">
            <h3>📊 Распределение выручки по товарам</h3>
            <canvas id="revenuePieChart" style="max-height:220px;"></canvas>
        </div>
        <div class="card">
            <h3>📊 Распределение цен</h3>
            <canvas id="priceDistChart" style="max-height:220px;"></canvas>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h3>📈 Концентрация выручки (Парето)</h3>
        <canvas id="paretoChart" style="max-height:160px;"></canvas>
        <div style="font-size:11px;color:#8b949e;margin-top:8px;">
            Кривая показывает, какой процент товаров приносит сколько процентов выручки.
            Чем круче — тем выше зависимость от малого числа товаров.
        </div>
    </div>

    <!-- Сетка каталога товаров -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px;">
    <?php foreach ($priceEffectivenessSorted as $item):
        $img = getProductImage($productImages, $item['name']);
    ?>
        <a href="?tab=catalog&product=<?= urlencode($item['name']) ?>" style="text-decoration:none;color:inherit;">
            <div class="card" style="height:100%;display:flex;flex-direction:column;cursor:pointer;transition:border-color .2s;border-color:<?= $item['rel_status'] === 'hot' ? '#2ecc71' : ($item['rel_status'] === 'dying' ? '#f1c40f' : ($item['rel_status'] === 'ghost' ? '#e74c3c' : '#30363d')) ?>;" onmouseover="this.style.borderColor='#2ecc71'" onmouseout="this.style.borderColor=''">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                    <?php if ($img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" style="width:42px;height:42px;border-radius:8px;object-fit:cover;">
                    <?php else: ?>
                        <div style="width:42px;height:42px;border-radius:8px;background:#21262d;display:flex;align-items:center;justify-content:center;font-size:18px;">📦</div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $item['name'] ?></div>
                        <div style="font-size:10px;color:#8b949e;margin-top:2px;">
                            <span style="color:<?= $item['rel_status'] === 'hot' ? '#2ecc71' : ($item['rel_status'] === 'dying' ? '#f1c40f' : '#8b949e') ?>;"><?= $item['rel_label'] ?></span>
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:auto;padding-top:8px;border-top:1px solid #21262d;">
                    <div>
                        <div style="font-size:10px;color:#8b949e;">Выручка</div>
                        <div style="font-size:14px;font-weight:bold;color:#2ecc71;"><?= number_format($item['revenue'], 0, '.', ' ') ?> ₽</div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:#8b949e;">Продажи</div>
                        <div style="font-size:14px;font-weight:bold;color:#fff;"><?= $item['purchases'] ?></div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:#8b949e;">Сред. чек</div>
                        <div style="font-size:13px;font-weight:bold;color:#9b59b6;"><?= number_format($item['avg_check'], 0, '.', ' ') ?> ₽</div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:#8b949e;">Игроков</div>
                        <div style="font-size:13px;font-weight:bold;color:#fff;"><?= $item['customers'] ?></div>
                    </div>
                </div>
                <?php if ($item['trend7'] != 0): ?>
                <div style="font-size:11px;margin-top:8px;color:<?= $item['trend7'] > 0 ? '#2ecc71' : '#e74c3c' ?>;">
                    <?= $item['trend7'] > 0 ? '▲' : '▼' ?> <?= abs($item['trend7']) ?>% за 7д
                </div>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($tab === 'dashboard'): ?>
// ── Тепловая карта ─────────────────────────────────────────────────────────
const heatRaw  = <?= json_encode($fullStats['heatmap'] ?? new stdClass()) ?>;
const dowNames = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
let heatMax = 0;
for (let d = 0; d < 7; d++)
    for (let h = 0; h < 24; h++) {
        const v = heatRaw[d + '_' + h] || 0;
        if (v > heatMax) heatMax = v;
    }
let html = '<table style="border-collapse:collapse;width:100%;font-size:11px;">';
html += '<tr><td style="padding:2px 4px;color:#8b949e;"></td>';
for (let h = 0; h < 24; h++)
    html += `<td style="text-align:center;color:#8b949e;padding:1px 2px;">${h}</td>`;
html += '</tr>';
for (let d = 0; d < 7; d++) {
    html += `<tr><td style="color:#8b949e;padding:2px 6px 2px 0;white-space:nowrap;">${dowNames[d]}</td>`;
    for (let h = 0; h < 24; h++) {
        const v   = heatRaw[d + '_' + h] || 0;
        const pct = heatMax > 0 ? v / heatMax : 0;
        const alpha = pct < 0.01 ? 0.05 : 0.15 + pct * 0.85;
        const r = Math.round(46  + (52  - 46)  * (1 - pct));
        const g = Math.round(204 * pct);
        const b = Math.round(113 * (1 - pct) + 219 * pct * 0.3);
        const bg = pct < 0.01 ? 'rgba(255,255,255,0.04)' : `rgba(${r},${g},${b},${alpha.toFixed(2)})`;
        const title = v > 0 ? `${dowNames[d]} ${h}:00 — ${v} покупок` : '';
        html += `<td title="${title}" style="background:${bg};border-radius:3px;width:${100/25}%;height:18px;cursor:default;"></td>`;
    }
    html += '</tr>';
}
html += '</table>';
document.getElementById('heatmap').innerHTML = html;

// ── График выручки ──────────────────────────────────────────────────────────
const dailyRaw = <?php
    $last30 = [];
    for ($i = 29; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $last30[$day] = $fullStats['daily_revenue'][$day] ?? 0;
    }
    echo json_encode($last30);
?>;

const revLabels = Object.keys(dailyRaw).map(d => { const [y,m,day] = d.split('-'); return day+'.'+m; });
const revData   = Object.values(dailyRaw);
const today = '<?= date('d.m') ?>';
const pointColors = revLabels.map(l => l === today ? '#f1c40f' : '#2ecc71');
const pointSizes  = revLabels.map(l => l === today ? 6 : (revData[revLabels.indexOf(l)] > 0 ? 3 : 0));

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: revLabels,
        datasets: [{
            label: 'Выручка ₽',
            data: revData,
            borderColor: '#2ecc71',
            backgroundColor: 'rgba(46,204,113,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: pointSizes,
            pointBackgroundColor: pointColors,
            pointBorderColor: pointColors,
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y.toLocaleString('ru-RU') + ' ₽'
                }
            }
        },
        scales: {
            x: { ticks: { color:'#8b949e', maxTicksLimit:10 }, grid: { color:'#21262d' } },
            y: { ticks: { color:'#8b949e' }, grid: { color:'#21262d' } }
        }
    }
});
<?php endif; ?>

<?php if ($tab === 'catalog' && $selectedProductItem): ?>
// ── График выручки товара ──────────────────────────────────────────────────
const productDailyRaw = <?php
    $last30 = [];
    for ($i = 29; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $last30[$day] = $selectedProductDaily[$day] ?? 0;
    }
    echo json_encode($last30);
?>;
const prodLabels = Object.keys(productDailyRaw).map(d => { const [y,m,day] = d.split('-'); return day+'.'+m; });
const prodData   = Object.values(productDailyRaw);
new Chart(document.getElementById('productRevenueChart'), {
    type: 'line',
    data: {
        labels: prodLabels,
        datasets: [{
            label: 'Выручка ₽',
            data: prodData,
            borderColor: '#9b59b6',
            backgroundColor: 'rgba(155,89,182,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: prodData.map(v => v > 0 ? 3 : 0),
            pointBackgroundColor: '#9b59b6',
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y.toLocaleString('ru-RU') + ' ₽'
                }
            }
        },
        scales: {
            x: { ticks: { color:'#8b949e', maxTicksLimit:10 }, grid: { color:'#21262d' } },
            y: { ticks: { color:'#8b949e' }, grid: { color:'#21262d' } }
        }
    }
});
<?php endif; ?>

<?php if ($tab === 'catalog' && !$selectedProductItem): ?>
// ── Каталог: распределение выручки ─────────────────────────────────────────
const pieLabels = <?= json_encode(array_merge(array_column($top5, 'name'), ['Остальные'])) ?>;
const pieData   = <?= json_encode(array_merge(array_column($top5, 'revenue'), [$otherTotal])) ?>;
const pieColors = ['#2ecc71','#3498db','#9b59b6','#e67e22','#e74c3c','#30363d'];
new Chart(document.getElementById('revenuePieChart'), {
    type: 'doughnut',
    data: { labels: pieLabels, datasets: [{ data: pieData, backgroundColor: pieColors, borderWidth: 0 }] },
    options: {
        plugins: {
            legend: { position:'bottom', labels: { color:'#8b949e', font:{size:10}, boxWidth:12, padding:8 } },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.toLocaleString('ru-RU') + ' ₽' } }
        },
        cutout: '60%'
    }
});

// ── Каталог: распределение цен ──────────────────────────────────────────────
const priceLabels = <?= json_encode($bucketLabels) ?>;
const priceData   = <?= json_encode($priceBuckets) ?>;
const priceColors = ['#2ecc71','#3498db','#9b59b6','#e67e22','#e74c3c'];
new Chart(document.getElementById('priceDistChart'), {
    type: 'bar',
    data: {
        labels: priceLabels,
        datasets: [{
            label: 'Товаров',
            data: priceData,
            backgroundColor: priceColors.map(c => c + '66'),
            borderColor: priceColors,
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' товаров' } } },
        scales: {
            x: { ticks: { color:'#8b949e', font:{size:10} }, grid: { display:false } },
            y: { ticks: { color:'#8b949e', precision:0 }, grid: { color:'#21262d' }, beginAtZero: true }
        }
    }
});

// ── Каталог: Парето ─────────────────────────────────────────────────────────
const paretoValues = <?= json_encode($paretoData) ?>;
const paretoLabels = paretoValues.map((_,i) => i + 1);
new Chart(document.getElementById('paretoChart'), {
    type: 'line',
    data: {
        labels: paretoLabels,
        datasets: [{
            label: 'Накопленная доля выручки',
            data: paretoValues,
            borderColor: '#f1c40f',
            backgroundColor: 'rgba(241,196,15,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 2,
            pointBackgroundColor: '#f1c40f',
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + '% выручки' } }
        },
        scales: {
            x: { ticks: { color:'#8b949e', font:{size:9}, maxTicksLimit:10 }, grid: { color:'#21262d' }, title: { display:true, text:'Товары (по убыванию выручки)', color:'#8b949e', font:{size:10} } },
            y: { ticks: { color:'#8b949e', callback: v => v + '%' }, grid: { color:'#21262d' }, min:0, max:100 }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>