(function ($) {
    'use strict';

    /* ── State ───────────────────────────────────────── */
    var page            = 1;
    var search          = '';
    var currentCategory = 0;
    var hasMore         = true;
    var isLoading       = false;
    var quoteItems      = [];
    var shippingData    = null;

    /* ── Utilities ───────────────────────────────────── */
    function fmtPrice(n) {
        var v = parseFloat(n || 0).toFixed(2);
        var parts = v.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return VynkQG.currency + parts.join('.');
    }

    function esc(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function stripEmoji(str) {
        return String(str || '')
            .replace(/[\u{1F300}-\u{1FFFF}]/gu, '')
            .replace(/[✈🚢]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function subtotal() {
        return quoteItems.reduce(function (s, i) { return s + i.price * i.quantity; }, 0);
    }

    function genRef() {
        return 'COT-' + Date.now().toString(36).toUpperCase().slice(-8);
    }

    /* ── Product Loading ─────────────────────────────── */
    function loadProducts(reset) {
        if (isLoading) return;
        if (!hasMore && !reset) return;

        if (reset) {
            page    = 1;
            hasMore = true;
            $('#vynk-products-grid').empty();
            $('#vynk-products-list-wrap').hide();
            $('#vynk-no-results').hide();
            $('#vynk-load-more-wrap').hide();
        }

        isLoading = true;
        $('#vynk-loading').show();

        $.post(VynkQG.ajaxUrl, {
            action:   'vynk_qg_products',
            nonce:    VynkQG.nonce,
            search:   search,
            page:     page,
            category: currentCategory,
        }, function (res) {
            isLoading = false;
            $('#vynk-loading').hide();

            if (!res.success) return;

            var products = res.data.products;
            hasMore = res.data.has_more;

            if (products.length === 0 && page === 1) {
                $('#vynk-no-results').show();
                return;
            }

            $('#vynk-products-list-wrap').css('display', 'flex');
            $.each(products, function (_, p) {
                $('#vynk-products-grid').append(buildCard(p));
            });

            if (hasMore) {
                page++;
                $('#vynk-load-more-wrap').show();
            }
        }).fail(function () {
            isLoading = false;
            $('#vynk-loading').hide();
        });
    }

    function buildCard(p) {
        var inStock = p.stock_status === 'instock';

        return [
            '<div class="vynk-product-row">',
                '<div class="vynk-col-thumb">',
                    '<img class="vynk-row-thumb" src="' + esc(p.image) + '" alt="' + esc(p.name) + '" loading="lazy" />',
                '</div>',
                '<div class="vynk-col-name">',
                    '<p class="vynk-row-name">' + esc(p.name) + '</p>',
                '</div>',
                '<div class="vynk-col-sku">',
                    '<span class="vynk-row-sku">' + esc(p.sku || '—') + '</span>',
                '</div>',
                '<div class="vynk-col-stock">',
                    inStock
                        ? '<span class="vynk-stock-in">En stock</span>'
                        : '<span class="vynk-stock-out">Sin stock</span>',
                '</div>',
                '<div class="vynk-col-price">',
                    p.regular_price > p.price ? '<span class="vynk-price-old">' + fmtPrice(p.regular_price) + '</span>' : '',
                    '<span class="vynk-price">' + fmtPrice(p.price) + '</span>',
                '</div>',
                '<div class="vynk-col-action">',
                    '<div class="vynk-row-actions">',
                        '<button class="vynk-add-btn vynk-row-add-btn"',
                            ' data-id="'    + p.id           + '"',
                            ' data-name="'  + esc(p.name)    + '"',
                            ' data-sku="'   + esc(p.sku)     + '"',
                            ' data-price="' + p.price        + '"',
                            ' data-image="' + esc(p.image)   + '"',
                            inStock ? '' : ' disabled',
                        '>',
                            inStock ? '+ Agregar' : 'Sin stock',
                        '</button>',
                        p.slug
                            ? '<a class="vynk-view-btn" href="' + VynkQG.siteUrl + '/producto/' + esc(p.slug) + '" target="_blank" title="Ver producto">' +
                                  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>' +
                              '</a>'
                            : '',
                    '</div>',
                '</div>',
            '</div>',
        ].join('');
    }

    /* ── Quote Management ────────────────────────────── */
    function addToQuote(id, name, sku, price, image) {
        var existing = null;
        $.each(quoteItems, function (_, item) {
            if (item.id === id) { existing = item; return false; }
        });

        if (existing) {
            existing.quantity++;
        } else {
            quoteItems.push({ id: id, name: name, sku: sku, price: parseFloat(price), image: image, quantity: 1 });
        }

        shippingData = null;
        renderQuote();
        scheduleShipping();
    }

    function removeFromQuote(id) {
        quoteItems = quoteItems.filter(function (i) { return i.id !== id; });
        shippingData = null;
        renderQuote();
        scheduleShipping();
    }

    function updateQty(id, delta) {
        $.each(quoteItems, function (_, item) {
            if (item.id === id) {
                item.quantity = Math.max(1, item.quantity + delta);
                return false;
            }
        });
        shippingData = null;
        renderQuote();
        scheduleShipping();
    }

    /* Auto-calculate shipping with debounce */
    var shippingTimer;
    function scheduleShipping() {
        clearTimeout(shippingTimer);
        if (quoteItems.length === 0) return;
        shippingTimer = setTimeout(function () { calcShipping(); }, 700);
    }

    function renderQuote() {
        if (quoteItems.length === 0) {
            $('#vynk-quote-empty').show();
            $('#vynk-quote-items').hide().empty();
            $('#vynk-quote-summary').hide();
            return;
        }

        $('#vynk-quote-empty').hide();
        $('#vynk-quote-items').show();
        $('#vynk-quote-summary').show();

        var html = '';
        $.each(quoteItems, function (_, item) {
            html += [
                '<div class="vynk-quote-item" data-id="' + item.id + '">',
                    '<img class="vynk-quote-item-img" src="' + esc(item.image) + '" alt="' + esc(item.name) + '" />',
                    '<div class="vynk-quote-item-info">',
                        '<p class="vynk-quote-item-name">' + esc(item.name) + '</p>',
                        item.sku ? '<p class="vynk-quote-item-sku">SKU: ' + esc(item.sku) + '</p>' : '',
                        '<p class="vynk-quote-item-price">' + fmtPrice(item.price * item.quantity) + '</p>',
                    '</div>',
                    '<div class="vynk-item-controls">',
                        '<button class="vynk-qty-btn vynk-qty-minus" data-id="' + item.id + '">−</button>',
                        '<span class="vynk-qty-val">' + item.quantity + '</span>',
                        '<button class="vynk-qty-btn vynk-qty-plus" data-id="' + item.id + '">+</button>',
                        '<button class="vynk-remove-btn" data-id="' + item.id + '">',
                            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">',
                                '<polyline points="3 6 5 6 21 6"/>',
                                '<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
                                '<path d="M10 11v6M14 11v6"/>',
                            '</svg>',
                        '</button>',
                    '</div>',
                '</div>',
            ].join('');
        });

        $('#vynk-quote-items').html(html);
        $('#vynk-subtotal').text(fmtPrice(subtotal()));

        if (!shippingData) {
            $('#vynk-shipping-results').hide();
        }
    }

    /* ── Shipping ────────────────────────────────────── */
    function calcShipping() {
        if (quoteItems.length === 0) return;

        $('#vynk-shipping-results').hide();
        $('#vynk-shipping-status').show();

        var items = quoteItems.map(function (i) {
            return { id: i.id, quantity: i.quantity };
        });

        $.post(VynkQG.ajaxUrl, {
            action: 'vynk_qg_shipping',
            nonce:  VynkQG.nonce,
            items:  JSON.stringify(items),
        }, function (res) {
            $('#vynk-shipping-status').hide();
            if (!res.success) return;
            shippingData = res.data;
            renderShipping();
        }).fail(function () {
            $('#vynk-shipping-status').hide();
        });
    }

    function renderShipping() {
        if (!shippingData) return;

        var sub   = subtotal();
        var rates = (shippingData.interior && shippingData.interior.rates) ? shippingData.interior.rates : [];
        var $list = $('#vynk-rates-list');

        $list.empty();

        if (rates.length === 0) {
            $list.html('<p style="font-size:11px;color:#bbb;margin:4px 0;">No disponible</p>');
        } else {
            $.each(rates, function (_, rate) {
                var isAereo = rate.id && rate.id.indexOf('aereo') !== -1;
                var label   = isAereo ? 'Envío Aéreo' : 'Envío Marítimo';
                $list.append(
                    '<div class="vynk-simple-rate">' +
                        '<span class="vynk-simple-rate-name">' + label + '</span>' +
                        '<span class="vynk-simple-rate-right">' +
                            '<span class="vynk-simple-rate-cost">' + fmtPrice(rate.price) + '</span>' +
                            '<span class="vynk-simple-rate-total">Total: ' + fmtPrice(sub + rate.price) + '</span>' +
                        '</span>' +
                    '</div>'
                );
            });
        }

        $('#vynk-shipping-results').show();
    }

    /* ── PDF Generation ──────────────────────────────── */
    function imgToBase64(url, asPng) {
        return new Promise(function (resolve) {
            if (!url) { resolve(null); return; }
            var img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function () {
                try {
                    var c = document.createElement('canvas');
                    c.width  = img.naturalWidth;
                    c.height = img.naturalHeight;
                    c.getContext('2d').drawImage(img, 0, 0);
                    var fmt  = asPng ? 'image/png' : 'image/jpeg';
                    var data = c.toDataURL(fmt, asPng ? 1 : 0.85);
                    resolve({ data: data, w: img.naturalWidth, h: img.naturalHeight, fmt: asPng ? 'PNG' : 'JPEG' });
                } catch (e) { resolve(null); }
            };
            img.onerror = function () { resolve(null); };
            img.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 'nc=' + Date.now();
        });
    }

    function calcShippingPromise() {
        return new Promise(function (resolve) {
            if (shippingData) { resolve(); return; }
            if (quoteItems.length === 0) { resolve(); return; }

            var items = quoteItems.map(function (i) {
                return { id: i.id, quantity: i.quantity };
            });

            $.post(VynkQG.ajaxUrl, {
                action: 'vynk_qg_shipping',
                nonce:  VynkQG.nonce,
                items:  JSON.stringify(items),
            }, function (res) {
                if (res.success) {
                    shippingData = res.data;
                    renderShipping();
                }
                resolve();
            }).fail(function () { resolve(); });
        });
    }

    function drawLinearGradientH(doc, x, y, w, h, c1, c2, c3) {
        var steps = 40;
        var sw = w / steps;
        for (var s = 0; s < steps; s++) {
            var t = s / (steps - 1);
            var tt, r, g, b;
            if (t <= 0.5) {
                tt = t * 2;
                r = Math.round(c1[0] + (c2[0] - c1[0]) * tt);
                g = Math.round(c1[1] + (c2[1] - c1[1]) * tt);
                b = Math.round(c1[2] + (c2[2] - c1[2]) * tt);
            } else {
                tt = (t - 0.5) * 2;
                r = Math.round(c2[0] + (c3[0] - c2[0]) * tt);
                g = Math.round(c2[1] + (c3[1] - c2[1]) * tt);
                b = Math.round(c2[2] + (c3[2] - c2[2]) * tt);
            }
            doc.setFillColor(r, g, b);
            doc.rect(x + s * sw, y, sw + 0.5, h, 'F');
        }
    }

    async function loadBarlowFont(doc) {
        var fonts = [
            { weight: 400, style: 'normal' },
            { weight: 700, style: 'bold'   },
        ];
        var loaded = 0;
        try {
            for (var i = 0; i < fonts.length; i++) {
                var f   = fonts[i];
                var res = await $.ajax({
                    url:  VynkQG.ajaxUrl,
                    type: 'POST',
                    data: { action: 'vynk_qg_font', nonce: VynkQG.nonce, weight: f.weight, ital: 0 },
                });
                if (res.success && res.data) {
                    var fname = 'Barlow-' + f.weight + '.ttf';
                    doc.addFileToVFS(fname, res.data);
                    doc.addFont(fname, 'Barlow', f.style);
                    loaded++;
                }
            }
        } catch (e) {}
        return loaded === fonts.length;
    }

    async function generatePDF() {
        var $btn = $('#vynk-gen-pdf');
        $btn.prop('disabled', true).text('Generando PDF…');

        try {
            await calcShippingPromise();
            var jsPDF  = window.jspdf.jsPDF;
            var doc    = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });
            var hasBarlowFont = await loadBarlowFont(doc);
            var F      = hasBarlowFont ? 'Barlow' : 'helvetica';
            var W      = 210;
            var margin = 14;
            var cW     = W - margin * 2;

            /* Palette */
            var RED      = [33, 5, 5];
            var RED_DARK = [18, 2, 2];
            var WHITE    = [255, 255, 255];
            var CHARCOAL = [34, 34, 34];
            var DARK     = [51, 51, 51];
            var GRAY     = [120, 120, 128];
            var LGRAY    = [229, 231, 235];
            var BGLIGHT  = [248, 248, 248];
            var BGRED    = [240, 240, 240];

            var ref   = genRef();
            var today = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            var sub   = subtotal();

            /* ── Convert all images first ── */
            var imagePromises = quoteItems.map(function (item) { return imgToBase64(item.image, false); });
            var logoPromise   = imgToBase64(VynkQG.logoUrl, true);
            var allResults    = await Promise.all(imagePromises.concat([logoPromise]));
            var images        = allResults.slice(0, quoteItems.length);
            var logoResult    = allResults[quoteItems.length];

            /* ─────────────────────────────────────────
               HEADER
            ───────────────────────────────────────── */
            var HEADER_H = 42;

            /* Hero gradient background */
            drawLinearGradientH(doc, 0, 0, W, HEADER_H, [15, 1, 1], [33, 5, 5], [17, 2, 2]);

            /* Diagonal accent stripe */
            doc.setFillColor.apply(doc, RED_DARK);
            doc.triangle(W - 60, 0, W, 0, W, HEADER_H, 'F');

            /* Logo imagen o fallback texto */
            if (logoResult && logoResult.data) {
                var maxLogoH = 20;
                var maxLogoW = 55;
                var ratio    = logoResult.w / logoResult.h;
                var lH       = maxLogoH;
                var lW       = Math.min(lH * ratio, maxLogoW);
                try {
                    doc.addImage(logoResult.data, logoResult.fmt, margin, (HEADER_H - lH) / 2, lW, lH);
                } catch (e) {
                    doc.setFont(F, 'bold');
                    doc.setFontSize(28);
                    doc.setTextColor.apply(doc, WHITE);
                    doc.text('VYNK', margin, 22);
                }
            } else {
                doc.setFont(F, 'bold');
                doc.setFontSize(28);
                doc.setTextColor.apply(doc, WHITE);
                doc.text('VYNK', margin, 22);
            }

            /* Title right */
            doc.setFont(F, 'bold');
            doc.setFontSize(15);
            doc.setTextColor.apply(doc, WHITE);
            doc.text('COTIZACIÓN', W - margin, 17, { align: 'right' });

            doc.setFont(F, 'normal');
            doc.setFontSize(8.5);
            doc.setTextColor(255, 200, 200);
            doc.text('Ref: ' + ref, W - margin, 25, { align: 'right' });
            doc.text('Fecha: ' + today, W - margin, 32, { align: 'right' });

            var y = HEADER_H + 9;

            /* ─────────────────────────────────────────
               PRODUCTS SECTION TITLE
            ───────────────────────────────────────── */
            doc.setFont(F, 'bold');
            doc.setFontSize(9);
            doc.setTextColor.apply(doc, RED);
            doc.text('PRODUCTOS', margin, y);

            y += 1.5;
            doc.setDrawColor.apply(doc, RED);
            doc.setLineWidth(0.6);
            doc.line(margin, y, W - margin, y);
            y += 4;

            /* Table column positions */
            var COL = {
                img:   margin,
                name:  margin + 22,
                sku:   margin + 95,
                qty:   margin + 122,
                unit:  margin + 141,
                total: W - margin,
            };

            /* Table header row */
            doc.setFillColor.apply(doc, BGLIGHT);
            doc.rect(margin, y, cW, 7, 'F');

            doc.setFont(F, 'bold');
            doc.setFontSize(7);
            doc.setTextColor.apply(doc, GRAY);
            doc.text('PRODUCTO / SKU', COL.name, y + 4.5);
            doc.text('CANT.', COL.qty + 5, y + 4.5, { align: 'center' });
            doc.text('P. UNIT.', COL.unit + 12, y + 4.5, { align: 'right' });
            doc.text('SUBTOTAL', COL.total, y + 4.5, { align: 'right' });

            y += 7;

            /* ─────────────────────────────────────────
               PRODUCT ROWS
            ───────────────────────────────────────── */
            var ROW_H = 23;

            for (var i = 0; i < quoteItems.length; i++) {
                var item = quoteItems[i];

                /* Page break */
                if (y + ROW_H > 268) {
                    doc.addPage();
                    y = 15;
                    /* Thin red top bar on continuation pages */
                    doc.setFillColor.apply(doc, RED);
                    doc.rect(0, 0, W, 4, 'F');
                    doc.setFont(F, 'bold');
                    doc.setFontSize(7);
                    doc.setTextColor.apply(doc, WHITE);
                    doc.text('VYNK  ·  ' + ref, margin, 2.8);
                    y = 10;
                }

                /* Alternating row */
                if (i % 2 === 0) {
                    doc.setFillColor(252, 252, 252);
                    doc.rect(margin, y, cW, ROW_H, 'F');
                }

                /* Product image */
                if (images[i]) {
                    try {
                        doc.addImage(images[i].data, images[i].fmt, COL.img, y + 1.5, 19, 19, undefined, 'FAST');
                    } catch (e) {}
                } else {
                    /* Placeholder box */
                    doc.setFillColor(240, 240, 240);
                    doc.rect(COL.img, y + 1.5, 19, 19, 'F');
                }

                /* Name */
                doc.setFont(F, 'bold');
                doc.setFontSize(8.5);
                doc.setTextColor.apply(doc, CHARCOAL);
                var nameLines = doc.splitTextToSize(item.name, 68);
                doc.text(nameLines.slice(0, 2), COL.name, y + 7.5);

                /* SKU */
                doc.setFont(F, 'normal');
                doc.setFontSize(7);
                doc.setTextColor.apply(doc, GRAY);
                doc.text(item.sku || '—', COL.name, y + 15);

                /* Quantity bubble */
                var qtyX = COL.qty + 5;
                doc.setFillColor.apply(doc, BGLIGHT);
                doc.roundedRect(qtyX - 5, y + 7, 10, 8, 2, 2, 'F');
                doc.setFont(F, 'bold');
                doc.setFontSize(8.5);
                doc.setTextColor.apply(doc, DARK);
                doc.text(String(item.quantity), qtyX, y + 12.5, { align: 'center' });

                /* Unit price */
                doc.setFont(F, 'normal');
                doc.setFontSize(8.5);
                doc.setTextColor.apply(doc, DARK);
                doc.text(fmtPrice(item.price), COL.unit + 12, y + 12.5, { align: 'right' });

                /* Row total */
                doc.setFont(F, 'bold');
                doc.setFontSize(9);
                doc.setTextColor.apply(doc, RED);
                doc.text(fmtPrice(item.price * item.quantity), COL.total, y + 12.5, { align: 'right' });

                /* Row separator */
                doc.setDrawColor.apply(doc, LGRAY);
                doc.setLineWidth(0.25);
                doc.line(margin, y + ROW_H, W - margin, y + ROW_H);

                y += ROW_H;
            }

            y += 6;

            /* ─────────────────────────────────────────
               SUBTOTAL BAR
            ───────────────────────────────────────── */
            doc.setFillColor(242, 242, 242);
            doc.rect(W - margin - 75, y, 75, 9, 'F');
            doc.setDrawColor.apply(doc, RED);
            doc.setLineWidth(0.4);
            doc.line(W - margin - 75, y, W - margin - 75, y + 9);

            doc.setFont(F, 'normal');
            doc.setFontSize(8.5);
            doc.setTextColor.apply(doc, GRAY);
            doc.text('SUBTOTAL', W - margin - 40, y + 6, { align: 'right' });

            doc.setFont(F, 'bold');
            doc.setFontSize(9);
            doc.setTextColor.apply(doc, CHARCOAL);
            doc.text(fmtPrice(sub), W - margin - 2, y + 6, { align: 'right' });

            y += 16;

            /* ─────────────────────────────────────────
               SHIPPING SECTION
            ───────────────────────────────────────── */
            if (y + 70 > 268) {
                doc.addPage();
                doc.setFillColor.apply(doc, RED);
                doc.rect(0, 0, W, 4, 'F');
                y = 12;
            }

            doc.setFont(F, 'bold');
            doc.setFontSize(9);
            doc.setTextColor.apply(doc, RED);
            doc.text('OPCIONES DE ENVÍO Y TOTALES', margin, y);

            y += 1.5;
            doc.setDrawColor.apply(doc, RED);
            doc.setLineWidth(0.6);
            doc.line(margin, y, W - margin, y);
            y += 6;

            if (shippingData) {
                var rates = (shippingData.interior && shippingData.interior.rates)
                    ? shippingData.interior.rates : [];

                if (rates.length > 0) {
                    var colW     = (cW - 6) / 2;
                    var cardH    = 50;
                    var startY   = y;

                    $.each(rates, function (idx, rate) {
                        var isAereo = rate.id && rate.id.indexOf('aereo') !== -1;
                        var label   = isAereo ? 'ENVÍO AÉREO' : 'ENVÍO MARÍTIMO';
                        var colX    = margin + idx * (colW + 6);

                        /* Shadow */
                        doc.setFillColor(220, 220, 220);
                        doc.roundedRect(colX + 1, startY + 1, colW, cardH, 3, 3, 'F');

                        /* Card */
                        doc.setFillColor.apply(doc, WHITE);
                        doc.setDrawColor.apply(doc, LGRAY);
                        doc.setLineWidth(0.25);
                        doc.roundedRect(colX, startY, colW, cardH, 3, 3, 'FD');

                        /* Header */
                        doc.setFillColor.apply(doc, RED);
                        doc.roundedRect(colX, startY, colW, 11, 3, 3, 'F');
                        doc.rect(colX, startY + 6, colW, 5, 'F');

                        doc.setFont(F, 'bold');
                        doc.setFontSize(8.5);
                        doc.setTextColor.apply(doc, WHITE);
                        doc.text(label, colX + colW / 2, startY + 7.5, { align: 'center' });

                        var rY = startY + 17;

                        /* Cost row */
                        doc.setFont(F, 'normal');
                        doc.setFontSize(7.5);
                        doc.setTextColor.apply(doc, GRAY);
                        doc.text('Costo de envío', colX + 4, rY);
                        doc.setFont(F, 'bold');
                        doc.setTextColor.apply(doc, DARK);
                        doc.text(fmtPrice(rate.price), colX + colW - 4, rY, { align: 'right' });

                        /* Estimated days */
                        if (rate.estimated_days) {
                            doc.setFont(F, 'normal');
                            doc.setFontSize(6.5);
                            doc.setTextColor.apply(doc, GRAY);
                            doc.text(rate.estimated_days, colX + 4, rY + 5);
                        }

                        /* Separator */
                        var sepY = startY + 30;
                        doc.setDrawColor.apply(doc, LGRAY);
                        doc.setLineWidth(0.3);
                        doc.line(colX + 4, sepY, colX + colW - 4, sepY);

                        /* Total */
                        doc.setFillColor(242, 242, 242);
                        doc.roundedRect(colX + 3, sepY + 2, colW - 6, 14, 2, 2, 'F');

                        doc.setFont(F, 'bold');
                        doc.setFontSize(7);
                        doc.setTextColor.apply(doc, GRAY);
                        doc.text('TOTAL', colX + 6, sepY + 9);

                        doc.setFont(F, 'bold');
                        doc.setFontSize(12);
                        doc.setTextColor.apply(doc, RED);
                        doc.text(fmtPrice(sub + rate.price), colX + colW - 5, sepY + 10.5, { align: 'right' });
                    });

                    y = startY + cardH + 12;
                }

            } else {
                /* No shipping calculated */
                doc.setFillColor.apply(doc, BGLIGHT);
                doc.roundedRect(margin, y, cW, 14, 3, 3, 'F');
                doc.setFont(F, 'normal');
                doc.setFontSize(8.5);
                doc.setTextColor.apply(doc, GRAY);
                doc.text('Los costos de envío se calcularán al momento de procesar el pedido.', W / 2, y + 9, { align: 'center' });
                y += 22;
            }

            /* ─────────────────────────────────────────
               VALIDITY NOTE
            ───────────────────────────────────────── */
            if (y + 14 < 270) {
                doc.setFillColor.apply(doc, BGRED);
                doc.setDrawColor(210, 210, 210);
                doc.setLineWidth(0.3);
                doc.roundedRect(margin, y, cW, 11, 2, 2, 'FD');
                doc.setFont(F, 'normal');
                doc.setFontSize(8);
                doc.setTextColor(80, 80, 80);
                doc.text(
                    'Esta cotización es válida por 30 días. Los precios están sujetos a disponibilidad de stock.',
                    W / 2, y + 7, { align: 'center' }
                );
            }

            /* ─────────────────────────────────────────
               FOOTER (all pages)
            ───────────────────────────────────────── */
            var totalPages = doc.internal.getNumberOfPages();
            for (var pg = 1; pg <= totalPages; pg++) {
                doc.setPage(pg);

                var footerY = 286;

                doc.setFillColor.apply(doc, CHARCOAL);
                doc.rect(0, footerY, W, 11, 'F');

                doc.setFillColor.apply(doc, RED);
                doc.rect(0, footerY, 3, 11, 'F');

                doc.setFont(F, 'bold');
                doc.setFontSize(8);
                doc.setTextColor.apply(doc, WHITE);
                doc.text('VYNK', margin + 2, footerY + 7);

                doc.setFont(F, 'normal');
                doc.setFontSize(7.5);
                doc.setTextColor(180, 180, 180);
                doc.text('vynk.us  ·  Miami, Florida  ·  info@vynk.us', margin + 14, footerY + 7);

                doc.setFont(F, 'normal');
                doc.setFontSize(7);
                doc.setTextColor(150, 150, 150);
                doc.text(ref + '   Pág. ' + pg + ' / ' + totalPages, W - margin, footerY + 7, { align: 'right' });
            }

            /* Save */
            doc.save('Cotizacion-Vynk-' + ref + '.pdf');

        } catch (err) {
            console.error('[VynkQG] PDF Error:', err);
            alert('Error al generar el PDF. Revisa la consola para más detalles.');
        }

        $btn.prop('disabled', false).html(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>' +
            '<polyline points="14 2 14 8 20 8"/>' +
            '<line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>' +
            '</svg> Generar PDF'
        );
    }

    /* ── Categories ─────────────────────────────────── */
    function loadCategories() {
        $.post(VynkQG.ajaxUrl, {
            action: 'vynk_qg_categories',
            nonce:  VynkQG.nonce,
        }, function (res) {
            if (!res.success || !res.data.length) return;

            var cats    = res.data;
            var byParent = {};
            $.each(cats, function (_, c) {
                if (!byParent[c.parent]) byParent[c.parent] = [];
                byParent[c.parent].push(c);
            });

            var $sel = $('#vynk-category');

            function appendChildren(parentId, depth) {
                var children = byParent[parentId] || [];
                $.each(children, function (_, c) {
                    var prefix = depth === 0 ? '' : Array(depth + 1).join('— ');
                    $sel.append(
                        $('<option>', { value: c.id, text: prefix + c.name })
                    );
                    appendChildren(c.id, depth + 1);
                });
            }

            appendChildren(0, 0);
        });
    }

    /* ── Dynamic Height ──────────────────────────────── */
    function setAppHeight() {
        var $app = $('#vynk-qg-app');
        if (!$app.length) return;
        var offsetTop  = $app.offset().top;
        var viewportH  = $(window).height();
        var available  = viewportH - offsetTop - 10;
        $app.css('height', Math.max(400, available) + 'px');
    }

    /* ── Event Bindings ──────────────────────────────── */
    $(function () {
        setAppHeight();
        $(window).on('resize', setAppHeight);
        loadCategories();
        loadProducts(true);

        /* Search with debounce */
        var searchTimer;
        $('#vynk-search').on('input', function () {
            clearTimeout(searchTimer);
            search = $(this).val();
            searchTimer = setTimeout(function () { loadProducts(true); }, 450);
        });

        /* Category filter */
        $('#vynk-category').on('change', function () {
            currentCategory = parseInt($(this).val(), 10) || 0;
            loadProducts(true);
        });

        /* Load more */
        $(document).on('click', '#vynk-load-more-btn', function () {
            loadProducts(false);
        });

        /* Add to quote */
        $(document).on('click', '.vynk-add-btn', function () {
            var $btn = $(this);
            addToQuote(
                parseInt($btn.data('id'), 10),
                $btn.data('name'),
                $btn.data('sku'),
                $btn.data('price'),
                $btn.data('image')
            );
            $btn.addClass('is-added');
            setTimeout(function () { $btn.removeClass('is-added'); }, 900);
        });

        /* Qty controls */
        $(document).on('click', '.vynk-qty-plus', function () {
            updateQty(parseInt($(this).data('id'), 10), 1);
        });
        $(document).on('click', '.vynk-qty-minus', function () {
            updateQty(parseInt($(this).data('id'), 10), -1);
        });
        $(document).on('click', '.vynk-remove-btn', function () {
            removeFromQuote(parseInt($(this).data('id'), 10));
        });

        /* Clear quote */
        $('#vynk-clear-quote').on('click', function () {
            if (quoteItems.length === 0) return;
            if (!window.confirm('¿Limpiar la cotización actual?')) return;
            quoteItems   = [];
            shippingData = null;
            renderQuote();
        });

        /* Generate PDF */
        $('#vynk-gen-pdf').on('click', function () {
            generatePDF();
        });
    });

})(jQuery);
