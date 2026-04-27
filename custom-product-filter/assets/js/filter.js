/**
 * Custom Product Filter — JavaScript
 * Maneja todo el filtrado AJAX, renderizado de cards y paginación
 */
(function ($) {
    'use strict';

    // =========================================================================
    // Estado global por instancia del widget
    // =========================================================================
    class ProductFilter {
        constructor(wrapper) {
            this.$wrapper  = $(wrapper);
            this.config    = JSON.parse(this.$wrapper.attr('data-cpf') || '{}');
            this.$sidebar  = this.$wrapper.find('.cpf-sidebar');
            this.$main     = this.$wrapper.find('.cpf-main');
            this.$grid     = this.$wrapper.find('.cpf-grid');
            this.$empty    = this.$wrapper.find('.cpf-empty');
            this.$count    = this.$wrapper.find('.cpf-results-count');
            this.$tags     = this.$wrapper.find('.cpf-active-tags');
            this.$pagination = this.$wrapper.find('.cpf-pagination');
            this.$resetBtn = this.$wrapper.find('.cpf-reset-btn');
            this.$sort     = this.$wrapper.find('.cpf-sort__select');
            this.$search   = this.$wrapper.find('.cpf-search__input');

            // Estado
            this.state = {
                page:        1,
                orderby:     'date_desc',
                search:      '',
                categories:  [],
                attributes:  {}, // { taxonomy: [slugs] }
                min_price:   0,
                max_price:   0,
                priceAbsMin: 0,
                priceAbsMax: 0,
            };

            this.searchTimer   = null;
            this.filtersLoaded = false;

            this.init();
        }

        init() {
            this.loadFilters().then(() => {
                this.filtersLoaded = true;
                this.loadProducts();
            });
            this.bindEvents();
        }

        // =====================================================================
        // Cargar opciones de filtros desde REST API
        // =====================================================================
        async loadFilters() {
            if ( ! this.config.show_sidebar ) return;

            try {
                const res  = await fetch(`${this.config.rest_url}/filters`, {
                    headers: { 'X-WP-Nonce': this.config.nonce }
                });
                const data = await res.json();

                this.state.priceAbsMin = data.price_range.min;
                this.state.priceAbsMax = data.price_range.max;
                this.state.min_price   = data.price_range.min;
                this.state.max_price   = data.price_range.max;

                this.renderFilters(data);
            } catch (e) {
                console.error('[CPF] Error cargando filtros:', e);
            }
        }

        // =====================================================================
        // Renderizar sidebar de filtros
        // =====================================================================
        renderFilters(data) {
            const $body = this.$wrapper.find('.cpf-sidebar__body');
            $body.empty();

            // Categorías
            if (data.categories && data.categories.length > 0) {
                $body.append(this.buildCheckboxGroup('Categorías', 'category', data.categories));
            }

            // Atributos
            if (data.attributes) {
                data.attributes.forEach(attr => {
                    $body.append(this.buildCheckboxGroup(attr.name, attr.taxonomy, attr.terms));
                });
            }

            // Precio
            if (this.config.show_price && data.price_range.max > 0) {
                $body.append(this.buildPriceFilter(data.price_range));
            }

            // Activar accordion
            this.initAccordions();
        }

        buildCheckboxGroup(title, taxonomy, terms) {
            const $group = $(`
                <div class="cpf-filter-group is-open" data-taxonomy="${this.escAttr(taxonomy)}">
                    <button class="cpf-filter-group__toggle" type="button" aria-expanded="true">
                        <span>${this.escHtml(title)}</span>
                        <svg class="cpf-filter-group__arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="cpf-filter-group__body">
                        <ul class="cpf-checkbox-list" role="group" aria-label="${this.escAttr(title)}"></ul>
                    </div>
                </div>
            `);

            const $list = $group.find('.cpf-checkbox-list');

            terms.forEach(term => {
                const id = `cpf-${taxonomy}-${term.slug}`;
                $list.append(`
                    <li>
                        <label class="cpf-checkbox-item" for="${this.escAttr(id)}">
                            <input type="checkbox"
                                   id="${this.escAttr(id)}"
                                   name="${this.escAttr(taxonomy)}"
                                   value="${this.escAttr(term.slug)}"
                                   aria-label="${this.escAttr(term.name)}">
                            <span class="cpf-checkbox__box" aria-hidden="true"></span>
                            <span class="cpf-checkbox__label">${this.escHtml(term.name)}</span>
                            <span class="cpf-checkbox__count">${term.count}</span>
                        </label>
                    </li>
                `);
            });

            return $group;
        }

        buildPriceFilter(range) {
            const min = Math.floor(range.min);
            const max = Math.ceil(range.max);
            const sym = (typeof CPF_CONFIG !== 'undefined') ? CPF_CONFIG.currency : '$';

            return $(`
                <div class="cpf-filter-group is-open" data-taxonomy="price">
                    <button class="cpf-filter-group__toggle" type="button" aria-expanded="true">
                        <span>Precio</span>
                        <svg class="cpf-filter-group__arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="cpf-filter-group__body">
                        <div class="cpf-price-range">
                            <div class="cpf-price-range__values">
                                <span class="cpf-price-min">${sym}${min}</span>
                                <span class="cpf-price-range__separator">–</span>
                                <span class="cpf-price-max">${sym}${max}</span>
                            </div>
                            <div class="cpf-price-range__track-wrap">
                                <div class="cpf-price-range__track-fill"></div>
                                <input type="range" class="cpf-range-min" min="${min}" max="${max}" value="${min}" step="1" aria-label="Precio mínimo">
                                <input type="range" class="cpf-range-max" min="${min}" max="${max}" value="${max}" step="1" aria-label="Precio máximo">
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }

        initAccordions() {
            this.$wrapper.find('.cpf-filter-group__toggle').on('click', function () {
                const $group = $(this).closest('.cpf-filter-group');
                const isOpen = $group.hasClass('is-open');
                $group.toggleClass('is-open', !isOpen);
                $(this).attr('aria-expanded', !isOpen);
            });
        }

        // =====================================================================
        // Cargar productos desde REST API
        // =====================================================================
        async loadProducts(showOverlay = false) {
            this.showLoading(showOverlay);

            const params = new URLSearchParams({
                page:     this.state.page,
                per_page: this.config.per_page || 12,
                orderby:  this.state.orderby,
            });

            if (this.state.search)    params.set('search', this.state.search);
            if (this.state.categories.length) params.set('category', this.state.categories.join(','));

            // Atributos
            const attrParts = Object.entries(this.state.attributes)
                .filter(([, vals]) => vals.length > 0)
                .map(([tax, vals]) => `${tax}:${vals.join(',')}`);
            if (attrParts.length) params.set('attributes', attrParts.join('|'));

            // Precio
            const hasPrice = this.state.min_price > this.state.priceAbsMin ||
                             this.state.max_price < this.state.priceAbsMax;
            if (hasPrice) {
                params.set('min_price', this.state.min_price);
                params.set('max_price', this.state.max_price);
            }

            try {
                const res  = await fetch(`${this.config.rest_url}/products?${params}`, {
                    headers: { 'X-WP-Nonce': this.config.nonce }
                });
                const data = await res.json();
                this.renderProducts(data);
            } catch (e) {
                console.error('[CPF] Error cargando productos:', e);
                this.hideLoading();
            }
        }

        // =====================================================================
        // Renderizar productos
        // =====================================================================
        renderProducts(data) {
            this.$grid.empty();
            this.hideLoading();

            if (!data.products || data.products.length === 0) {
                this.$empty.show();
                this.$count.html('');
                this.$pagination.empty();
                return;
            }

            this.$empty.hide();

            // Contador
            const showing = Math.min(data.current_page * this.config.per_page, data.total);
            const from    = ((data.current_page - 1) * this.config.per_page) + 1;
            this.$count.html(`Mostrando <strong>${from}–${showing}</strong> de <strong>${data.total}</strong>`);

            // Cards
            data.products.forEach((product, i) => {
                const $card = this.buildCard(product);
                $card.css('animation-delay', `${i * 0.04}s`);
                this.$grid.append($card);
            });

            // Paginación
            this.renderPagination(data.current_page, data.total_pages);

            // Scroll suave al inicio del widget
            if (data.current_page > 1) {
                $('html, body').animate({ scrollTop: this.$wrapper.offset().top - 80 }, 300);
            }
        }

        buildCard(p) {
            const sym = (typeof CPF_CONFIG !== 'undefined') ? CPF_CONFIG.currency : '$';

            // Badges
            let badges = '';
            if (!p.in_stock)    badges += `<span class="cpf-badge cpf-badge--out">Agotado</span>`;
            else if (p.on_sale) badges += `<span class="cpf-badge cpf-badge--sale">Oferta</span>`;

            // Precio
            let priceHtml = '';
            if (p.on_sale && p.regular_price) {
                priceHtml = `
                    <span class="cpf-card__price-current is-sale">${sym}${p.sale_price.toFixed(2)}</span>
                    <span class="cpf-card__price-regular">${sym}${p.regular_price.toFixed(2)}</span>
                `;
            } else {
                priceHtml = `<span class="cpf-card__price-current">${sym}${p.price.toFixed(2)}</span>`;
            }

            // Rating
            let ratingHtml = '';
            if (this.config.show_rating && p.review_count > 0) {
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    const filled = i <= Math.round(p.rating) ? 'is-filled' : '';
                    stars += `<svg class="cpf-star ${filled}" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>`;
                }
                ratingHtml = `
                    <div class="cpf-card__rating">
                        <div class="cpf-stars" aria-label="Rating: ${p.rating} de 5">${stars}</div>
                        <span class="cpf-card__review-count">(${p.review_count})</span>
                    </div>
                `;
            }

            // Categorías
            const cats = Array.isArray(p.categories) ? p.categories.join(', ') : '';

            const isVariable = p.product_type === 'variable' || p.product_type === 'grouped';
            const btnLabel   = isVariable ? 'Ver opciones' : 'Añadir al carrito';

            return $(`
                <div class="cpf-card" data-href="${this.escAttr(p.permalink)}" role="listitem" aria-label="${this.escAttr(p.name)}" tabindex="0">
                    <div class="cpf-card__image-wrap">
                        <img class="cpf-card__image"
                             src="${this.escAttr(p.image)}"
                             alt="${this.escAttr(p.name)}"
                             loading="lazy">
                        <div class="cpf-card__badges" aria-hidden="true">${badges}</div>
                    </div>
                    <div class="cpf-card__body">
                        ${cats ? `<span class="cpf-card__cats">${this.escHtml(cats)}</span>` : ''}
                        <h3 class="cpf-card__name">${this.escHtml(p.name)}</h3>
                        ${ratingHtml}
                        <div class="cpf-card__footer">
                            <div class="cpf-card__price">${priceHtml}</div>
                            <button class="cpf-card__btn"
                                    type="button"
                                    data-product-id="${p.id}"
                                    data-product-type="${this.escAttr(p.product_type)}"
                                    data-variable="${isVariable ? '1' : '0'}"
                                    data-permalink="${this.escAttr(p.permalink)}"
                                    aria-label="${this.escAttr(btnLabel)}"
                                    ${!p.in_stock ? 'disabled' : ''}>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `);
        }

        // =====================================================================
        // Paginación
        // =====================================================================
        renderPagination(current, total) {
            this.$pagination.empty();
            if (total <= 1) return;

            const pages = this.getPageRange(current, total);

            // Botón anterior
            const $prev = $(`
                <button class="cpf-pagination__btn" ${current === 1 ? 'disabled' : ''} aria-label="Página anterior">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
            `);
            $prev.on('click', () => { this.goToPage(current - 1); });
            this.$pagination.append($prev);

            // Páginas
            pages.forEach(p => {
                if (p === '...') {
                    this.$pagination.append(`<span class="cpf-pagination__dots">…</span>`);
                } else {
                    const $btn = $(`
                        <button class="cpf-pagination__btn ${p === current ? 'is-active' : ''}"
                                aria-label="Página ${p}"
                                ${p === current ? 'aria-current="page"' : ''}>
                            ${p}
                        </button>
                    `);
                    $btn.on('click', () => { this.goToPage(p); });
                    this.$pagination.append($btn);
                }
            });

            // Botón siguiente
            const $next = $(`
                <button class="cpf-pagination__btn" ${current === total ? 'disabled' : ''} aria-label="Página siguiente">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            `);
            $next.on('click', () => { this.goToPage(current + 1); });
            this.$pagination.append($next);
        }

        getPageRange(current, total) {
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            if (current <= 4) return [1, 2, 3, 4, 5, '...', total];
            if (current >= total - 3) return [1, '...', total-4, total-3, total-2, total-1, total];
            return [1, '...', current - 1, current, current + 1, '...', total];
        }

        goToPage(page) {
            this.state.page = page;
            this.loadProducts(true);
        }

        // =====================================================================
        // Tags de filtros activos
        // =====================================================================
        updateActiveTags() {
            this.$tags.empty();
            let hasFilters = false;

            // Categorías
            this.state.categories.forEach(slug => {
                const label = this.$wrapper.find(`input[name="category"][value="${slug}"]`).siblings('.cpf-checkbox__label').text();
                this.addTag(label || slug, () => { this.removeCategory(slug); });
                hasFilters = true;
            });

            // Atributos
            Object.entries(this.state.attributes).forEach(([tax, vals]) => {
                vals.forEach(slug => {
                    const label = this.$wrapper.find(`input[name="${tax}"][value="${slug}"]`).siblings('.cpf-checkbox__label').text();
                    this.addTag(label || slug, () => { this.removeAttribute(tax, slug); });
                    hasFilters = true;
                });
            });

            // Precio
            const hasPrice = this.state.min_price > this.state.priceAbsMin ||
                             this.state.max_price < this.state.priceAbsMax;
            if (hasPrice) {
                const sym = (typeof CPF_CONFIG !== 'undefined') ? CPF_CONFIG.currency : '$';
                const label = `${sym}${this.state.min_price} – ${sym}${this.state.max_price}`;
                this.addTag(label, () => { this.resetPrice(); });
                hasFilters = true;
            }

            // Búsqueda
            if (this.state.search) {
                this.addTag(`"${this.state.search}"`, () => {
                    this.state.search = '';
                    this.$search.val('');
                    this.triggerFilter();
                });
                hasFilters = true;
            }

            // Mostrar/ocultar reset general
            this.$resetBtn.toggle(hasFilters);
        }

        addTag(label, onRemove) {
            const $tag = $(`
                <span class="cpf-tag" role="button" tabindex="0" aria-label="Quitar filtro: ${this.escAttr(label)}">
                    ${this.escHtml(label)}
                    <span class="cpf-tag__remove" aria-hidden="true">✕</span>
                </span>
            `);
            $tag.on('click keydown', (e) => {
                if (e.type === 'click' || e.key === 'Enter' || e.key === ' ') {
                    onRemove();
                }
            });
            this.$tags.append($tag);
        }

        removeCategory(slug) {
            this.state.categories = this.state.categories.filter(s => s !== slug);
            this.$wrapper.find(`input[name="category"][value="${slug}"]`).prop('checked', false);
            this.triggerFilter();
        }

        removeAttribute(taxonomy, slug) {
            if (this.state.attributes[taxonomy]) {
                this.state.attributes[taxonomy] = this.state.attributes[taxonomy].filter(s => s !== slug);
                if (this.state.attributes[taxonomy].length === 0) {
                    delete this.state.attributes[taxonomy];
                }
            }
            this.$wrapper.find(`input[name="${taxonomy}"][value="${slug}"]`).prop('checked', false);
            this.triggerFilter();
        }

        resetPrice() {
            this.state.min_price = this.state.priceAbsMin;
            this.state.max_price = this.state.priceAbsMax;
            this.$wrapper.find('.cpf-range-min').val(this.state.priceAbsMin);
            this.$wrapper.find('.cpf-range-max').val(this.state.priceAbsMax);
            this.updatePriceTrack();
            this.triggerFilter();
        }

        // =====================================================================
        // Eventos
        // =====================================================================
        bindEvents() {
            const self = this;

            // Checkboxes de filtro
            this.$wrapper.on('change', '.cpf-sidebar input[type="checkbox"]', function () {
                const $input   = $(this);
                const taxonomy = $input.attr('name');
                const value    = $input.val();
                const checked  = $input.is(':checked');

                if (taxonomy === 'category') {
                    if (checked) {
                        if (!self.state.categories.includes(value)) self.state.categories.push(value);
                    } else {
                        self.state.categories = self.state.categories.filter(v => v !== value);
                    }
                } else {
                    if (!self.state.attributes[taxonomy]) self.state.attributes[taxonomy] = [];
                    if (checked) {
                        if (!self.state.attributes[taxonomy].includes(value)) self.state.attributes[taxonomy].push(value);
                    } else {
                        self.state.attributes[taxonomy] = self.state.attributes[taxonomy].filter(v => v !== value);
                        if (self.state.attributes[taxonomy].length === 0) delete self.state.attributes[taxonomy];
                    }
                }
                self.triggerFilter();
            });

            // Range de precio
            this.$wrapper.on('input', '.cpf-range-min, .cpf-range-max', function () {
                const $min = self.$wrapper.find('.cpf-range-min');
                const $max = self.$wrapper.find('.cpf-range-max');
                let minVal = parseInt($min.val());
                let maxVal = parseInt($max.val());

                if (minVal > maxVal - 1) {
                    if ($(this).hasClass('cpf-range-min')) {
                        minVal = maxVal - 1;
                        $min.val(minVal);
                    } else {
                        maxVal = minVal + 1;
                        $max.val(maxVal);
                    }
                }

                const sym = (typeof CPF_CONFIG !== 'undefined') ? CPF_CONFIG.currency : '$';
                self.$wrapper.find('.cpf-price-min').text(`${sym}${minVal}`);
                self.$wrapper.find('.cpf-price-max').text(`${sym}${maxVal}`);
                self.state.min_price = minVal;
                self.state.max_price = maxVal;
                self.updatePriceTrack();
            });

            this.$wrapper.on('change', '.cpf-range-min, .cpf-range-max', function () {
                self.triggerFilter();
            });

            // Búsqueda
            this.$search.on('input', function () {
                clearTimeout(self.searchTimer);
                self.searchTimer = setTimeout(() => {
                    self.state.search = $(this).val().trim();
                    self.triggerFilter();
                }, 420);
            });

            // Ordenamiento
            this.$sort.on('change', function () {
                self.state.orderby = $(this).val();
                self.state.page    = 1;
                self.loadProducts(true);
            });

            // Navegar al producto al hacer clic en la tarjeta (excepto en el botón)
            this.$wrapper.on('click', '.cpf-card', function (e) {
                if (!$(e.target).closest('.cpf-card__btn').length) {
                    window.location.href = $(this).data('href');
                }
            });

            // Teclado: Enter en la tarjeta navega al producto
            this.$wrapper.on('keydown', '.cpf-card', function (e) {
                if (e.key === 'Enter' && !$(e.target).closest('.cpf-card__btn').length) {
                    window.location.href = $(this).data('href');
                }
            });

            // Añadir al carrito
            this.$wrapper.on('click', '.cpf-card__btn', function (e) {
                e.stopPropagation();
                const $btn      = $(this);
                const productId = $btn.data('product-id');
                const isVar     = $btn.data('variable') === 1 || $btn.data('variable') === '1';
                if (isVar) {
                    window.location.href = $btn.data('permalink');
                } else {
                    self.addToCart($btn, productId);
                }
            });

            // Toggle sidebar en móvil
            this.$wrapper.find('.cpf-sidebar__header').on('click', function () {
                if (window.innerWidth <= 768) {
                    const $header = $(this);
                    const $body   = self.$wrapper.find('.cpf-sidebar__body');
                    $header.toggleClass('is-open');
                    $body.toggleClass('is-open');
                }
            });

            // Reset general
            this.$resetBtn.on('click', () => this.resetAll());

            // Delegado para el botón de empty
            this.$wrapper.on('click', '.cpf-empty__reset', () => this.resetAll());
        }

        async addToCart($btn, productId) {
            $btn.addClass('is-loading').prop('disabled', true);

            const cfg = typeof CPF_CONFIG !== 'undefined' ? CPF_CONFIG : {};

            try {
                const formData = new FormData();
                formData.append('action',     'cpf_add_to_cart');
                formData.append('product_id', productId);
                formData.append('quantity',   1);
                formData.append('nonce',      cfg.wc_nonce || '');

                const res  = await fetch(cfg.ajax_url || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body:   formData,
                });
                const data = await res.json();

                $btn.removeClass('is-loading').prop('disabled', false);

                if (data.success) {
                    // Trigger fragments para actualizar el mini-carrito del tema
                    $(document.body).trigger('wc_fragment_refresh');
                    $(document.body).trigger('added_to_cart', [{}, '', $btn]);

                    // Mostrar toast VYNK
                    CPFToast.show({
                        name:     data.data.product_name,
                        image:    data.data.product_image,
                        cartUrl:  data.data.cart_url || cfg.cart_url || '/carrito',
                    });
                } else {
                    // Producto variable u error: ir a la página del producto
                    const permalink = $btn.data('permalink');
                    if (permalink) window.location.href = permalink;
                }
            } catch (e) {
                $btn.removeClass('is-loading').prop('disabled', false);
                console.error('[CPF] Error al añadir al carrito:', e);
            }
        }

        triggerFilter() {
            this.state.page = 1;
            this.updateActiveTags();
            this.loadProducts(true);
        }

        resetAll() {
            this.state.categories = [];
            this.state.attributes = {};
            this.state.search     = '';
            this.state.page       = 1;
            this.state.min_price  = this.state.priceAbsMin;
            this.state.max_price  = this.state.priceAbsMax;

            // Desmarcar checkboxes
            this.$wrapper.find('.cpf-sidebar input[type="checkbox"]').prop('checked', false);

            // Resetear sliders
            this.$wrapper.find('.cpf-range-min').val(this.state.priceAbsMin);
            this.$wrapper.find('.cpf-range-max').val(this.state.priceAbsMax);
            this.updatePriceTrack();

            // Resetear texto de precio
            const sym = (typeof CPF_CONFIG !== 'undefined') ? CPF_CONFIG.currency : '$';
            this.$wrapper.find('.cpf-price-min').text(`${sym}${this.state.priceAbsMin}`);
            this.$wrapper.find('.cpf-price-max').text(`${sym}${this.state.priceAbsMax}`);

            // Resetear búsqueda
            this.$search.val('');

            this.updateActiveTags();
            this.loadProducts(true);
        }

        // =====================================================================
        // Helpers visuales
        // =====================================================================
        updatePriceTrack() {
            const $trackWrap = this.$wrapper.find('.cpf-price-range__track-wrap');
            if (!$trackWrap.length) return;

            const min    = this.state.priceAbsMin;
            const max    = this.state.priceAbsMax;
            const range  = max - min;
            if (range === 0) return;

            const leftPct  = ((this.state.min_price - min) / range) * 100;
            const rightPct = ((max - this.state.max_price) / range) * 100;

            $trackWrap.find('.cpf-price-range__track-fill').css({
                left:  leftPct + '%',
                right: rightPct + '%',
            });
        }

        showLoading(overlay = false) {
            if (overlay) {
                if (!this.$main.find('.cpf-loading-overlay').length) {
                    this.$main.append('<div class="cpf-loading-overlay"><div class="cpf-spinner"></div></div>');
                }
            } else {
                // Skeletons iniciales
                this.$grid.empty();
                for (let i = 0; i < (this.config.per_page || 12); i++) {
                    this.$grid.append(`
                        <div class="cpf-skeleton-card" aria-hidden="true">
                            <div class="cpf-skeleton cpf-skeleton--image"></div>
                            <div class="cpf-skeleton cpf-skeleton--title"></div>
                            <div class="cpf-skeleton cpf-skeleton--price"></div>
                        </div>
                    `);
                }
            }
        }

        hideLoading() {
            this.$main.find('.cpf-loading-overlay').remove();
        }

        escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        escAttr(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    }

    // =========================================================================
    // Toast de carrito — estilo VYNK (expuesto globalmente para wp_footer)
    // =========================================================================
    const CPFToast = window.CPFToast = {
        DURATION: 4500,

        getContainer() {
            let $c = $('#cpf-toast-container');
            if (!$c.length) {
                $c = $('<div id="cpf-toast-container" role="region" aria-label="Notificaciones del carrito"></div>');
                $('body').append($c);
            }
            return $c;
        },

        show({ name, image, cartUrl }) {
            const $container = this.getContainer();

            const $toast = $(`
                <div class="cpf-toast" role="alert" aria-live="polite">
                    <div class="cpf-toast__icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    ${image ? `<img class="cpf-toast__img" src="${this.escAttr(image)}" alt="" loading="lazy">` : ''}
                    <div class="cpf-toast__body">
                        <span class="cpf-toast__label">¡Añadido al carrito!</span>
                        <span class="cpf-toast__name" title="${this.escAttr(name)}">${this.escHtml(name)}</span>
                        <div class="cpf-toast__actions">
                            <a class="cpf-toast__cart-btn" href="${this.escAttr(cartUrl)}">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                </svg>
                                Ver carrito
                            </a>
                        </div>
                    </div>
                    <button class="cpf-toast__close" aria-label="Cerrar notificación">×</button>
                    <div class="cpf-toast__progress" style="--cpf-toast-duration: ${this.DURATION}ms"></div>
                </div>
            `);

            $container.prepend($toast);

            // Auto-dismiss
            const timer = setTimeout(() => this.dismiss($toast), this.DURATION);

            $toast.find('.cpf-toast__close').on('click', () => {
                clearTimeout(timer);
                this.dismiss($toast);
            });
        },

        dismiss($toast) {
            $toast.addClass('is-hiding');
            $toast.one('animationend', () => $toast.remove());
        },

        escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        },

        escAttr(str) {
            return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        },
    };

    // =========================================================================
    // Inicializar todas las instancias del widget en la página
    // =========================================================================
    function initAll() {
        $('.cpf-wrapper[data-cpf]').each(function () {
            if (!$(this).data('cpf-instance')) {
                const instance = new ProductFilter(this);
                $(this).data('cpf-instance', instance);
            }
        });
    }

    // =========================================================================
    // Interceptar added_to_cart de WooCommerce en TODA la tienda
    // (páginas de producto individuales, widgets de WC, etc.)
    // =========================================================================
    $(document.body).on('added_to_cart', function (e, fragments, cartHash, $button) {
        // Si lo disparó nuestro widget, ya mostramos el toast — evitar duplicado
        if ($button && $button.hasClass('cpf-card__btn')) return;

        // Ocultar solo el banner de add-to-cart nativo (no mensajes de checkout)
        $('.woocommerce-message').filter(function () {
            return /carrito|cart|added/i.test($(this).text());
        }).fadeOut(150, function () { $(this).remove(); });

        // Recopilar nombre e imagen del producto desde el DOM
        var name    = '';
        var imgSrc  = '';

        if ($button && $button.length) {
            var $area = $button.closest('.product, form.cart, .entry-content');
            name   = $area.find('h1.product_title, h1.entry-title, .product_title').first().text().trim();
            imgSrc = $area.find('.wp-post-image, .woocommerce-product-gallery__image img').first().attr('src') || '';
        }

        // Fallbacks
        if (!name) name = $('h1.product_title, h1.entry-title').first().text().trim();
        if (!name) name = document.title.split(/\s[–\-|]\s/)[0].trim() || 'Producto';
        if (!imgSrc) imgSrc = $('img.wp-post-image').first().attr('src') || '';

        var cartUrl = (typeof CPF_CONFIG !== 'undefined' && CPF_CONFIG.cart_url) ? CPF_CONFIG.cart_url : '/carrito';

        CPFToast.show({ name: name, image: imgSrc, cartUrl: cartUrl });
    });

    $(document).ready(function () {
        initAll();
    });

    // Re-inicializar cuando Elementor carga el widget en el editor
    $(window).on('elementor/frontend/init', function () {
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/cpf_product_filter.default', function ($scope) {
                const $wrapper = $scope.find('.cpf-wrapper[data-cpf]');
                if ($wrapper.length && !$wrapper.data('cpf-instance')) {
                    $wrapper.data('cpf-instance', new ProductFilter($wrapper[0]));
                }
            });
        }
    });

})(jQuery);
