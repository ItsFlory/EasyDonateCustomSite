<?php
header('Content-Type: application/json');
$allowedOrigin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$allowedOrigin .= '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/helpers.php';

$action = $_GET['action'] ?? null;

// Валидация Minecraft ника (только латиница, цифры, подчёркивание, 3-16 символов)
function validateNickname(string $nick): bool {
    return (bool)preg_match('/^[a-zA-Z0-9_]{3,16}$/', $nick);
}

// Санитизация строки для записи в JSON (безопасное хранение)
function sanitizeInput(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ========== СОЗДАНИЕ ПЛАТЕЖА ДЛЯ КЕЙСА ==========
if ($action === 'create_case_payment') {
    $customer = trim($_GET['customer'] ?? '');
    $email = trim($_GET['email'] ?? '');
    $caseId = (int)($_GET['case_id'] ?? 0);
    $quantity = max(1, (int)($_GET['quantity'] ?? 1));
    $payType = $_GET['payment_type'] ?? null;
    $payMethod = $_GET['payment_method'] ?? null;

    if (!$customer || !$email || !$caseId) {
        echo json_encode(['success' => false, 'response' => 'Заполните ник и email']);
        exit;
    }
    if (!validateNickname($customer)) {
        echo json_encode(['success' => false, 'response' => 'Ник должен содержать только латиницу, цифры и _, от 3 до 16 символов']);
        exit;
    }
    $customer = sanitizeInput($customer);
    $email    = sanitizeInput($email);

    $params = [
        'customer'    => $customer,
        'server_id'   => (int)($_GET['server_id'] ?? SERVER_ID),
        'products'    => json_encode([$caseId => $quantity]),
        'email'       => $email,
    ];

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $params['success_url'] = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/shop/roulette.php?payment_id=__PAYMENT_ID__';

    if ($payType) {
        $params['payment_type'] = $payType;
        if ($payMethod) {
            $params['payment_method'] = $payMethod;
        }
    }

    // ===== ТЕСТОВЫЙ РЕЖИМ =====
    if (isTestMode()) {
        $fakeId = -1 * time();
        $saved = withPaymentsLock(function(&$payments) use ($fakeId, $customer, $caseId, $quantity) {
            $payments[$fakeId] = [
                'customer'  => $customer,
                'case_id'   => $caseId,
                'case_name' => 'Кейс (тест)',
                'status'    => 'paid',
                'used'      => false,
                'cost'      => 0,
                'created_at'=> date('Y-m-d H:i:s'),
                'spins_total' => $quantity,
                'spins_left'  => $quantity,
                'won_items'   => [],
                'pending_item'      => null,
                'pending_item_id'   => null,
                'pending_amount'    => null,
            ];
            return true;
        });
        if (!$saved) {
            echo json_encode(['success' => false, 'response' => 'Ошибка записи платежа']);
            exit;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/shop/roulette.php?payment_id=' . $fakeId;
        echo json_encode(['success' => true, 'response' => ['url' => $url]]);
        exit;
    }

    $finalUrl = "https://easydonate.ru/api/v3/shop/payment/create?" . http_build_query($params);
    $result = easyDonateRequest($finalUrl);

    if ($result === null || empty($result['success'])) {
        echo json_encode(['success' => false, 'response' => $result['response'] ?? 'Ошибка API EasyDonate']);
        exit;
    }

    // Подменяем success_url с реальным payment_id
    $paymentId = $result['response']['payment']['id'] ?? null;
    if ($paymentId) {
        $result['response']['url'] = str_replace('__PAYMENT_ID__', $paymentId, $result['response']['url']);

        // Сохраняем в payments.json (статус pending — webhook обновит на paid)
        withPaymentsLock(function(&$payments) use ($paymentId, $customer, $caseId, $result, $quantity) {
            $payments[$paymentId] = [
                'customer'  => $customer,
                'case_id'   => $caseId,
                'case_name' => $result['response']['payment']['products'][0]['name'] ?? 'Кейс',
                'status'    => 'pending',
                'used'      => false,
                'cost'      => $result['response']['payment']['cost'] ?? 0,
                'created_at'=> date('Y-m-d H:i:s'),
                'spins_total' => $quantity,
                'spins_left'  => $quantity,
                'won_items'   => [],
                'pending_item'      => null,
                'pending_item_id'   => null,
                'pending_amount'    => null,
            ];
            return true;
        });
    }

    echo json_encode($result);
    exit;
}

// ========== ПРОВЕРКА СТАТУСА ПЛАТЕЖА ==========
if ($action === 'check_payment') {
    $paymentId = (int)($_GET['payment_id'] ?? 0);
    if (!$paymentId) {
        echo json_encode(['success' => false, 'response' => 'Не указан payment_id']);
        exit;
    }

    $payments = loadPayments();

    if (isset($payments[$paymentId])) {
        $p = $payments[$paymentId];
        echo json_encode([
            'success' => true,
            'status'   => $p['status'],
            'used'     => $p['used'] || $p['status'] === 'used',
            'customer' => $p['customer'],
            'case_id'  => $p['case_id'],
            'case_name'=> $p['case_name'],
            'spins_total' => $p['spins_total'] ?? 1,
            'spins_left'  => $p['spins_left'] ?? ($p['used'] ? 0 : 1),
            'pending_item' => $p['pending_item'] ?? null,
        ]);
        exit;
    }

    // Fallback: проверяем напрямую через API EasyDonate
    $url = "https://easydonate.ru/api/v3/shop/payment/{$paymentId}";
    $result = easyDonateRequest($url);

    if ($result === null || empty($result['success'])) {
        echo json_encode(['success' => false, 'response' => 'Платёж не найден']);
        exit;
    }

    $payment = $result['response'];
    $isPaid = ($payment['status'] ?? 0) == 2;

    if ($isPaid) {
        // Автоматически сохраняем в payments.json (атомарно)
        $saved = withPaymentsLock(function(&$payments) use ($paymentId, $payment) {
            if (!isset($payments[$paymentId])) {
                $payments[$paymentId] = [
                    'customer' => $payment['customer'] ?? '',
                    'case_id' => 0,
                    'case_name' => 'Кейс',
                    'status' => 'paid',
                    'used' => false,
                    'cost' => $payment['cost'] ?? 0,
                    'created_at' => $payment['created_at'] ?? date('Y-m-d H:i:s'),
                    'spins_total' => 1,
                    'spins_left'  => 1,
                    'won_items'   => [],
                    'pending_item'      => null,
                    'pending_item_id'   => null,
                    'pending_amount'    => null,
                ];
            } elseif ($payments[$paymentId]['status'] === 'pending') {
                $payments[$paymentId]['status'] = 'paid';
            }
            return true;
        });
        if (!$saved) {
            error_log('check_payment: не удалось сохранить payment ' . $paymentId);
        }
    }

    echo json_encode([
        'success' => true,
        'status' => $isPaid ? 'paid' : 'pending',
        'used' => false,
        'customer' => $payment['customer'] ?? '',
        'spins_total' => 1,
        'spins_left'  => $isPaid ? 1 : 0,
    ]);
    exit;
}

// ========== ПОЛУЧЕНИЕ СПИСКА СЕРВЕРОВ ==========
if ($action === 'get_servers') {
    $url = "https://easydonate.ru/api/v3/servers";
    $result = easyDonateRequest($url);
    if ($result && !empty($result['success'])) {
        echo json_encode(['success' => true, 'response' => $result['response']]);
    } else {
        echo json_encode(['success' => true, 'response' => [
            ['id' => SERVER_ID, 'name' => 'MinecraftTimes']
        ]]);
    }
    exit;
}

// ============ ВСПОМОГАТЕЛЬНАЯ ФУНКЦИЯ: загрузить предметы кейса ============
function loadCaseItems(int $caseId): ?array
{
    if (!$caseId) return null;
    $productsUrl = "https://easydonate.ru/api/v3/shop/products?server_id=" . SERVER_ID;
    $productsData = easyDonateRequest($productsUrl);
    if (!$productsData || empty($productsData['response'])) return null;
    foreach ($productsData['response'] as $product) {
        if ((int)$product['id'] === $caseId) {
            return [
                'items'    => parseCaseItems($product),
                'product'  => $product,
            ];
        }
    }
    return null;
}

// ========== ВЫБОР ПРЕДМЕТА (resolve) ==========
if ($action === 'resolve_spin') {
    $paymentId = (int)($_GET['payment_id'] ?? 0);
    if (!$paymentId) {
        echo json_encode(['success' => false, 'response' => 'Не указан payment_id']);
        exit;
    }

    // Rate limit: 10 запросов за 30 секунд на payment_id
    if (!checkRateLimit('resolve_' . $paymentId, 10, 30)) {
        echo json_encode(['success' => false, 'response' => 'Слишком много запросов, повторите позже']);
        exit;
    }

    $response = null;
    $locked = withPaymentsLock(function(&$payments) use ($paymentId, &$response) {
        if (!isset($payments[$paymentId])) {
            $response = ['success' => false, 'response' => 'Платёж не найден'];
            return true;
        }

        $p = &$payments[$paymentId];

        // Миграция старых платежей (до multi-spin)
        $p['spins_left']   ??= 1;
        $p['spins_total']  ??= 1;
        $p['won_items']    ??= [];
        $p['pending_item']    ??= null;
        $p['pending_item_id'] ??= null;
        $p['pending_amount']  ??= null;

        // Если платёж завис в старом статусе 'resolved' (прерванный спин) — возвращаем в paid
        if ($p['status'] === 'resolved') {
            $p['status'] = 'paid';
        }

        if ($p['spins_left'] <= 0) {
            $response = ['success' => false, 'response' => 'Все открытия уже использованы'];
            return true;
        }
        if ($p['status'] !== 'paid') {
            $response = ['success' => false, 'response' => 'Платёж не оплачен'];
            return true;
        }
        if ($p['pending_item'] !== null) {
            $response = ['success' => false, 'response' => 'Уже выполняется открытие'];
            return true;
        }

        $caseData = loadCaseItems((int)$p['case_id']);
        if (!$caseData || empty($caseData['items'])) {
            $response = ['success' => false, 'response' => 'Ошибка загрузки кейса'];
            return true;
        }

        $wonItem = weightedRandom($caseData['items']);
        $p['pending_item']    = $wonItem['name'];
        $p['pending_item_id'] = $wonItem['item_id'];
        $p['pending_amount']  = $wonItem['amount'] ?? 1;

        $response = [
            'success' => true,
            'item' => [
                'name'    => $wonItem['name'],
                'chance'  => $wonItem['chance'],
                'item_id' => $wonItem['item_id'],
                'amount'  => $wonItem['amount'] ?? 1,
            ],
            'spins_left' => $p['spins_left'],
        ];
        return true;
    });

    if (!$locked) {
        echo json_encode(['success' => false, 'response' => 'Ошибка блокировки файла платежей']);
        exit;
    }
    echo json_encode($response);
    exit;
}

// ========== ФИНАЛИЗАЦИЯ: RCON + decrement spins_left ==========
if ($action === 'spin_case') {
    $paymentId = (int)($_GET['payment_id'] ?? 0);
    if (!$paymentId) {
        echo json_encode(['success' => false, 'response' => 'Не указан payment_id']);
        exit;
    }

    // Rate limit: 10 запросов за 30 секунд на payment_id
    if (!checkRateLimit('spin_' . $paymentId, 10, 30)) {
        echo json_encode(['success' => false, 'response' => 'Слишком много запросов, повторите позже']);
        exit;
    }

    $response = null;
    $locked = withPaymentsLock(function(&$payments) use ($paymentId, &$response) {
        if (!isset($payments[$paymentId])) {
            $response = ['success' => false, 'response' => 'Платёж не найден'];
            return true;
        }

        $p = &$payments[$paymentId];

        // Миграция старых платежей (до multi-spin)
        $p['spins_left']   ??= 1;
        $p['spins_total']  ??= 1;
        $p['won_items']    ??= [];
        $p['pending_item']    ??= null;
        $p['pending_item_id'] ??= null;
        $p['pending_amount']  ??= null;

        if ($p['spins_left'] <= 0) {
            $response = ['success' => false, 'response' => 'Все открытия уже использованы'];
            return true;
        }
        if ($p['pending_item'] === null) {
            $response = ['success' => false, 'response' => 'Сначала выберите предмет (resolve_spin)'];
            return true;
        }

        $customer   = $p['customer'];
        $wonItemId  = $p['pending_item_id'];
        $wonName    = $p['pending_item'];
        $itemAmount = $p['pending_amount'] ?? 1;

        // Ищем шаблон give-команды
        $caseData = loadCaseItems((int)$p['case_id']);
        $giveTemplate = null;
        if ($caseData) {
            foreach ($caseData['product']['commands'] as $cmd) {
                if (preg_match('/^(minecraft:)?give\s+\S+\s+/i', $cmd, $m)) {
                    $giveTemplate = preg_replace(
                        '/\{player\}|\{user\}|\{username\}|\{amount\}/i',
                        $customer,
                        $cmd
                    );
                    $parts = preg_split('/\s+/', $giveTemplate);
                    if (count($parts) >= 3) {
                        $giveTemplate = $parts[0] . ' ' . $parts[1] . ' ';
                    }
                    break;
                }
            }
        }

        // RCON (выполняем до сохранения, чтобы не блокировать файл надолго)
        // Но savePayments внутри withPaymentsLock сохранит корректное состояние
        $rconSuccess = false;
        $rconResult  = '';

        if ($giveTemplate && $wonItemId) {
            try {
                require_once __DIR__ . '/rcon.php';
                $rcon = new MinecraftRcon(RCON_HOST, RCON_PORT, RCON_PASSWORD, RCON_TIMEOUT);
                $rcon->connect();
                $cmd = $giveTemplate . $wonItemId . ' ' . $itemAmount;
                $rconResult = rtrim($rcon->command($cmd));
                $rcon->disconnect();
                $rconSuccess = true;
            } catch (Exception $e) {
                $rconResult = 'RCON: внутренняя ошибка сервера';
            }
        } else {
            $rconResult = 'Команда для выдачи не настроена в товаре';
        }

        // Фиксируем выигрыш
        $p['spins_left']--;
        $p['won_items'][] = [
            'item'       => $wonName,
            'item_id'    => $wonItemId,
            'amount'     => $itemAmount,
            'rcon_success' => $rconSuccess,
            'rcon_response' => $rconResult,
            'won_at'     => date('Y-m-d H:i:s'),
        ];
        $p['pending_item']    = null;
        $p['pending_item_id'] = null;
        $p['pending_amount']  = null;

        if ($p['spins_left'] <= 0) {
            $p['status'] = 'used';
            $p['used']   = true;
        }

        $response = [
            'success'     => true,
            'spins_left'  => $p['spins_left'],
            'spins_total' => $p['spins_total'],
            'all_used'    => $p['spins_left'] <= 0,
            'item' => [
                'name'    => $wonName,
                'amount'  => $itemAmount,
            ],
            'rcon' => [
                'success' => $rconSuccess,
                'response' => $rconResult,
            ],
        ];
        return true;
    });

    if (!$locked) {
        echo json_encode(['success' => false, 'response' => 'Ошибка блокировки файла платежей']);
        exit;
    }
    echo json_encode($response);
    exit;
}

// ========== СОЗДАНИЕ ПЛАТЕЖА (обычный товар, multi-item) ==========
if ($action === 'create_payment') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $customer = trim($input['customer'] ?? '');
    $email = trim($input['email'] ?? '');
    $coupon = trim($input['coupon'] ?? '');
    $payType = $input['payment_type'] ?? null;
    $payMethod = $input['payment_method'] ?? null;
    $serverId = (int)($input['server_id'] ?? SERVER_ID);
    $productsRaw = $input['products'] ?? '{}';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    if (!$customer || !$email) {
        echo json_encode(['success' => false, 'response' => 'Заполните ник и email']);
        exit;
    }
    if (!validateNickname($customer)) {
        echo json_encode(['success' => false, 'response' => 'Ник должен содержать только латиницу, цифры и _, от 3 до 16 символов']);
        exit;
    }
    $customer = sanitizeInput($customer);
    $email    = sanitizeInput($email);

    if (is_string($productsRaw)) {
        $productsArr = json_decode($productsRaw, true);
    } else {
        $productsArr = $productsRaw;
    }
    if (empty($productsArr)) {
        echo json_encode(['success' => false, 'response' => 'Нет товаров для покупки']);
        exit;
    }

    // ===== ТЕСТОВЫЙ РЕЖИМ =====
    if (isTestMode()) {
        $rconResults = [];
        $allSuccess = true;
        $prodUrl = "https://easydonate.ru/api/v3/shop/products?server_id=" . $serverId;
        $prodData = easyDonateRequest($prodUrl);

        if (!$prodData || empty($prodData['response'])) {
            echo json_encode([
                'success' => false,
                'response' => 'Тестовый режим: не удалось загрузить товары из EasyDonate API'
            ]);
            exit;
        }

        $productMap = [];
        foreach ($prodData['response'] as $p) {
            $productMap[(int)$p['id']] = $p;
        }
        require_once __DIR__ . '/rcon.php';
        try {
            $rcon = new MinecraftRcon(RCON_HOST, RCON_PORT, RCON_PASSWORD, RCON_TIMEOUT);
            $rcon->connect();
            foreach ($productsArr as $pid => $qty) {
                $pid = (int)$pid;
                $qty = max(1, (int)$qty);
                $product = $productMap[$pid] ?? null;
                if (!$product || empty($product['commands'])) {
                    $rconResults[] = "Товар #{$pid}: нет команд для выдачи";
                    $allSuccess = false;
                    continue;
                }
                $amountPer = $product['number'] ?? 1;
                for ($i = 0; $i < $qty; $i++) {
                    foreach ($product['commands'] as $cmd) {
                        $cmd = str_replace(['{player}', '{user}', '{username}', '{amount}'], [$customer, $customer, $customer, $amountPer], $cmd);
                        try {
                            $result = rtrim($rcon->command($cmd));
                            $rconResults[] = $cmd . ' → ' . $result;
                        } catch (Exception $e) {
                            $rconResults[] = $cmd . ' → ERROR: ' . $e->getMessage();
                            $allSuccess = false;
                        }
                    }
                }
            }
            $rcon->disconnect();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'response' => 'RCON: внутренняя ошибка сервера']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'response' => [
                'url' => $scheme . '://' . $_SERVER['HTTP_HOST'] . '/shop/index.php?status=success',
                'test_mode' => true,
                'rcon_all_ok' => $allSuccess,
                'rcon_results' => $rconResults,
            ]
        ]);
        exit;
    }

    $params = [
        'customer'    => $customer,
        'server_id'   => $serverId,
        'products'    => json_encode($productsArr),
        'email'       => $email,
        'success_url' => $scheme . '://' . $_SERVER['HTTP_HOST'] . '/shop/index.php?status=success'
    ];

    if ($coupon) $params['coupon'] = $coupon;

    if ($payType) {
        $params['payment_type'] = $payType;
        if ($payMethod) {
            $params['payment_method'] = $payMethod;
        }
    }

    $finalUrl = "https://easydonate.ru/api/v3/shop/payment/create?" . http_build_query($params);

    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Shop-Key: " . SHOP_KEY . "\r\n"
        ],
        "ssl" => sslContext()
    ];

    $context = stream_context_create($opts);
    $response = @file_get_contents($finalUrl, false, $context);

    if ($response === false) {
        echo json_encode(["success" => false, "response" => "Ошибка API"]);
    } else {
        echo $response;
    }
    exit;
}

// ========== ЗАГРУЗКА ТОВАРОВ ==========
$CASE_ID = isset($_GET['case_id']) ? (int)$_GET['case_id'] : 0;

$url = "https://easydonate.ru/api/v3/shop/products?server_id=" . SERVER_ID;
$data = easyDonateRequest($url);

if ($data === null) {
    echo json_encode(['success' => false, 'response' => 'Ошибка загрузки товаров']);
    exit;
}

if ($CASE_ID > 0) {
    foreach ($data['response'] as $product) {
        if ((int)$product['id'] === $CASE_ID) {
            $items = parseCaseItems($product);
            echo json_encode([
                'success' => true,
                'type' => 'case',
                'items' => $items,
                'case_name' => $product['name'],
                'price' => $product['price'] ?? 0,
            ]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'response' => 'Кейс не найден']);
    exit;
}

// Определяем тип "case" для товаров, у которых есть команды с "выбил:"
$products = $data['response'];
foreach ($products as &$product) {
    if (isCaseProduct($product)) {
        $product['type'] = 'case';
    }
}
unset($product);

echo json_encode(['success' => true, 'response' => $products]);
