$(function () {
    // ─── Navbar collapse ──────────────────────────────────────
    $('[data-toggle="collapse"]').on('click', function () {
        var target = $(this).data('target');
        $(target).collapse('toggle');
    });

    // ─── Cart ─────────────────────────────────────────────────
    var cartItems = [];
    var cartTotal = 0;

    function updateCartUI() {
        var count = cartItems.reduce(function (sum, item) { return sum + item.quantity; }, 0);
        $('.cart-counter').text(count);
        var total = cartItems.reduce(function (sum, item) { return sum + (item.price * item.quantity); }, 0);
        $('.cart-cost').text(total + ' ₽');
        $('#cartTotalDisplay').text(total + ' ₽');
        cartTotal = total;
        renderCartItems();
    }

    function renderCartItems() {
        var html = '';
        if (cartItems.length === 0) {
            html = '<div class="text-center p-4"><p>Корзина пуста</p></div>';
        } else {
            cartItems.forEach(function (item, idx) {
                html += '<div class="d-flex align-items-center gap-12 mb-24" style="gap:16px">';
                html += '<div style="width:80px;height:80px;border-radius:20px;overflow:hidden;border:1px solid var(--border-color-default);flex-shrink:0;"><img src="' + (item.image || 'assets/img/product_default.png') + '" style="width:100%;height:100%;object-fit:contain"></div>';
                html += '<div style="flex:1"><div style="font-weight:500;color:var(--color-default)">' + item.name + '</div><div style="color:var(--color-accent);font-weight:600">' + (item.price * item.quantity) + ' ₽</div></div>';
                html += '<div class="d-flex align-items-center gap-10"><button class="btn btn-sm btn-outline-accent" onclick="removeFromCart(' + idx + ')">✕</button></div>';
                html += '</div>';
            });
        }
        $('#cartProductsList').html(html);
    }

    window.addToCart = function (id, name, price, image, isCase) {
        if (isCase) {
            openBuyModal(id, name, price, true);
            return;
        }
        var existing = cartItems.find(function (i) { return i.id === id; });
        if (existing) {
            existing.quantity++;
        } else {
            cartItems.push({ id: id, name: name, price: price, image: image, quantity: 1 });
        }
        updateCartUI();
        $('#cart').modal('show');
    };

    window.removeFromCart = function (idx) {
        cartItems.splice(idx, 1);
        updateCartUI();
    };

    window.clearCart = function () {
        cartItems = [];
        updateCartUI();
    };

    window.openCart = function () {
        $('#cart').modal('show');
    };

    window.checkoutCart = function () {
        if (cartItems.length === 0) {
            showToast('Корзина пуста', 'Добавьте товары в корзину', 'error');
            return;
        }
        var total = cartItems.reduce(function (sum, item) { return sum + (item.price * item.quantity); }, 0);
        var names = cartItems.map(function (item) { return item.name; }).join(', ');
        selectedCartItems = cartItems.slice();
        selectedProductIsCase = false;
        selectedProductName = 'Корзина (' + cartItems.length + ' шт.)';
        selectedQuantity = 1;
        $('#modalTitle').text(names.length > 50 ? names.substring(0, 50) + '...' : names);
        $('#modalPrice').text(total + ' ₽');
        $('#quantityRow').hide();
        $('#promoCode').val('');
        $('#cart').modal('hide');
        $('#buyModal').modal('show');
    };

    // ─── Cart modal ───────────────────────────────────────────
    $('#cart').on('show.bs.modal', function () {
        $('body').addClass('modal-open');
        $('.modal-backdrop').addClass('show');
    });
    $('#cart').on('hidden.bs.modal', function () {
        $('body').removeClass('modal-open');
        $('.modal-backdrop').removeClass('show');
    });

    // ─── Products loading ─────────────────────────────────────
    var allProducts = [];
    var isotopeInstance = null;

    function getCategoryIcon(catName) {
        var icons = {
            'Привилегии': '<svg width="52" height="52" viewBox="0 0 52 52" fill="none"><path d="M32.0493 36.9698C32.0493 37.7 32.1728 38.3088 32.1728 38.9198C32.1728 42.8198 29.4905 45.5 25.7118 45.5C21.8118 45.5 19.2552 42.8177 19.2552 38.5537V36.9698C17.1817 38.4323 15.8405 38.9198 14.0118 38.9198C10.7228 38.9198 7.67432 35.75 7.67432 32.2162C7.67432 29.6573 9.50298 27.4625 12.1853 26.2448L12.792 26C9.38165 24.2948 7.67432 22.3427 7.67432 19.6625C7.67432 16.0073 10.478 12.9588 14.0118 12.9588C15.4743 12.9588 17.3051 13.6912 18.6441 14.6662L19.253 14.9088C19.253 14.1787 19.1317 13.5677 19.1317 13.0802C19.1317 9.18017 21.8118 6.5 25.5883 6.5C29.4883 6.5 32.0472 9.18017 32.0472 13.4463V14.3L31.9258 14.9088C33.9972 13.4463 35.3383 12.9588 37.167 12.9588C40.4581 12.9588 43.5045 16.1287 43.5045 19.6625C43.5045 22.2213 41.6758 24.4162 38.9956 25.6338L38.3847 26C41.7972 27.7052 43.5045 29.6573 43.5045 32.3375C43.5045 35.9948 40.7008 39.0412 37.167 39.0412C35.7045 39.0412 33.7545 38.4323 32.5347 37.3338L32.0472 36.9677L32.0493 36.9698Z" stroke="var(--spirit-shop-category-icon-color)" stroke-width="3" stroke-linejoin="round"/></svg>',
            'Рулетка': '<svg width="52" height="52" viewBox="0 0 52 52" fill="none"><path d="M32.5 19.4999H32.5217M35.8692 8.32645L43.6735 16.1308C44.2524 16.7096 44.7117 17.3968 45.025 18.1532C45.3383 18.9095 45.4996 19.7202 45.4996 20.5389C45.4996 21.3575 45.3383 22.1682 45.025 22.9245C44.7117 23.6809 44.2524 24.3681 43.6735 24.9469L37.947 30.6734C37.3682 31.2524 36.6809 31.7116 35.9246 32.0249C35.1682 32.3383 34.3576 32.4995 33.5389 32.4995C32.7202 32.4995 31.9096 32.3383 31.1532 32.0249C30.3969 31.7116 29.7097 31.2524 29.1308 30.6734L28.4787 30.0213L14.2697 44.2303C13.5497 44.9501 12.5993 45.3935 11.5852 45.4826L11.206 45.4999H8.66667C8.13598 45.4999 7.62377 45.305 7.22719 44.9524C6.83062 44.5998 6.57726 44.1138 6.51517 43.5868L6.5 43.3333V40.7939C6.50025 39.7766 6.85844 38.7918 7.51183 38.0119L7.76967 37.7303L8.66667 36.8333H13V32.4999H17.3333V28.1666L21.9787 23.5213L21.3265 22.8691C20.7476 22.2903 20.2883 21.6031 19.975 20.8467C19.6617 20.0904 19.5004 19.2797 19.5004 18.461C19.5004 17.6424 19.6617 16.8317 19.975 16.0754C20.2883 15.319 20.7476 14.6318 21.3265 14.0529L27.053 8.32645C27.6318 7.74752 28.3191 7.28827 29.0754 6.97495C29.8318 6.66163 30.6424 6.50037 31.4611 6.50037C32.2798 6.50037 33.0904 6.66163 33.8468 6.97495C34.6031 7.28827 35.2903 7.74752 35.8692 8.32645Z" stroke="var(--spirit-shop-category-icon-color)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };
        return icons[catName] || '<svg width="52" height="52" viewBox="0 0 52 52" fill="none"><path d="M38.0748 39.8818L44.2152 33.7415C44.6227 33.3341 44.9459 32.8504 45.1664 32.318C45.387 31.7857 45.5005 31.2151 45.5005 30.6388C45.5005 30.0626 45.387 29.492 45.1664 28.9597C44.9459 28.4273 44.6227 27.9436 44.2152 27.5362L28.795 12.1182M13 19.5H12.9783M17.0278 13H10.8875C9.72386 13 8.60788 13.4623 7.78507 14.2851C6.96225 15.1079 6.5 16.2239 6.5 17.3875V23.5278C6.5 24.6913 6.9615 25.8072 7.78483 26.6305L21.0362 39.8818C21.4436 40.2893 21.9273 40.6126 22.4597 40.8331C22.992 41.0536 23.5626 41.1671 24.1388 41.1671C24.7151 41.1671 25.2857 41.0536 25.818 40.8331C26.3504 40.6126 26.8341 40.2893 27.2415 39.8818L33.3818 33.7415C33.7893 33.3341 34.1126 32.8504 34.3331 32.318C34.5536 31.7857 34.6671 31.2151 34.6671 30.6388C34.6671 30.0626 34.5536 29.492 34.3331 28.9597C34.1126 28.4273 33.7893 27.9436 33.3818 27.5362L20.1283 14.2848C19.306 13.4626 18.1908 13.0004 17.0278 13Z" stroke="var(--spirit-shop-category-icon-color)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    function buildCategories(products) {
        var counts = {};
        products.forEach(function (p) {
            var group = p.group_name || 'Другие';
            counts[group] = (counts[group] || 0) + 1;
        });

        var html = '';
        html += '<li class="category spirit-shop-category all-products active filter-button" data-filter="*">';
        html += '<svg width="52" height="52" viewBox="0 0 52 52" fill="none"><path d="M19.5 6.5H8.66667C7.47005 6.5 6.5 7.47005 6.5 8.66667V19.5C6.5 20.6966 7.47005 21.6667 8.66667 21.6667H19.5C20.6966 21.6667 21.6667 20.6966 21.6667 19.5V8.66667C21.6667 7.47005 20.6966 6.5 19.5 6.5Z" stroke="var(--color-default)" stroke-width="3" stroke-linejoin="round"/><path d="M19.5 30.3333H8.66667C7.47005 30.3333 6.5 31.3034 6.5 32.5V43.3333C6.5 44.5299 7.47005 45.5 8.66667 45.5H19.5C20.6966 45.5 21.6667 44.5299 21.6667 43.3333V32.5C21.6667 31.3034 20.6966 30.3333 19.5 30.3333Z" stroke="var(--color-default)" stroke-width="3" stroke-linejoin="round"/><path d="M37.9167 21.6667C38.9125 21.6667 39.8986 21.4705 40.8187 21.0894C41.7387 20.7083 42.5747 20.1497 43.2789 19.4456C43.9831 18.7414 44.5417 17.9054 44.9227 16.9853C45.3038 16.0653 45.5 15.0792 45.5 14.0833C45.5 13.0875 45.3038 12.1014 44.9227 11.1813C44.5417 10.2613 43.9831 9.42528 43.2789 8.72111C42.5747 8.01693 41.7387 7.45834 40.8187 7.07725C39.8986 6.69615 38.9125 6.5 37.9167 6.5C35.9054 6.5 33.9766 7.29896 32.5544 8.72111C31.1323 10.1433 30.3333 12.0721 30.3333 14.0833C30.3333 16.0946 31.1323 18.0234 32.5544 19.4456C33.9766 20.8677 35.9054 21.6667 37.9167 21.6667Z" stroke="var(--color-default)" stroke-width="3" stroke-linejoin="round"/><path d="M43.3333 30.3333H32.5C31.3034 30.3333 30.3333 31.3034 30.3333 32.5V43.3333C30.3333 44.5299 31.3034 45.5 32.5 45.5H43.3333C44.5299 45.5 45.5 44.5299 45.5 43.3333V32.5C45.5 31.3034 44.5299 30.3333 43.3333 30.3333Z" stroke="var(--color-default)" stroke-width="3" stroke-linejoin="round"/></svg>';
        html += '<span><p class="category-name">Все товары</p><p class="category-products-count">' + products.length + ' товаров</p></span></li>';

        Object.keys(counts).forEach(function (name) {
            var safeName = name.replace(/\s+/g, '_');
            var cnt = counts[name] || 0;
            if (cnt === 0) return;
            html += '<li class="spirit-shop-category groups filter-button" data-filter=".' + safeName + '">';
            html += getCategoryIcon(name);
            html += '<span><p class="category-name">' + name + '</p><p class="category-products-count">' + cnt + ' товаров</p></span></li>';
        });

        $('#categoriesList').html(html);
    }

    function renderProducts(products) {
        var html = '';
        products.forEach(function (p) {
            var isCase = p.type === 'case';
            var group = (p.group_name || 'Другие').replace(/\s+/g, '_');
            var discount = p.old_price ? Math.round((1 - p.price / p.old_price) * 100) : 0;
            var hasSummerBadge = p.name.includes('Премиум') || p.name.includes('Префикс') || p.name.includes('Разбан');

            html += '<div class="col-lg-3 col-md-4 col-6 filter-item ' + group + '">';
            html += '<div class="card product-card h-100">';
            html += '<div class="shop-product-payload">';
            if (discount > 0) {
                html += '<div class="badge badge-accent-blured">-' + discount + '%</div>';
            }
            if (hasSummerBadge) {
                html += '<span class="badge badge-accent"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18.3334C13.4312 18.3334 16.25 15.6142 16.25 12.1242C16.25 11.2675 16.2063 10.3492 15.7313 8.92169C15.2563 7.49419 15.1608 7.31002 14.6587 6.42835C14.4442 8.2271 13.2962 8.9771 13.0046 9.20127C13.0046 8.96794 12.3104 6.39002 11.2575 4.8471C10.2237 3.33335 8.81792 2.34044 7.99375 1.66669C7.99375 2.94585 7.63417 4.84752 7.11875 5.81669C6.60375 6.78544 6.50708 6.82085 5.86333 7.54169C5.22 8.26252 4.92458 8.48544 4.38625 9.36044C3.84833 10.2354 3.75 11.4009 3.75 12.2575C3.75 15.7475 6.56875 18.3334 10 18.3334Z" stroke="white" stroke-width="2" stroke-linejoin="round"/></svg>Летние скидки</span>';
            }
            html += '</div>';
            html += '<div class="product-preview-image-wrapper">';
            html += '<img class="product-preview-image" src="' + (p.image || 'assets/img/product_default.png') + '" alt="' + p.name + '" loading="lazy">';
            html += '</div>';
            html += '<div class="card-body">';
            html += '<h4 class="shop-product-name text-truncate">' + p.name + '</h4>';
            html += '<h3 class="shop-product-cost mb-0">' + p.price + ' ₽</h3>';
            if (p.old_price) {
                html += '<div class="shop-product-sale"><s>' + p.old_price + ' ₽</s><span>-' + discount + '%</span></div>';
            }
            html += '</div>';
            html += '<div class="card-footer">';
            if (isCase) {
                var safeName = p.name.replace(/'/g, "\\'");
                html += '<button class="btn btn-outline-accent rounded-pill btn-lg w-100" onclick="addToCart(' + p.id + ', \'' + safeName + '\', ' + p.price + ', \'' + (p.image || '') + '\', true)">';
                html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 19C4 19.5304 4.21071 20.0391 4.58579 20.4142C4.96086 20.7893 5.46957 21 6 21C6.53043 21 7.03914 20.7893 7.41421 20.4142C7.78929 20.0391 8 19.5304 8 19C8 18.4696 7.78929 17.9609 7.41421 17.5858C7.03914 17.2107 6.53043 17 6 17C5.46957 17 4.96086 17.2107 4.58579 17.5858C4.21071 17.9609 4 18.4696 4 19ZM15 19C15 19.5304 15.2107 20.0391 15.5858 20.4142C15.9609 20.7893 16.4696 21 17 21C17.5304 21 18.0391 20.7893 18.4142 20.4142C18.7893 20.0391 19 19.5304 19 19C19 18.4696 18.7893 17.9609 18.4142 17.5858C18.0391 17.2107 17.5304 17 17 17C16.4696 17 15.9609 17.2107 15.5858 17.5858C15.2107 17.9609 15 18.4696 15 19Z" stroke="var(--color-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 17H6V3H4" stroke="var(--color-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.00012 5L12.0001 5.429M19.1381 12.002L18.9951 13.002H5.99512M15.0001 6H21.0001M18.0001 3V9" stroke="var(--color-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '<span class="flex-shrink-0">Купить кейс</span></button>';
            } else {
                html += '<button class="btn btn-outline-accent rounded-pill btn-lg w-100" onclick="addToCart(' + p.id + ', \'' + p.name.replace(/'/g, "\\'") + '\', ' + p.price + ', \'' + (p.image || '') + '\', false)">';
                html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 19C4 19.5304 4.21071 20.0391 4.58579 20.4142C4.96086 20.7893 5.46957 21 6 21C6.53043 21 7.03914 20.7893 7.41421 20.4142C7.78929 20.0391 8 19.5304 8 19C8 18.4696 7.78929 17.9609 7.41421 17.5858C7.03914 17.2107 6.53043 17 6 17C5.46957 17 4.96086 17.2107 4.58579 17.5858C4.21071 17.9609 4 18.4696 4 19ZM15 19C15 19.5304 15.2107 20.0391 15.5858 20.4142C15.9609 20.7893 16.4696 21 17 21C17.5304 21 18.0391 20.7893 18.4142 20.4142C18.7893 20.0391 19 19.5304 19 19C19 18.4696 18.7893 17.9609 18.4142 17.5858C18.0391 17.2107 17.5304 17 17 17C16.4696 17 15.9609 17.2107 15.5858 17.5858C15.2107 17.9609 15 18.4696 15 19Z" stroke="var(--color-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 17H6V3H4" stroke="var(--color-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.00012 5L12.0001 5.429M19.1381 12.002L18.9951 13.002H5.99512M15.0001 6H21.0001M18.0001 3V9" stroke="var(--color-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '<span class="flex-shrink-0">В корзину</span></button>';
            }
            html += '</div></div></div>';
        });

        $('#productsGrid').html(html);

        if (isotopeInstance) {
            isotopeInstance.destroy();
        }
        isotopeInstance = new Isotope('.filter-items', {
            transitionDuration: 300,
            layoutMode: 'fitRows',
            fitRows: { equalheight: true }
        });
    }

    async function loadProducts() {
        try {
            var prodRes = await fetch('api.php');
            var prodData = await prodRes.json();
            if (prodData.success) {
                allProducts = prodData.response;
                buildCategories(allProducts);
                renderProducts(allProducts);
                setupFilter();
            }
        } catch (e) {
            console.error('loadProducts failed:', e);
            $('#productsGrid').html('<div class="col-12 text-center"><p>Ошибка загрузки товаров</p></div>');
        }
    }

    function setupFilter() {
        $('#categoriesList').off('click', '.filter-button').on('click', '.filter-button', function () {
            var filterValue = $(this).data('filter') || '*';
            if (filterValue === undefined) filterValue = '*';
            isotopeInstance.arrange({ filter: filterValue });
            $('#categoriesList .filter-button').removeClass('active');
            $(this).addClass('active');
        });
    }

    loadProducts();

    // ─── Buy Modal (for cases + cart) ─────────────────────────
    var selectedProductId = null;
    var selectedProductIsCase = false;
    var selectedProductName = '';
    var selectedQuantity = 1;
    var selectedCartItems = [];

    window.openBuyModal = function (id, name, price, isCase) {
        selectedProductId = id;
        selectedProductIsCase = isCase;
        selectedProductName = name;
        selectedQuantity = 1;
        selectedCartItems = isCase ? [{ id: id, quantity: 1 }] : [];
        $('#modalTitle').text(name);
        $('#modalPrice').text(price + ' ₽');
        $('#promoCode').val('');
        $('#buyModal').modal('show');
        if (isCase) {
            $('#quantityRow').show();
            $('.qty-btn').removeClass('active');
            $('.qty-btn[data-qty="1"]').addClass('active');
        } else {
            $('#quantityRow').hide();
        }
    };

    window.closeModal = function () {
        $('#buyModal').modal('hide');
    };

    window.setQuantity = function (qty) {
        selectedQuantity = qty;
        $('.qty-btn').removeClass('active');
        $('.qty-btn[data-qty="' + qty + '"]').addClass('active');
    };

    window.processPurchase = function (payType, paySystem) {
        var nick = $('#nickname').val().trim();
        var email = $('#emailInput').val().trim();
        var promo = $('#promoCode').val().trim();

        if (!nick || !email) {
            showToast('Ошибка', 'Пожалуйста, укажите ник и email', 'error');
            return;
        }

        if (selectedProductIsCase) {
            var url = 'api.php?action=create_case_payment'
                + '&customer=' + encodeURIComponent(nick)
                + '&email=' + encodeURIComponent(email)
                + '&case_id=' + selectedProductId
                + '&quantity=' + selectedQuantity
                + '&payment_type=' + payType
                + '&payment_method=' + paySystem;
            if (promo) url += '&promo=' + encodeURIComponent(promo);

            fetch(url)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.response && data.response.url) {
                        window.location.href = data.response.url;
                    } else {
                        showToast('Ошибка', data.response || 'Не удалось создать платёж', 'error');
                    }
                })
                .catch(function () {
                    showToast('Ошибка', 'Соединение с сервером прервано', 'error');
                });
        } else {
            var products = {};
            (selectedCartItems || []).forEach(function (item) {
                products[item.id] = item.quantity || 1;
            });

            if (Object.keys(products).length === 0) {
                showToast('Корзина пуста', 'Добавьте товары в корзину', 'error');
                return;
            }

            fetch('api.php?action=create_payment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer: nick,
                    email: email,
                    products: products,
                    coupon: promo || null,
                    payment_type: payType,
                    payment_method: paySystem,
                    server_id: window.selectedServerId || null
                })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.response) {
                        if (data.response.test_mode && !data.response.rcon_all_ok) {
                            var details = (data.response.rcon_results || []).join('\n');
                            showToast('Ошибка выдачи', details, 'error');
                            return;
                        }
                        if (data.response.url) {
                            window.location.href = data.response.url;
                        } else {
                            showToast('Ошибка', data.response, 'error');
                        }
                    } else {
                        showToast('Ошибка', data.response || 'Не удалось создать платёж', 'error');
                    }
                })
                .catch(function () {
                    showToast('Ошибка', 'Соединение с сервером прервано', 'error');
                });
        }
    };

    // ─── Toast notifications ──────────────────────────────────
    function showToast(title, message, type) {
        var html = '<div class="toast-notification toast-' + (type || 'info') + '">'
                 + '<strong>' + title + '</strong>'
                 + (message ? '<p>' + message + '</p>' : '')
                 + '</div>';
        var $toast = $(html);
        $('body').append($toast);
        setTimeout(function () { $toast.addClass('show'); }, 10);
        setTimeout(function () {
            $toast.removeClass('show');
            setTimeout(function () { $toast.remove(); }, 300);
        }, 4000);
    }

    // ─── Pay modal ────────────────────────────────────────────
    $('#buyModal').on('show.bs.modal', function () { $('body').addClass('modal-open'); $('.modal-backdrop').addClass('show'); });
    $('#buyModal').on('hidden.bs.modal', function () { $('body').removeClass('modal-open'); $('.modal-backdrop').removeClass('show'); });

    // ─── Handle URL params ────────────────────────────────────
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        showToast('Оплата успешна', 'Товар придет в течение пары минут.', 'success');
    }
});
