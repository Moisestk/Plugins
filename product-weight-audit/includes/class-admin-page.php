<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PWA_Admin_Page {

    const PER_PAGE = 30;

    public function init() {
        add_action( 'admin_menu',                    [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts',         [ $this, 'enqueue_assets' ] );
        add_action( 'admin_post_pwa_export_csv',     [ $this, 'export_csv' ] );
        add_action( 'admin_post_pwa_save_settings',  [ $this, 'save_settings' ] );
    }

    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            'Auditoría de Peso y Medidas',
            'Auditoría de Medidas',
            'manage_woocommerce',
            'pwa-audit',
            [ $this, 'render_page' ]
        );
    }

    public function enqueue_assets( $hook ) {
        if ( $hook !== 'woocommerce_page_pwa-audit' ) return;
        wp_enqueue_style( 'pwa-admin', PWA_URL . 'assets/css/admin.css', [], PWA_VERSION );
        wp_enqueue_script( 'pwa-gemini', PWA_URL . 'assets/js/gemini.js', [ 'jquery' ], PWA_VERSION, true );
        wp_localize_script( 'pwa-gemini', 'pwaGemini', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'pwa_gemini_nonce' ),
            'hasApiKey'  => ! empty( get_option( 'pwa_gemini_api_key' ) ),
            'unitWeight' => get_option( 'woocommerce_weight_unit', 'kg' ),
            'unitDim'    => get_option( 'woocommerce_dimension_unit', 'cm' ),
        ] );
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public function save_settings() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Sin permisos.' );
        check_admin_referer( 'pwa_save_settings' );
        update_option( 'pwa_gemini_api_key', sanitize_text_field( $_POST['pwa_gemini_api_key'] ?? '' ) );
        wp_redirect( admin_url( 'admin.php?page=pwa-audit&tab=settings&saved=1' ) );
        exit;
    }

    // -------------------------------------------------------------------------
    // Data
    // -------------------------------------------------------------------------

    private function get_products( $filter = 'all', $search = '', $page = 1 ) {
        $args = [
            'post_type'      => [ 'product', 'product_variation' ],
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        if ( $search !== '' ) {
            $args['s'] = sanitize_text_field( $search );
        }

        $ids  = get_posts( $args );
        $rows = [];

        foreach ( $ids as $id ) {
            $product = wc_get_product( $id );
            if ( ! $product ) continue;

            if ( $product->is_type( 'variation' ) && ! $product->get_parent_id() ) continue;

            $weight = $product->get_weight();
            $length = $product->get_length();
            $width  = $product->get_width();
            $height = $product->get_height();

            $has_weight = $weight !== '' && $weight !== null && $weight !== '0';
            $has_dims   = ( $length !== '' && $length !== null && $length !== '0' )
                       && ( $width  !== '' && $width  !== null && $width  !== '0' )
                       && ( $height !== '' && $height !== null && $height !== '0' );

            $status = $this->resolve_status( $has_weight, $has_dims, $weight, $length, $width, $height );

            if ( $filter !== 'all' && $status !== $filter ) continue;

            $parent_name = '';
            if ( $product->is_type( 'variation' ) ) {
                $parent      = wc_get_product( $product->get_parent_id() );
                $parent_name = $parent ? $parent->get_name() . ' › ' : '';
            }

            $rows[] = [
                'id'          => $id,
                'name'        => $parent_name . $product->get_name(),
                'sku'         => $product->get_sku() ?: '—',
                'type'        => $product->get_type(),
                'weight'      => $weight ?: '—',
                'length'      => $length ?: '—',
                'width'       => $width  ?: '—',
                'height'      => $height ?: '—',
                'has_weight'  => $has_weight,
                'has_dims'    => $has_dims,
                'status'      => $status,
                'edit_url'    => get_edit_post_link( $id ),
                'thumb_url'   => get_the_post_thumbnail_url( $id, 'thumbnail' ) ?: '',
            ];
        }

        return $rows;
    }

    private function resolve_status( $has_weight, $has_dims, $weight, $length, $width, $height ) {
        if ( $has_weight && $has_dims ) return 'complete';

        $any = $has_weight
            || ( $length !== '' && $length !== null && $length !== '0' )
            || ( $width  !== '' && $width  !== null && $width  !== '0' )
            || ( $height !== '' && $height !== null && $height !== '0' );

        return $any ? 'partial' : 'empty';
    }

    private function paginate( array $rows, int $page ): array {
        $total  = count( $rows );
        $pages  = (int) ceil( $total / self::PER_PAGE );
        $page   = max( 1, min( $page, max( 1, $pages ) ) );
        $offset = ( $page - 1 ) * self::PER_PAGE;
        $slice  = array_slice( $rows, $offset, self::PER_PAGE );

        return [ $slice, $total, $pages, $page ];
    }

    private function count_by_status( array $rows ): array {
        $counts = [ 'complete' => 0, 'partial' => 0, 'empty' => 0 ];
        foreach ( $rows as $row ) {
            $counts[ $row['status'] ] ++;
        }
        return $counts;
    }

    // -------------------------------------------------------------------------
    // Render — main
    // -------------------------------------------------------------------------

    public function render_page() {
        $tab          = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'audit';
        $allowed_tabs = [ 'audit', 'settings' ];
        if ( ! in_array( $tab, $allowed_tabs, true ) ) $tab = 'audit';

        $has_key = ! empty( get_option( 'pwa_gemini_api_key' ) );
        ?>
        <div class="wrap pwa-wrap">
            <h1 class="wp-heading-inline">Auditoría de Peso y Medidas</h1>

            <?php if ( $tab === 'audit' ): ?>
                <?php
                $filter     = isset( $_GET['pwa_filter'] ) ? sanitize_key( $_GET['pwa_filter'] )        : 'all';
                $search     = isset( $_GET['pwa_search'] ) ? sanitize_text_field( $_GET['pwa_search'] ) : '';
                $export_url = wp_nonce_url(
                    admin_url( 'admin-post.php?action=pwa_export_csv&pwa_filter=' . urlencode( $filter ) . '&pwa_search=' . urlencode( $search ) ),
                    'pwa_export_csv'
                );
                ?>
                <a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action pwa-export-btn">
                    Exportar CSV
                </a>
                <button
                    id="pwa-btn-bulk"
                    class="page-title-action pwa-btn-ai-bulk"
                    <?php if ( ! $has_key ) echo 'disabled title="Configure la API key de Gemini primero."'; ?>
                >
                    ✦ Analizar incompletos con IA
                </button>
            <?php endif; ?>

            <hr class="wp-header-end">

            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pwa-audit' ) ); ?>"
                   class="nav-tab <?php echo $tab === 'audit' ? 'nav-tab-active' : ''; ?>">
                    Auditoría
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pwa-audit&tab=settings' ) ); ?>"
                   class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    Configuración Gemini
                    <?php if ( ! $has_key ): ?>
                        <span class="pwa-tab-badge">!</span>
                    <?php endif; ?>
                </a>
            </nav>

            <?php if ( $tab === 'settings' ): ?>
                <?php $this->render_settings_tab(); ?>
            <?php else: ?>
                <?php $this->render_audit_body(); ?>
            <?php endif; ?>

            <?php $this->render_gemini_modal(); ?>
            <?php $this->render_bulk_panel(); ?>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Render — settings tab
    // -------------------------------------------------------------------------

    private function render_settings_tab() {
        $api_key = get_option( 'pwa_gemini_api_key', '' );
        $saved   = isset( $_GET['saved'] );
        ?>
        <div class="pwa-settings-wrap">
            <?php if ( $saved ): ?>
                <div class="notice notice-success inline is-dismissible">
                    <p>Configuración guardada correctamente.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="pwa_save_settings">
                <?php wp_nonce_field( 'pwa_save_settings' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="pwa_gemini_api_key">API Key de Gemini</label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="pwa_gemini_api_key"
                                name="pwa_gemini_api_key"
                                value="<?php echo esc_attr( $api_key ); ?>"
                                class="regular-text"
                                autocomplete="off"
                                placeholder="AIza..."
                            >
                            <p class="description">
                                Obtén tu API key gratuita en
                                <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">Google AI Studio</a>.
                                Se usa el modelo <strong>gemini-2.0-flash</strong>.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( 'Guardar configuración' ); ?>
            </form>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Render — audit body
    // -------------------------------------------------------------------------

    private function render_audit_body() {
        $filter  = isset( $_GET['pwa_filter'] )  ? sanitize_key( $_GET['pwa_filter'] )         : 'all';
        $search  = isset( $_GET['pwa_search'] )  ? sanitize_text_field( $_GET['pwa_search'] )  : '';
        $page    = isset( $_GET['pwa_page'] )    ? max( 1, intval( $_GET['pwa_page'] ) )       : 1;

        $allowed_filters = [ 'all', 'complete', 'partial', 'empty' ];
        if ( ! in_array( $filter, $allowed_filters, true ) ) $filter = 'all';

        $all_rows = $this->get_products( 'all', $search );
        $counts   = $this->count_by_status( $all_rows );

        $filtered_rows = ( $filter === 'all' )
            ? $all_rows
            : array_values( array_filter( $all_rows, fn( $r ) => $r['status'] === $filter ) );

        [ $rows, $total, $pages, $page ] = $this->paginate( $filtered_rows, $page );

        $base_url    = admin_url( 'admin.php?page=pwa-audit' );
        $unit_weight = get_option( 'woocommerce_weight_unit', 'kg' );
        $unit_dim    = get_option( 'woocommerce_dimension_unit', 'cm' );
        ?>

        <?php $this->render_summary( $counts, count( $all_rows ) ); ?>

        <form method="get" class="pwa-toolbar">
            <input type="hidden" name="page" value="pwa-audit">
            <?php $this->render_filters( $filter, $counts, $base_url ); ?>
            <div class="pwa-search-wrap">
                <input
                    type="search"
                    name="pwa_search"
                    value="<?php echo esc_attr( $search ); ?>"
                    placeholder="Buscar por nombre o SKU…"
                    class="pwa-search-input"
                >
                <?php if ( $filter !== 'all' ): ?>
                    <input type="hidden" name="pwa_filter" value="<?php echo esc_attr( $filter ); ?>">
                <?php endif; ?>
                <button type="submit" class="button">Buscar</button>
                <?php if ( $search !== '' ): ?>
                    <a href="<?php echo esc_url( $base_url . ( $filter !== 'all' ? '&pwa_filter=' . $filter : '' ) ); ?>" class="button">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ( empty( $rows ) ): ?>
            <div class="pwa-empty">No se encontraron productos con los filtros actuales.</div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped pwa-table">
                <thead>
                    <tr>
                        <th class="col-name">Producto</th>
                        <th class="col-sku">SKU</th>
                        <th class="col-type">Tipo</th>
                        <th class="col-weight">Peso (<?php echo esc_html( $unit_weight ); ?>)</th>
                        <th class="col-dim">Alto (<?php echo esc_html( $unit_dim ); ?>)</th>
                        <th class="col-dim">Ancho (<?php echo esc_html( $unit_dim ); ?>)</th>
                        <th class="col-dim">Largo (<?php echo esc_html( $unit_dim ); ?>)</th>
                        <th class="col-status">Estado</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $row ): ?>
                        <tr
                            class="pwa-row pwa-row--<?php echo esc_attr( $row['status'] ); ?>"
                            data-product-id="<?php echo esc_attr( $row['id'] ); ?>"
                        >
                            <td class="col-name">
                                <strong><?php echo esc_html( $row['name'] ); ?></strong>
                            </td>
                            <td><?php echo esc_html( $row['sku'] ); ?></td>
                            <td><?php echo esc_html( ucfirst( $row['type'] ) ); ?></td>
                            <td class="<?php echo $row['has_weight'] ? 'pwa-has' : 'pwa-missing'; ?>">
                                <?php echo esc_html( $row['weight'] ); ?>
                            </td>
                            <td class="<?php echo ( $row['height'] !== '—' ) ? 'pwa-has' : 'pwa-missing'; ?>">
                                <?php echo esc_html( $row['height'] ); ?>
                            </td>
                            <td class="<?php echo ( $row['width'] !== '—' ) ? 'pwa-has' : 'pwa-missing'; ?>">
                                <?php echo esc_html( $row['width'] ); ?>
                            </td>
                            <td class="<?php echo ( $row['length'] !== '—' ) ? 'pwa-has' : 'pwa-missing'; ?>">
                                <?php echo esc_html( $row['length'] ); ?>
                            </td>
                            <td>
                                <?php echo $this->status_badge( $row['status'] ); ?>
                            </td>
                            <td class="col-actions">
                                <a href="<?php echo esc_url( $row['edit_url'] ); ?>" class="button button-small" target="_blank">
                                    Editar
                                </a>
                                <?php if ( $row['status'] !== 'complete' ): ?>
                                    <button
                                        class="button button-small pwa-btn-ai"
                                        data-id="<?php echo esc_attr( $row['id'] ); ?>"
                                        data-name="<?php echo esc_attr( $row['name'] ); ?>"
                                        data-sku="<?php echo esc_attr( $row['sku'] ); ?>"
                                        data-img="<?php echo esc_attr( $row['thumb_url'] ); ?>"
                                    >✦ IA</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php $this->render_pagination( $page, $pages, $filter, $search, $base_url, $total ); ?>
        <?php endif; ?>
        <?php
    }

    // -------------------------------------------------------------------------
    // Render — Gemini modal
    // -------------------------------------------------------------------------

    private function render_gemini_modal() {
        $unit_weight = get_option( 'woocommerce_weight_unit', 'kg' );
        $unit_dim    = get_option( 'woocommerce_dimension_unit', 'cm' );
        ?>
        <div id="pwa-gemini-modal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="pwa-modal-name">
            <div id="pwa-modal-overlay"></div>
            <div class="pwa-modal__box">
                <div class="pwa-modal__header">
                    <img id="pwa-modal-img" src="" alt="" style="display:none">
                    <div class="pwa-modal__title">
                        <h3 id="pwa-modal-name"></h3>
                        <span id="pwa-modal-sku" class="pwa-modal__sku"></span>
                    </div>
                    <button id="pwa-modal-close" class="pwa-modal__close" aria-label="Cerrar">&times;</button>
                </div>

                <div class="pwa-modal__body">
                    <div id="pwa-gemini-loading" class="pwa-gemini-loading">
                        <div class="pwa-spinner"></div>
                        <p>Consultando Gemini AI&hellip;</p>
                    </div>

                    <div id="pwa-gemini-result" style="display:none">
                        <div class="pwa-modal__meta">
                            <span id="pwa-confidence-badge" class="pwa-confidence"></span>
                            <p id="pwa-reasoning" class="pwa-reasoning"></p>
                        </div>
                        <div class="pwa-fields">
                            <label class="pwa-field">
                                <span>Peso (<?php echo esc_html( $unit_weight ); ?>)</span>
                                <input type="number" id="pwa-field-weight" step="0.001" min="0" placeholder="0.000">
                            </label>
                            <label class="pwa-field">
                                <span>Largo (<?php echo esc_html( $unit_dim ); ?>)</span>
                                <input type="number" id="pwa-field-length" step="0.1" min="0" placeholder="0.0">
                            </label>
                            <label class="pwa-field">
                                <span>Ancho (<?php echo esc_html( $unit_dim ); ?>)</span>
                                <input type="number" id="pwa-field-width" step="0.1" min="0" placeholder="0.0">
                            </label>
                            <label class="pwa-field">
                                <span>Alto (<?php echo esc_html( $unit_dim ); ?>)</span>
                                <input type="number" id="pwa-field-height" step="0.1" min="0" placeholder="0.0">
                            </label>
                        </div>
                    </div>

                    <div id="pwa-gemini-error" class="pwa-error-box" style="display:none">
                        <p id="pwa-error-msg"></p>
                    </div>
                </div>

                <div class="pwa-modal__footer">
                    <button id="pwa-btn-save" class="button button-primary" style="display:none">Guardar datos</button>
                    <button id="pwa-btn-cancel" class="button">Cancelar</button>
                </div>
            </div>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Render — bulk panel
    // -------------------------------------------------------------------------

    private function render_bulk_panel() {
        ?>
        <div id="pwa-bulk-panel" class="pwa-bulk-panel" style="display:none">
            <div class="pwa-bulk-panel__header" id="pwa-bulk-panel__title">
                <div>
                    <strong>Análisis masivo con Gemini AI</strong>
                    <span id="pwa-bulk-category" class="pwa-bulk-category"></span>
                    <span id="pwa-bulk-status" class="pwa-bulk-status">Procesando...</span>
                </div>
                <div class="pwa-bulk-right">
                    <span id="pwa-bulk-counter" class="pwa-bulk-counter">0 / 0</span>
                    <button id="pwa-bulk-stop" class="button button-secondary">Detener</button>
                </div>
            </div>
            <div class="pwa-bulk-progress">
                <div class="pwa-progress-bar">
                    <div id="pwa-bulk-fill" class="pwa-progress-fill" style="width:0%"></div>
                </div>
            </div>
            <div id="pwa-bulk-log" class="pwa-bulk-log"></div>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Render — summary / filters / pagination / badge (unchanged)
    // -------------------------------------------------------------------------

    private function render_summary( array $counts, int $total ) {
        $complete_pct = $total > 0 ? round( ( $counts['complete'] / $total ) * 100 ) : 0;
        $has_key      = ! empty( get_option( 'pwa_gemini_api_key' ) );
        $disabled     = $has_key ? '' : 'disabled title="Configure la API key de Gemini primero."';

        $cards = [
            [ 'class' => 'complete', 'number' => $counts['complete'], 'label' => 'Completos',      'filter' => 'complete' ],
            [ 'class' => 'partial',  'number' => $counts['partial'],  'label' => 'Parciales',       'filter' => 'partial'  ],
            [ 'class' => 'empty',    'number' => $counts['empty'],    'label' => 'Sin datos',       'filter' => 'empty'    ],
            [ 'class' => 'total',    'number' => $total,              'label' => 'Total productos', 'filter' => null       ],
        ];
        ?>
        <div class="pwa-summary">
            <?php foreach ( $cards as $card ): ?>
            <div class="pwa-summary__item">
                <div class="pwa-summary__card pwa-summary__card--<?php echo esc_attr( $card['class'] ); ?>">
                    <span class="pwa-summary__number"><?php echo esc_html( $card['number'] ); ?></span>
                    <span class="pwa-summary__label"><?php echo esc_html( $card['label'] ); ?></span>
                </div>
                <?php if ( $card['filter'] !== null ): ?>
                <button
                    class="pwa-card-analyze-btn"
                    data-filter="<?php echo esc_attr( $card['filter'] ); ?>"
                    data-label="<?php echo esc_attr( $card['label'] ); ?>"
                    <?php echo $disabled; ?>
                >Generar todos</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="pwa-progress-wrap">
                <div class="pwa-progress-label">Completitud: <?php echo $complete_pct; ?>%</div>
                <div class="pwa-progress-bar">
                    <div class="pwa-progress-fill" style="width:<?php echo $complete_pct; ?>%"></div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_filters( string $active, array $counts, string $base_url ) {
        $filters = [
            'all'      => 'Todos',
            'complete' => 'Completos',
            'partial'  => 'Parciales',
            'empty'    => 'Sin datos',
        ];
        echo '<div class="pwa-filter-tabs">';
        foreach ( $filters as $key => $label ) {
            $url          = $key === 'all' ? $base_url : $base_url . '&pwa_filter=' . $key;
            $active_class = $active === $key ? ' pwa-tab--active' : '';
            $count_html   = $key !== 'all' ? ' <span class="pwa-tab-count">(' . $counts[ $key ] . ')</span>' : '';
            printf(
                '<a href="%s" class="pwa-tab pwa-tab--%s%s">%s%s</a>',
                esc_url( $url ),
                esc_attr( $key ),
                esc_attr( $active_class ),
                esc_html( $label ),
                $count_html
            );
        }
        echo '</div>';
    }

    private function render_pagination( int $page, int $pages, string $filter, string $search, string $base_url, int $total ) {
        if ( $pages <= 1 ) return;

        $start = ( ( $page - 1 ) * self::PER_PAGE ) + 1;
        $end   = min( $page * self::PER_PAGE, $total );

        $qs = '';
        if ( $filter !== 'all' ) $qs .= '&pwa_filter=' . urlencode( $filter );
        if ( $search !== '' )    $qs .= '&pwa_search=' . urlencode( $search );

        echo '<div class="pwa-pagination">';
        printf( '<span class="pwa-pagination__info">Mostrando %d–%d de %d</span>', $start, $end, $total );
        echo '<div class="pwa-pagination__links">';

        if ( $page > 1 ) {
            printf( '<a href="%s" class="button">&#8592; Anterior</a>', esc_url( $base_url . $qs . '&pwa_page=' . ( $page - 1 ) ) );
        }

        for ( $i = max( 1, $page - 2 ); $i <= min( $pages, $page + 2 ); $i++ ) {
            $active = $i === $page ? ' pwa-page--active' : '';
            printf(
                '<a href="%s" class="button%s">%d</a>',
                esc_url( $base_url . $qs . '&pwa_page=' . $i ),
                esc_attr( $active ),
                $i
            );
        }

        if ( $page < $pages ) {
            printf( '<a href="%s" class="button">Siguiente &#8594;</a>', esc_url( $base_url . $qs . '&pwa_page=' . ( $page + 1 ) ) );
        }

        echo '</div></div>';
    }

    private function status_badge( string $status ): string {
        $labels = [
            'complete' => [ 'Completo',  'pwa-badge--complete' ],
            'partial'  => [ 'Parcial',   'pwa-badge--partial'  ],
            'empty'    => [ 'Sin datos', 'pwa-badge--empty'    ],
        ];
        [ $label, $class ] = $labels[ $status ] ?? [ $status, '' ];
        return sprintf( '<span class="pwa-badge %s">%s</span>', esc_attr( $class ), esc_html( $label ) );
    }

    // -------------------------------------------------------------------------
    // CSV Export
    // -------------------------------------------------------------------------

    public function export_csv() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Sin permisos.' );
        check_admin_referer( 'pwa_export_csv' );

        $filter = isset( $_GET['pwa_filter'] ) ? sanitize_key( $_GET['pwa_filter'] )        : 'all';
        $search = isset( $_GET['pwa_search'] ) ? sanitize_text_field( $_GET['pwa_search'] ) : '';

        $allowed = [ 'all', 'complete', 'partial', 'empty' ];
        if ( ! in_array( $filter, $allowed, true ) ) $filter = 'all';

        $all_rows = $this->get_products( 'all', $search );
        $rows     = ( $filter === 'all' )
            ? $all_rows
            : array_values( array_filter( $all_rows, fn( $r ) => $r['status'] === $filter ) );

        $unit_weight = get_option( 'woocommerce_weight_unit', 'kg' );
        $unit_dim    = get_option( 'woocommerce_dimension_unit', 'cm' );
        $filename    = 'auditoria-medidas-' . date( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );

        $output = fopen( 'php://output', 'w' );
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

        fputcsv( $output, [
            'ID',
            'Nombre',
            'SKU',
            'Tipo',
            'Peso (' . $unit_weight . ')',
            'Alto (' . $unit_dim . ')',
            'Ancho (' . $unit_dim . ')',
            'Largo (' . $unit_dim . ')',
            'Estado',
            'URL Editar',
        ] );

        $status_labels = [
            'complete' => 'Completo',
            'partial'  => 'Parcial',
            'empty'    => 'Sin datos',
        ];

        foreach ( $rows as $row ) {
            fputcsv( $output, [
                $row['id'],
                $row['name'],
                $row['sku'],
                ucfirst( $row['type'] ),
                $row['weight'],
                $row['height'],
                $row['width'],
                $row['length'],
                $status_labels[ $row['status'] ] ?? $row['status'],
                $row['edit_url'],
            ] );
        }

        fclose( $output );
        exit;
    }
}
