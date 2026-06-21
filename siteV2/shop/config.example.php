<?php

// ============================================================
// КОНФИГУРАЦИЯ МАГАЗИНА
// ============================================================
// Скопируйте этот файл в config.php и заполните реальные данные.
// config.php добавлен в .gitignore — он не попадёт в репозиторий.

define('SHOP_KEY',     'ваш_ключ_магазина_easydonate');
define('SERVER_ID',    0); // ID сервера в EasyDonate
define('RCON_HOST',    '127.0.0.1');
define('RCON_PORT',    25575);
define('RCON_PASSWORD','ваш_пароль_rcon');
define('RCON_TIMEOUT', 3);
define('ADMIN_PASS',   'ваш_пароль_админки');

define('PAYMENTS_FILE', __DIR__ . '/payments.json');
define('TEST_MODE_FILE', __DIR__ . '/test_mode.json');
