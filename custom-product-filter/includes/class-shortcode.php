<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CPF_Shortcode {

    public static function render( $atts ) {
        $atts = shortcode_atts( [
            'per_page'     => 12,
            'columns'      => 3,
            'show_sidebar' => 'yes',
            'show_search'  => 'yes',
            'show_price'   => 'yes',
            'show_sort'    => 'yes',
            'show_rating'  => 'yes',
        ], $atts, 'custom_product_filter' );

        $per_page     = intval( $atts['per_page'] );
        $columns      = intval( $atts['columns'] );
        $show_sidebar = $atts['show_sidebar'] === 'yes';
        $show_search  = $atts['show_search']  === 'yes';
        $show_price   = $atts['show_price']   === 'yes';
        $show_sort    = $atts['show_sort']    === 'yes';
        $show_rating  = $atts['show_rating']  === 'yes';

        $config = [
            'per_page'     => $per_page,
            'columns'      => $columns,
            'show_sidebar' => $show_sidebar,
            'show_search'  => $show_search,
            'show_price'   => $show_price,
            'show_sort'    => $show_sort,
            'show_rating'  => $show_rating,
            'rest_url'     => rest_url( 'cpf/v1' ),
            'nonce'        => wp_create_nonce( 'wp_rest' ),
        ];

        ob_start();
        ?>
        <div class="cpf-wrapper<?php echo $show_sidebar ? ' cpf-wrapper--has-sidebar' : ''; ?>"
             data-cpf="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
             style="--cpf-cols: <?php echo $columns; ?>; --cpf-cols-tablet: 2; --cpf-cols-mobile: 1;">

            <?php if ( $show_sidebar ) : ?>
            <aside class="cpf-sidebar">
                <div class="cpf-sidebar__header">
                    <span class="cpf-sidebar__title">Filtros</span>
                    <button class="cpf-reset-btn" style="display:none" aria-label="Limpiar filtros">
                        Limpiar todo
                    </button>
                </div>
                <div class="cpf-sidebar__body">
                    <?php for ( $i = 0; $i < 3; $i++ ) : ?>
                    <div class="cpf-filter-skeleton" aria-hidden="true">
                        <div class="cpf-skeleton cpf-skeleton--heading"></div>
                        <div class="cpf-skeleton cpf-skeleton--line"></div>
                        <div class="cpf-skeleton cpf-skeleton--line"></div>
                        <div class="cpf-skeleton cpf-skeleton--line cpf-skeleton--short"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </aside>
            <?php endif; ?>

            <div class="cpf-main">

                <div class="cpf-toolbar">
                    <?php if ( $show_search ) : ?>
                    <div class="cpf-search">
                        <svg class="cpf-search__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" class="cpf-search__input" placeholder="Buscar productos..." aria-label="Buscar productos">
                    </div>
                    <?php endif; ?>

                    <div class="cpf-toolbar__right">
                        <span class="cpf-results-count" aria-live="polite"></span>
                        <?php if ( $show_sort ) : ?>
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

                <div class="cpf-active-tags" aria-label="Filtros activos"></div>

                <div class="cpf-grid" role="list" aria-label="Productos">
                    <?php for ( $i = 0; $i < $per_page; $i++ ) : ?>
                    <div class="cpf-skeleton-card" role="listitem" aria-hidden="true">
                        <div class="cpf-skeleton cpf-skeleton--image"></div>
                        <div class="cpf-skeleton cpf-skeleton--title"></div>
                        <div class="cpf-skeleton cpf-skeleton--price"></div>
                    </div>
                    <?php endfor; ?>
                </div>

                <div class="cpf-empty" style="display:none" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="28" cy="28" r="20"/><line x1="44" y1="44" x2="58" y2="58"/>
                        <line x1="20" y1="28" x2="36" y2="28"/>
                    </svg>
                    <p>No se encontraron productos.</p>
                    <button class="cpf-empty__reset">Limpiar filtros</button>
                </div>

                <nav class="cpf-pagination" aria-label="Paginación de productos"></nav>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
