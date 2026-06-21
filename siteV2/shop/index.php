<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Купить донат на сервере MinecraftTimes">
    <meta name="keywords" content="minecraft, автодонат, онлайн-магазин, MinecraftTimes">
    <meta property="og:title" content="MinecraftTimes">
    <meta property="og:url" content="">
    <title>MinecraftTimes — Магазин</title>

    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="assets/css/animate.min.css">
    <link rel="stylesheet" href="assets/css/icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pay-button.css">
    <link rel="stylesheet" href="assets/css/easydonate.css">
    <link rel="stylesheet" href="assets/css/framework.extras-min.css">

    <style>
        body {
            --color-accent: #2a354f;
            --color-accent-125: #2a354f30;
            --color-accent-15: #2a354f26;
            --color-accent-25: #2a354f40;
            --color-accent-50: #2a354f80;
            --color-accent-75: #2a354fC0;
        }
    </style>
</head>
<body class="dark-theme">

    <!-- ═══ Navbar ═══ -->
    <nav class="spirit-navbar">
        <div class="navbar-general">
            <div class="container">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="d-flex align-items-center" style="gap: 72px">
                            <div class="d-flex align-items-center" style="gap: 12px">

                                <a class="d-lg-none d-block navbar-dropdown-menu-btn" href="#" data-toggle="collapse" data-target=".navbar-dropdown-menu-wrapper" aria-expanded="false" aria-label="Навигация">
                                    <svg class="collapse-closed d-none" width="40" height="40" viewBox="0 0 40 40" fill="none">
                                        <path d="M12 14H28M12 20H28M12 26H28" stroke="var(--color-default)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <svg class="collapse-collapsed d-none" width="40" height="40" viewBox="0 0 40 40" fill="none">
                                        <path d="M12 12L28 28M28 12L12 28" stroke="var(--color-default)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>

                                <div class="collapse navbar-dropdown-menu-wrapper" aria-labelledby="dropdown">
                                    <div class="navbar-dropdown-menu">
                                        <ul class="navbar-dropdown-menu-content">
                                            <li class="navbar-dropdown-menu-item">
                                                <a href="#shop">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                        <path d="M20 7.5L12 3L4 7.5M20 7.5V16.5L12 21M20 7.5L12 12M12 21L4 16.5V7.5M12 21V12M4 7.5L12 12M8.2 9.8L15.8 5.2" stroke="var(--color-default)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Магазин
                                                </a>
                                            </li>
                                            <li class="navbar-dropdown-menu-item">
                                                <a href="#servers">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                        <path d="M18 12C18.7956 12 19.5587 11.6839 20.1213 11.1213C20.6839 10.5587 21 9.79565 21 9V7C21 6.20435 20.6839 5.44129 20.1213 4.87868C19.5587 4.31607 18.7956 4 18 4H6C5.20435 4 4.44129 4.31607 3.87868 4.87868C3.31607 5.44129 3 6.20435 3 7V9C3 9.79565 3.31607 10.5587 3.87868 11.1213C4.44129 11.6839 5.20435 12 6 12M18 12H6M18 12C18.7956 12 19.5587 12.3161 20.1213 12.8787C20.6839 13.4413 21 14.2044 21 15V17C21 17.7956 20.6839 18.5587 20.1213 19.1213C19.5587 19.6839 18.7956 20 18 20H6C5.20435 20 4.44129 19.6839 3.87868 19.1213C3.31607 18.5587 3 17.7956 3 17V15C3 14.2044 3.31607 13.4413 3.87868 12.8787C4.44129 12.3161 5.20435 12 6 12M7 8V8.01M7 16V16.01M11 8H17M11 16H17" stroke="var(--color-default)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Наши серверы
                                                </a>
                                            </li>
                                            <li class="navbar-dropdown-menu-item">
                                                <a href="/__instruction">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                        <path d="M12 16V16.01M12 13C12.4498 13.0014 12.8868 12.8511 13.2407 12.5734C13.5945 12.2958 13.8444 11.907 13.95 11.4698C14.0557 11.0327 14.0109 10.5726 13.8229 10.1641C13.6349 9.75548 13.3147 9.42219 12.914 9.218C12.5162 9.01421 12.0611 8.95102 11.6228 9.03872C11.1845 9.12642 10.7888 9.35983 10.5 9.701M12 3C19.2 3 21 4.8 21 12C21 19.2 19.2 21 12 21C4.8 21 3 19.2 3 12C3 4.8 4.8 3 12 3Z" stroke="var(--color-default)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Инструкция по покупке
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <a href="/">
                                    <div class="spirit-logo-wrapper">
                                        <img src="assets/img/logo.png" class="spirit-shop-logo" alt="MinecraftTimes">
                                        <h3 class="spirit-shop-name">MinecraftTimes</h3>
                                    </div>
                                </a>
                            </div>

                            <div>
                                <ul class="spirit-navbar-menu d-lg-flex d-none">
                                    <li class="spirit-navbar-item">
                                        <a href="#shop">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                <path d="M20 7.5L12 3L4 7.5M20 7.5V16.5L12 21M20 7.5L12 12M12 21L4 16.5V7.5M12 21V12M4 7.5L12 12M8.2 9.8L15.8 5.2" stroke="var(--color-default)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Магазин
                                        </a>
                                    </li>
                                    <li class="spirit-navbar-item">
                                        <a href="#servers">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                <path d="M18 12C18.7956 12 19.5587 11.6839 20.1213 11.1213C20.6839 10.5587 21 9.79565 21 9V7C21 6.20435 20.6839 5.44129 20.1213 4.87868C19.5587 4.31607 18.7956 4 18 4H6C5.20435 4 4.44129 4.31607 3.87868 4.87868C3.31607 5.44129 3 6.20435 3 7V9C3 9.79565 3.31607 10.5587 3.87868 11.1213C4.44129 11.6839 5.20435 12 6 12M18 12H6M18 12C18.7956 12 19.5587 12.3161 20.1213 12.8787C20.6839 13.4413 21 14.2044 21 15V17C21 17.7956 20.6839 18.5587 20.1213 19.1213C19.5587 19.6839 18.7956 20 18 20H6C5.20435 20 4.44129 19.6839 3.87868 19.1213C3.31607 18.5587 3 17.7956 3 17V15C3 14.2044 3.31607 13.4413 3.87868 12.8787C4.44129 12.3161 5.20435 12 6 12M7 8V8.01M7 16V16.01M11 8H17M11 16H17" stroke="var(--color-default)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Наши серверы
                                        </a>
                                    </li>
                                    <li class="spirit-navbar-item">
                                        <a href="/__instruction">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 16V16.01M12 13C12.4498 13.0014 12.8868 12.8511 13.2407 12.5734C13.5945 12.2958 13.8444 11.907 13.95 11.4698C14.0557 11.0327 14.0109 10.5726 13.8229 10.1641C13.6349 9.75548 13.3147 9.42219 12.914 9.218C12.5162 9.01421 12.0611 8.95102 11.6228 9.03872C11.1845 9.12642 10.7888 9.35983 10.5 9.701M12 3C19.2 3 21 4.8 21 12C21 19.2 19.2 21 12 21C4.8 21 3 19.2 3 12C3 4.8 4.8 3 12 3Z" stroke="var(--color-default)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Инструкция по покупке
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex align-items-center justify-content-end" style="gap: 6px">


                            <a class="cart-badge flex-shrink-0" data-toggle="modal" data-target="#cart">
                                <div class="cart-icon-wrapper position-relative">
                                    <svg width="18" height="20" viewBox="0 0 18 20" fill="none">
                                        <path d="M3 15C3.53043 15 4.03914 15.2107 4.41421 15.5858C4.78929 15.9609 5 16.4696 5 17C5 17.5304 4.78929 18.0391 4.41421 18.4142C4.03914 18.7893 3.53043 19 3 19C2.46957 19 1.96086 18.7893 1.58579 18.4142C1.21071 18.0391 1 17.5304 1 17C1 16.4696 1.21071 15.9609 1.58579 15.5858C1.96086 15.2107 2.46957 15 3 15ZM3 15H14M3 15V1H1M14 15C14.5304 15 15.0391 15.2107 15.4142 15.5858C15.7893 15.9609 16 16.4696 16 17C16 17.5304 15.7893 18.0391 15.4142 18.4142C15.0391 18.7893 14.5304 19 14 19C13.4696 19 12.9609 18.7893 12.5858 18.4142C12.2107 18.0391 12 17.5304 12 17C12 16.4696 12.2107 15.9609 12.5858 15.5858C12.9609 15.2107 13.4696 15 14 15ZM3 3L17 4L16 11H9.5H3" stroke="var(--color-default)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="cart-counter">0</span>
                                </div>
                                <span class="cart-cost d-sm-block d-none">0 ₽</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ═══ Jumbotron ═══ -->
    <section class="section section-jumbotron">
        <div class="container-fluid">
            <div class="spirit-shop-jumbotron overlay-dark" style="background-image: url(https://cdn.easydonate.ru/images/backgrounds/c4/fb/c4fbb6ed1e85f1495f60b0cf53c956531821d547d7e36c5092bb8b1809fde3fe.png)">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <h1 class="shop-name text-lg-left text-center">MinecraftTimes</h1>
                            <div class="spirit-shop-jumbotron-footer justify-content-lg-start justify-content-center">
                                <a href="#shop" class="btn btn-xl btn-accent rounded-pill" style="width: 294px">
                                    Перейти в магазин
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-6 d-lg-block d-none jumbotron-img">
                            <img src="assets/img/hero.png" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ Shop ═══ -->
    <section class="section section-shop wow fadeIn" id="shop">
        <div class="container">
            <div class="shop">
                <div class="row">
                    <div class="col-xl-3 mb-xl-0 mb-4">
                        <ul class="spirit-shop-categories-wrapper dragscroll" id="categoriesList">
                            <li class="category spirit-shop-category all-products active filter-button" data-filter="*">
                                <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
                                    <path d="M19.5 6.5H8.66667C7.47005 6.5 6.5 7.47005 6.5 8.66667V19.5C6.5 20.6966 7.47005 21.6667 8.66667 21.6667H19.5C20.6966 21.6667 21.6667 20.6966 21.6667 19.5V8.66667C21.6667 7.47005 20.6966 6.5 19.5 6.5Z" stroke="var(--color-default)" stroke-width="3" stroke-linejoin="round"/>
                                    <path d="M19.5 30.3333H8.66667C7.47005 30.3333 6.5 31.3034 6.5 32.5V43.3333C6.5 44.5299 7.47005 45.5 8.66667 45.5H19.5C20.6966 45.5 21.6667 44.5299 21.6667 43.3333V32.5C21.6667 31.3034 20.6966 30.3333 19.5 30.3333Z" stroke="var(--color-default)" stroke-width="3" stroke-linejoin="round"/>
                                    <path d="M37.9167 21.6667C38.9125 21.6667 39.8986 21.4705 40.8187 21.0894C41.7387 20.7083 42.5747 20.1497 43.2789 19.4456C43.9831 18.7414 44.5417 17.9054 44.9227 16.9853C45.3038 16.0653 45.5 15.0792 45.5 14.0833C45.5 13.0875 45.3038 12.1014 44.9227 11.1813C44.5417 10.2613 43.9831 9.42528 43.2789 8.72111C42.5747 8.01693 41.7387 7.45834 40.8187 7.07725C39.8986 6.69615 38.9125 6.5 37.9167 6.5C35.9054 6.5 33.9766 7.29896 32.5544 8.72111C31.1323 10.1433 30.3333 12.0721 30.3333 14.0833C30.3333 16.0946 31.1323 18.0234 32.5544 19.4456C33.9766 20.8677 35.9054 21.6667 37.9167 21.6667Z" stroke="var(--color-default)" stroke-width="3" stroke-linejoin="round"/>
                                    <path d="M43.3333 30.3333H32.5C31.3034 30.3333 30.3333 31.3034 30.3333 32.5V43.3333C30.3333 44.5299 31.3034 45.5 32.5 45.5H43.3333C44.5299 45.5 45.5 44.5299 45.5 43.3333V32.5C45.5 31.3034 44.5299 30.3333 43.3333 30.3333Z" stroke="var(--color-default)" stroke-width="3" stroke-linejoin="round"/>
                                </svg>
                                <span>
                                    <p class="category-name">Все товары</p>
                                    <p class="category-products-count">0 товаров</p>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div class="col-xl-9">
                        <h3 class="d-xl-block d-none mb-36">Магазин</h3>
                        <div class="shop-products">
                            <div class="row no-gutters filter-items" id="productsGrid">
                                <div class="col-12 text-center" style="padding:60px 0">
                                    <div class="spinner-border" style="color:var(--color-accent)"></div>
                                    <p class="mt-24">Загрузка товаров...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ Servers ═══ -->
    <section class="section section-md" id="servers">
        <div class="container text-center">
            <h2 class="mb-36">Наши серверы</h2>
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card server-card">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-24 flex-wrap" style="gap: 10px">
                                <div class="badge-xxl">
                                    <svg width="25" height="24" viewBox="0 0 25 24" fill="none">
                                        <path d="M18.5 12C19.2956 12 20.0587 11.6839 20.6213 11.1213C21.1839 10.5587 21.5 9.79565 21.5 9V7C21.5 6.20435 21.1839 5.44129 20.6213 4.87868C20.0587 4.31607 19.2956 4 18.5 4H6.5C5.70435 4 4.94129 4.31607 4.37868 4.87868C3.81607 5.44129 3.5 6.20435 3.5 7V9C3.5 9.79565 3.81607 10.5587 4.37868 11.1213C4.94129 11.6839 5.70435 12 6.5 12M18.5 12H6.5M18.5 12C19.2956 12 20.0587 12.3161 20.6213 12.8787C21.1839 13.4413 21.5 14.2044 21.5 15V17C21.5 17.7957 21.1839 18.5587 20.6213 19.1213C20.0587 19.6839 19.2956 20 18.5 20H6.5C5.70435 20 4.94129 19.6839 4.37868 19.1213C3.81607 18.5587 3.5 17.7957 3.5 17V15C3.5 14.2044 3.81607 13.4413 4.37868 12.8787C4.94129 12.3161 5.70435 12 6.5 12M7.5 8V8.01M7.5 16V16.01M11.5 8H17.5M11.5 16H17.5" stroke="#000" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <h3 class="mb-0 font-weight-500">Выживание</h3>
                            </div>
                            <div class="d-flex align-items-center flex-wrap justify-content-center" style="gap: 12px">
                                <p class="color-neutral mb-0">Играют:</p>
                                <div class="d-flex align-items-center" style="gap: 7px">
                                    <svg width="25" height="25" viewBox="0 0 25 25" fill="none">
                                        <circle opacity="0.25" cx="12.5" cy="12.5" r="12.5" fill="#67FF4F"/>
                                        <circle cx="12.5" cy="12.5" r="6.5" fill="#67FF4F"/>
                                    </svg>
                                    <p class="mb-0 color-default">18 из 60</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ Footer ═══ -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center" style="padding-bottom:48px">
                    <div class="spirit-logo-wrapper justify-content-center mb-24">
                        <img src="assets/img/logo.png" class="spirit-shop-logo" alt="MinecraftTimes">
                        <h3 class="spirit-shop-name">MinecraftTimes</h3>
                    </div>
                    <p style="color:rgba(255,255,255,.5);font-size:14px">© 2024 MinecraftTimes. Все права защищены.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- ═══ Cart Modal ═══ -->
    <div class="modal fade" id="cart" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content cart">
                <div class="cart-content">
                    <div style="padding: 50px; height: 100%; position: relative;">
                        <button type="button" class="cart-close" data-dismiss="modal" onclick="$('#cart').modal('hide')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M18 6L6 18M6 6L18 18" stroke="#1B1C1C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <h2 style="margin-bottom: 36px;">Корзина</h2>
                        <div id="cartProductsList">
                            <div class="text-center"><p>Корзина пуста</p></div>
                        </div>
                        <div style="margin-top: 24px; border-top: 1px solid var(--border-color-default); padding-top: 24px;">
                            <div class="d-flex justify-content-between align-items-center mb-24">
                                <span style="font-size: 18px; color: var(--color-default);">Итого:</span>
                                <span class="h3 mb-0" style="color: var(--color-accent); font-weight: 700;" id="cartTotalDisplay">0 ₽</span>
                            </div>
                            <button class="btn btn-accent rounded-pill btn-lg w-100" onclick="checkoutCart()">Оформить заказ</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Payment Modal ═══ -->
    <div class="modal fade" id="buyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="border-radius:33px;padding:30px;background:var(--spirit-card-background-color)">
                <button type="button" class="cart-close" onclick="closeModal()" style="position:absolute;right:20px;top:20px;left:auto;background:var(--spirit-card-background-color-hover)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M18 6L6 18M6 6L18 18" stroke="var(--color-default)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <h2 style="margin-top:0;color:var(--color-default)">Оформление заказа</h2>
                <p style="color:var(--color-neutral);font-size:14px;margin-bottom:20px">Покупка: <span id="modalTitle" style="color:var(--color-default);font-weight:bold;"></span> за <span id="modalPrice" style="color:var(--color-accent);"></span></p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px">
                    <div class="form-control-wrapper">
                        <label>Ваш ник</label>
                        <input type="text" id="nickname" placeholder="Steve" class="form-control">
                    </div>
                    <div class="form-control-wrapper">
                        <label>E-mail</label>
                        <input type="email" id="emailInput" placeholder="mail@mail.ru" class="form-control">
                    </div>
                </div>

                <div id="quantityRow" style="display:none;margin-bottom:20px">
                    <label style="font-size:11px;color:var(--color-accent);font-weight:bold;text-transform:uppercase">Количество открытий:</label>
                    <div style="display:flex;gap:8px;margin-top:8px">
                        <button class="qty-btn active" data-qty="1" onclick="setQuantity(1)">1</button>
                        <button class="qty-btn" data-qty="3" onclick="setQuantity(3)">3</button>
                        <button class="qty-btn" data-qty="5" onclick="setQuantity(5)">5</button>
                        <button class="qty-btn" data-qty="10" onclick="setQuantity(10)">10</button>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px">
                    <div class="form-control-wrapper">
                        <label>Промокод</label>
                        <input type="text" id="promoCode" placeholder="Введите промокод" class="form-control">
                    </div>
                    <div class="form-control-wrapper">
                        <label>&nbsp;</label>
                        <div style="font-size:12px;color:var(--color-neutral);padding:10px 0">Есть промокод? Введите выше</div>
                    </div>
                </div>

                <label style="font-size:11px;color:var(--color-accent);font-weight:bold;text-transform:uppercase;margin-bottom:12px">Выберите способ оплаты:</label>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                    <div class="pay-method" onclick="processPurchase('sbp', 'tinkoff')">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 60 40'%3E%3Crect fill='%231a5a9c' width='60' height='40' rx='4'/%3E%3Ctext fill='%23fff' font-size='18' x='30' y='27' text-anchor='middle'%3EСБП%3C/text%3E%3C/svg%3E">
                        <span class="pay-method-name">СБП</span>
                    </div>
                    <div class="pay-method" onclick="processPurchase('card', 'tinkoff')">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 60 40'%3E%3Crect fill='%230078c0' width='60' height='40' rx='4'/%3E%3Ctext fill='%23fff' font-size='18' x='30' y='27' text-anchor='middle'%3EМИР%3C/text%3E%3C/svg%3E">
                        <span class="pay-method-name">Карты РФ</span>
                    </div>
                    <div class="pay-method" onclick="processPurchase('card', 'yourpaymentsworld')">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 60 40'%3E%3Crect fill='%23ffb600' width='60' height='40' rx='4'/%3E%3Ccircle fill='%23eb001b' cx='28' cy='20' r='10'/%3E%3Ccircle fill='%23f79e1b' cx='32' cy='20' r='10'/%3E%3C/svg%3E">
                        <span class="pay-method-name">Ин. карты</span>
                    </div>
                    <div class="pay-method" onclick="processPurchase('advcash', 'advcash')">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 60 40'%3E%3Crect fill='%232d2d2d' width='60' height='40' rx='4'/%3E%3Ctext fill='%23fff' font-size='18' x='30' y='27' text-anchor='middle'%3EV%3C/text%3E%3C/svg%3E">
                        <span class="pay-method-name">Volet</span>
                    </div>
                </div>

                <div style="font-size:12px;color:var(--color-neutral);text-align:left">
                    Нажимая на способ оплаты, вы принимаете <a href="#" style="color:var(--color-accent)">оферту</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Modal Backdrop ═══ -->
    <div class="modal-backdrop fade"></div>

    <!-- ═══ Scripts ═══ -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery.cookie.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/wow.min.js"></script>
    <script src="assets/js/dragscroll.min.js"></script>
    <script src="assets/js/app.js"></script>

    <script>
        new WOW().init();
    </script>

</body>
</html>
