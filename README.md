# Minecraft Times

Веб-сайт ванильного Minecraft-сервера **Minecraft Times**. Включает лендинг, базу знаний, галерею, а также полноценный магазин доната с рулеткой кейсов и админ-панелью аналитики.

---

## Структура проекта

```
├── siteV2/                   # Лендинг сервера (HTML + Tailwind)
│   ├── index.html            # Главная страница
│   ├── wiki.html             # База знаний
│   ├── rules.html            # Правила сервера
│   ├── gallery.html          # Галерея
│   ├── stories.html          # Истории игроков
│   ├── styles.css            # Кастомные стили
│   ├── script.js             # Интерактивность
│   ├── .htaccess             # Apache rewrite + кеширование
│   └── shop/                 # PHP-магазин доната
│       ├── index.php         # Витрина товаров (EasyDonate)
│       ├── admin.php         # Админ-панель аналитики
│       ├── api.php           # API (создание платежей, рулетка)
│       ├── roulette.php      # Рулетка открытия кейсов
│       ├── webhook.php       # Вебхук EasyDonate
│       ├── helpers.php       # Вспомогательные функции
│       ├── config.example.php # Шаблон конфигурации
│       ├── rcon.php          # RCON-протокол для выдачи предметов
│       └── assets/           # CSS, JS, изображения
├── .gitignore
├── LICENSE
└── README.md
```

---

## Требования

- **Веб-сервер**: Apache + PHP 8.0+ с mod_rewrite
- **Расширения PHP**: curl, json, openssl
- **Node.js** (опционально, для сборки Tailwind)

---

## Установка и настройка

### 1. Клонирование

```bash
git clone https://github.com/ваш-username/minecraft-times.git
cd minecraft-times
```

### 2. Настройка конфигурации

```bash
cp siteV2/shop/config.example.php siteV2/shop/config.php
```

Отредактируйте `siteV2/shop/config.php`:

| Параметр | Описание |
|----------|----------|
| `SHOP_KEY` | API-ключ магазина EasyDonate |
| `SERVER_ID` | ID сервера в EasyDonate |
| `RCON_HOST` | IP Minecraft-сервера |
| `RCON_PORT` | Порт RCON (по умолчанию 25575) |
| `RCON_PASSWORD` | Пароль RCON |
| `ADMIN_PASS` | Пароль для входа в админ-панель |

### 3. Настройка вебхука EasyDonate

В личном кабинете EasyDonate укажите URL вебхука:

```
https://ваш-домен/shop/webhook.php
```

### 4. Tailwind CSS (опционально)

```bash
cd siteV2
npm install
npx tailwindcss -i ./src/input.css -o ./tailwind.css --watch
```

### 5. Права доступа

Убедитесь, что веб-сервер имеет право записи в `siteV2/shop/payments.json`.

---

## Страницы магазина

| URL | Описание |
|-----|----------|
| `/shop/` | Витрина товаров и кейсов |
| `/shop/admin.php` | Админ-панель со статистикой продаж |
| `/shop/roulette.php?id=XXX` | Демо рулетки |
| `/shop/roulette.php?payment_id=XXX` | Открытие оплаченного кейса |
| `/shop/webhook.php` | Вебхук EasyDonate (POST) |

---

## Особенности

- **Лендинг**: адаптивный дизайн, виджет онлайна, анимации
- **Магазин**: интеграция с EasyDonate, кейсы с рулеткой, несколько способов оплаты
- **Рулетка**: визуальная анимация прокрутки, sound design, multi-spin
- **Админка**: аналитика продаж, Unit-экономика, когортный анализ, тепловая карта
- **Выдача**: RCON-команды напрямую на Minecraft-сервер

---

## Безопасность

Данный репозиторий публичен. Вы можете попробывать поломать сайт по ссылке shop.minecraft-times.fun
Если найдете уязвимость, будем рады вашему пул реквесту.


---

## Лицензия

MIT
"# EasyDonateCustomSite" 
