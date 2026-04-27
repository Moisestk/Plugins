<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CPF_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'cpf_product_filter'; }
    public function get_title()      { return 'Filtro de Productos'; }
    public function get_icon()       { return 'eicon-filter'; }
    public function get_categories() { return [ 'basic' ]; }
    public function get_keywords()   { return [ 'woocommerce', 'products', 'filter', 'shop', 'catalog' ]; }

    protected function register_controls() {

        // =====================================================================
        // TAB CONTENT — Configuración
        // =====================================================================
        $this->start_controls_section( 'section_config', [
            'label' => 'Configuración',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'products_per_page', [
            'label'   => 'Productos por página',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 12,
            'min'     => 4,
            'max'     => 48,
            'step'    => 4,
        ] );

        $this->add_responsive_control( 'columns', [
            'label'          => 'Columnas del grid',
            'type'           => \Elementor\Controls_Manager::SELECT,
            'default'        => '3',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'options'        => [ '2' => '2', '3' => '3', '4' => '4' ],
        ] );

        $this->add_control( 'show_sidebar', [
            'label'     => 'Mostrar sidebar de filtros',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'label_on'  => 'Sí',
            'label_off' => 'No',
        ] );

        $this->add_control( 'show_search', [
            'label'     => 'Mostrar buscador',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'label_on'  => 'Sí',
            'label_off' => 'No',
        ] );

        $this->add_control( 'show_price_filter', [
            'label'     => 'Filtro de precio',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'label_on'  => 'Sí',
            'label_off' => 'No',
        ] );

        $this->add_control( 'show_sort', [
            'label'     => 'Mostrar ordenamiento',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'label_on'  => 'Sí',
            'label_off' => 'No',
        ] );

        $this->add_control( 'show_rating', [
            'label'     => 'Mostrar rating en cards',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'label_on'  => 'Sí',
            'label_off' => 'No',
        ] );

        $this->end_controls_section();

        // =====================================================================
        // TAB STYLE — Cards
        // =====================================================================
        $this->start_controls_section( 'section_style_cards', [
            'label' => 'Cards de producto',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'card_bg', [
            'label'     => 'Fondo',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .cpf-card' => 'background-color: {{VALUE}}' ],
        ] );

        $this->add_control( 'card_radius', [
            'label'      => 'Radio de borde',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
            'default'    => [ 'size' => 14, 'unit' => 'px' ],
            'selectors'  => [ '{{WRAPPER}} .cpf-card' => 'border-radius: {{SIZE}}{{UNIT}}' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .cpf-card',
            'fields_options' => [
                'box_shadow_type' => [ 'default' => 'yes' ],
                'box_shadow'      => [
                    'default' => [
                        'horizontal' => 0, 'vertical' => 4, 'blur' => 20,
                        'spread' => 0, 'color' => 'rgba(0,0,0,0.07)',
                    ],
                ],
            ],
        ] );

        $this->add_control( 'image_ratio', [
            'label'      => 'Proporción de imagen',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'range'      => [ '%' => [ 'min' => 50, 'max' => 150 ] ],
            'default'    => [ 'size' => 100, 'unit' => '%' ],
            'selectors'  => [ '{{WRAPPER}} .cpf-card__image-wrap' => 'padding-bottom: {{SIZE}}{{UNIT}}' ],
        ] );

        $this->end_controls_section();

        // =====================================================================
        // TAB STYLE — Colores
        // =====================================================================
        $this->start_controls_section( 'section_style_colors', [
            'label' => 'Colores',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'accent_color', [
            'label'     => 'Color de acento',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => 'var(--e-global-color-accent, #6C63FF)',
            'selectors' => [
                '{{WRAPPER}} .cpf-checkbox__box'                => 'border-color: {{VALUE}}',
                '{{WRAPPER}} input[type=checkbox]:checked + .cpf-checkbox__box' => 'background: {{VALUE}}; border-color: {{VALUE}}',
                '{{WRAPPER}} .cpf-tag--active'                  => 'background: {{VALUE}}; border-color: {{VALUE}}',
                '{{WRAPPER}} .cpf-pagination__btn.is-active'    => 'background: {{VALUE}}; border-color: {{VALUE}}; color: #fff',
                '{{WRAPPER}} .cpf-badge--sale'                  => 'background: {{VALUE}}',
                '{{WRAPPER}} .cpf-price-range__track-fill'      => 'background: {{VALUE}}',
                '{{WRAPPER}} .cpf-price-range input[type=range]::-webkit-slider-thumb' => 'background: {{VALUE}}',
            ],
        ] );

        $this->add_control( 'price_color', [
            'label'     => 'Color de precio',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => 'var(--e-global-color-text, #1a1a1a)',
            'selectors' => [ '{{WRAPPER}} .cpf-card__price' => 'color: {{VALUE}}' ],
        ] );

        $this->end_controls_section();

        // =====================================================================
        // TAB STYLE — Sidebar
        // =====================================================================
        $this->start_controls_section( 'section_style_sidebar', [
            'label' => 'Sidebar',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'sidebar_bg', [
            'label'     => 'Fondo del sidebar',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#f8f9fa',
            'selectors' => [ '{{WRAPPER}} .cpf-sidebar' => 'background-color: {{VALUE}}' ],
        ] );

        $this->add_control( 'sidebar_radius', [
            'label'      => 'Radio del sidebar',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 24 ] ],
            'default'    => [ 'size' => 14, 'unit' => 'px' ],
            'selectors'  => [ '{{WRAPPER}} .cpf-sidebar' => 'border-radius: {{SIZE}}{{UNIT}}' ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();

        $config = [
            'per_page'      => (int) $s['products_per_page'],
            'columns'       => (int) ( $s['columns'] ?? 3 ),
            'show_sidebar'  => $s['show_sidebar']      === 'yes',
            'show_search'   => $s['show_search']       === 'yes',
            'show_price'    => $s['show_price_filter'] === 'yes',
            'show_sort'     => $s['show_sort']         === 'yes',
            'show_rating'   => $s['show_rating']       === 'yes',
            'rest_url'      => rest_url( 'cpf/v1' ),
            'nonce'         => wp_create_nonce( 'wp_rest' ),
        ];

        $cols_desktop = (int) ( $s['columns']        ?? 3 );
        $cols_tablet  = (int) ( $s['columns_tablet'] ?? 2 );
        $cols_mobile  = 1;
        ?>
        <div class="cpf-wrapper<?php echo $s['show_sidebar'] === 'yes' ? ' cpf-wrapper--has-sidebar' : ''; ?>"
             data-cpf="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
             style="--cpf-cols: <?php echo $cols_desktop; ?>; --cpf-cols-tablet: <?php echo $cols_tablet; ?>; --cpf-cols-mobile: <?php echo $cols_mobile; ?>;">

            <?php if ( $s['show_sidebar'] === 'yes' ) : ?>
            <aside class="cpf-sidebar">
                <div class="cpf-sidebar__header">
                    <span class="cpf-sidebar__title">Filtros</span>
                    <button class="cpf-reset-btn" style="display:none" aria-label="Limpiar filtros">
                        Limpiar todo
                    </button>
                </div>
                <div class="cpf-sidebar__body">
                    <!-- Se renderiza dinámicamente via JS -->
                    <?php $this->render_filter_skeletons(); ?>
                </div>
            </aside>
            <?php endif; ?>

            <div class="cpf-main">

                <!-- Toolbar -->
                <div class="cpf-toolbar">
                    <?php if ( $s['show_search'] === 'yes' ) : ?>
                    <div class="cpf-search">
                        <svg class="cpf-search__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" class="cpf-search__input" placeholder="Buscar productos..." aria-label="Buscar productos">
                    </div>
                    <?php endif; ?>

                    <div class="cpf-toolbar__right">
                        <span class="cpf-results-count" aria-live="polite"></span>
                        <?php if ( $s['show_sort'] === 'yes' ) : ?>
                        <div class="cpf-sort">
                            <select class="cpf-sort__select" aria-label="Ordenar productos">
                                <option value="date_desc">Más recientes</option>
                                <option value="date_asc">Más antiguos</option>
                                <option value="price_asc">Precio: menor a mayor</option>
                                <option value="price_desc">Precio: mayor a menor</option>
                                <option value="title_asc">Nombre A–Z</option>
                                <option value="popularity_desc">Más populares</option>
                                <option value="rating_desc">Mejor valorados</option>
                            </select>
                            <svg class="cpf-sort__arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filtros activos -->
                <div class="cpf-active-tags" aria-label="Filtros activos"></div>

                <!-- Grid de productos -->
                <div class="cpf-grid" role="list" aria-label="Productos">
                    <?php for ( $i = 0; $i < (int) $s['products_per_page']; $i++ ) : ?>
                    <div class="cpf-skeleton-card" role="listitem" aria-hidden="true">
                        <div class="cpf-skeleton cpf-skeleton--image"></div>
                        <div class="cpf-skeleton cpf-skeleton--title"></div>
                        <div class="cpf-skeleton cpf-skeleton--price"></div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Sin resultados -->
                <div class="cpf-empty" style="display:none" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="28" cy="28" r="20"/><line x1="44" y1="44" x2="58" y2="58"/>
                        <line x1="20" y1="28" x2="36" y2="28"/>
                    </svg>
                    <p>No se encontraron productos.</p>
                    <button class="cpf-empty__reset">Limpiar filtros</button>
                </div>

                <!-- Paginación -->
                <nav class="cpf-pagination" aria-label="Paginación de productos"></nav>
            </div>
        </div>
        <?php
    }

    private function render_filter_skeletons() {
        for ( $i = 0; $i < 3; $i++ ) : ?>
        <div class="cpf-filter-skeleton" aria-hidden="true">
            <div class="cpf-skeleton cpf-skeleton--heading"></div>
            <div class="cpf-skeleton cpf-skeleton--line"></div>
            <div class="cpf-skeleton cpf-skeleton--line"></div>
            <div class="cpf-skeleton cpf-skeleton--line cpf-skeleton--short"></div>
        </div>
        <?php endfor;
    }
}
