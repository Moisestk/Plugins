<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PWA_Admin_Page {

    const PER_PAGE = 30;

    public function init() {
        add_action( 'admin_menu',            [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_post_pwa_export_csv', [ $this, 'export_csv' ] );
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

        $ids = get_posts( $args );

        $rows = [];
        foreach ( $ids as $id ) {
            $product = wc_get_product( $id );
            if ( ! $product ) continue;

            // Saltar variaciones huérfanas
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
                $parent = wc_get_product( $product->get_parent_id() );
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
    // Render
    // -------------------------------------------------------------------------

    public function render_page() {
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

        $base_url = admin_url( 'admin.php?page=pwa-audit' );
        $export_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=pwa_export_csv&pwa_filter=' . urlencode( $filter ) . '&pwa_search=' . urlencode( $search ) ),
            'pwa_export_csv'
        );

        $unit_weight = get_option( 'woocommerce_weight_unit', 'kg' );
        $unit_dim    = get_option( 'woocommerce_dimension_unit', 'cm' );
        ?>
        <div class="wrap pwa-wrap">
            <h1 class="wp-heading-inline">Auditoría de Peso y Medidas</h1>
            <a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action pwa-export-btn">
                Exportar CSV
            </a>
            <hr class="wp-header-end">

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
                            <tr class="pwa-row pwa-row--<?php echo esc_attr( $row['status'] ); ?>">
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
                                <td>
                                    <a href="<?php echo esc_url( $row['edit_url'] ); ?>" class="button button-small" target="_blank">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php $this->render_pagination( $page, $pages, $filter, $search, $base_url, $total ); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_summary( array $counts, int $total ) {
        $complete_pct = $total > 0 ? round( ( $counts['complete'] / $total ) * 100 ) : 0;
        ?>
        <div class="pwa-summary">
            <div class="pwa-summary__card pwa-summary__card--complete">
                <span class="pwa-summary__number"><?php echo $counts['complete']; ?></span>
                <span class="pwa-summary__label">Completos</span>
            </div>
            <div class="pwa-summary__card pwa-summary__card--partial">
                <span class="pwa-summary__number"><?php echo $counts['partial']; ?></span>
                <span class="pwa-summary__label">Parciales</span>
            </div>
            <div class="pwa-summary__card pwa-summary__card--empty">
                <span class="pwa-summary__number"><?php echo $counts['empty']; ?></span>
                <span class="pwa-summary__label">Sin datos</span>
            </div>
            <div class="pwa-summary__card pwa-summary__card--total">
                <span class="pwa-summary__number"><?php echo $total; ?></span>
                <span class="pwa-summary__label">Total productos</span>
            </div>
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
            $url     = $key === 'all' ? $base_url : $base_url . '&pwa_filter=' . $key;
            $active_class = $active === $key ? ' pwa-tab--active' : '';
            $count_display = $key !== 'all' ? ' <span class="pwa-tab-count">(' . $counts[ $key ] . ')</span>' : '';
            printf(
                '<a href="%s" class="pwa-tab pwa-tab--%s%s">%s%s</a>',
                esc_url( $url ),
                esc_attr( $key ),
                esc_attr( $active_class ),
                esc_html( $label ),
                $count_display
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
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ); // UTF-8 BOM para Excel

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
