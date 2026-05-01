<?php
/**
 * Plugin Name: Vynk Quote Generator
 * Plugin URI:  https://vynk.us
 * Description: Generador de cotizaciones en PDF para el equipo de Vynk — busca productos, arma pedidos y exporta PDF con imágenes, precios y opciones de envío.
 * Version:     1.0.0
 * Author:      Ártica Group
 * Requires Plugins: woocommerce, skyway-cargo-calculator
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Vynk_Quote_Generator {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_vynk_qg_products',   [ $this, 'ajax_products' ] );
        add_action( 'wp_ajax_vynk_qg_shipping',   [ $this, 'ajax_shipping' ] );
        add_action( 'wp_ajax_vynk_qg_categories', [ $this, 'ajax_categories' ] );
        add_action( 'wp_ajax_vynk_qg_font',       [ $this, 'ajax_font' ] );
    }

    public function register_menu() {
        add_menu_page(
            'Cotizaciones — Vynk',
            'Cotizaciones',
            'manage_woocommerce',
            'vynk-quote-generator',
            [ $this, 'render_page' ],
            'dashicons-media-spreadsheet',
            58
        );
    }

    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_vynk-quote-generator' !== $hook ) return;

        wp_enqueue_style(
            'vynk-qg-fonts',
            'https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,600;0,700;0,800;1,400&display=swap',
            [],
            null
        );

        wp_enqueue_style(
            'vynk-qg',
            plugin_dir_url( __FILE__ ) . 'assets/admin.css',
            [ 'vynk-qg-fonts' ],
            '1.0.0'
        );

        wp_enqueue_script(
            'jspdf',
            'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
            [],
            '2.5.1',
            true
        );

        wp_enqueue_script(
            'vynk-qg',
            plugin_dir_url( __FILE__ ) . 'assets/admin.js',
            [ 'jquery', 'jspdf' ],
            '1.0.0',
            true
        );

        $logo_path = plugin_dir_path( __FILE__ ) . 'assets/logo.webp';
        $logo_url  = file_exists( $logo_path )
            ? plugin_dir_url( __FILE__ ) . 'assets/logo.webp'
            : '';

        wp_localize_script( 'vynk-qg', 'VynkQG', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'vynk_qg' ),
            'currency' => html_entity_decode( get_woocommerce_currency_symbol() ),
            'siteUrl'  => esc_url( home_url() ),
            'logoUrl'  => esc_url( $logo_url ),
        ] );
    }

    public function ajax_products() {
        check_ajax_referer( 'vynk_qg', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( -1 );

        $search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page     = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page = 24;

        $category_id = absint( $_POST['category'] ?? 0 );

        $args = [
            'status'  => 'publish',
            'type'    => [ 'simple', 'variable' ],
            'limit'   => $per_page,
            'offset'  => ( $page - 1 ) * $per_page,
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'objects',
        ];

        if ( $search !== '' ) {
            $args['s'] = $search;
        }

        if ( $category_id > 0 ) {
            $args['tax_query'] = [ [
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => $category_id,
                'include_children' => true,
            ] ];
        }

        $products = wc_get_products( $args );
        $data     = [];

        foreach ( $products as $product ) {
            $img_id  = $product->get_image_id();
            $img_url = $img_id
                ? (string) wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' )
                : (string) wc_placeholder_img_src( 'woocommerce_thumbnail' );

            $price = (float) $product->get_price();
            $reg   = (float) ( $product->get_regular_price() ?: $price );

            $data[] = [
                'id'            => $product->get_id(),
                'name'          => $product->get_name(),
                'slug'          => $product->get_slug(),
                'sku'           => $product->get_sku(),
                'price'         => $price,
                'regular_price' => $reg,
                'image'         => esc_url_raw( $img_url ),
                'type'          => $product->get_type(),
                'stock_status'  => $product->get_stock_status(),
            ];
        }

        wp_send_json_success( [
            'products' => $data,
            'has_more' => count( $products ) === $per_page,
            'page'     => $page,
        ] );
    }

    public function ajax_categories() {
        check_ajax_referer( 'vynk_qg', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( -1 );

        $terms = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'number'     => 0,
        ] );

        if ( is_wp_error( $terms ) ) {
            wp_send_json_success( [] );
            return;
        }

        $data = [];
        foreach ( $terms as $term ) {
            $data[] = [
                'id'     => $term->term_id,
                'name'   => $term->name,
                'parent' => $term->parent,
                'count'  => $term->count,
            ];
        }

        wp_send_json_success( $data );
    }

    public function ajax_shipping() {
        check_ajax_referer( 'vynk_qg', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( -1 );

        if ( ! function_exists( 'vynk_calcular_envio_cb' ) ) {
            wp_send_json_error( [ 'message' => 'Plugin de envío Skyway no está activo.' ] );
            return;
        }

        $raw = json_decode( stripslashes( wp_unslash( $_POST['items'] ?? '[]' ) ), true );
        if ( ! is_array( $raw ) ) $raw = [];

        $items = array_values( array_filter( array_map( function ( $item ) {
            $id  = absint( $item['id'] ?? 0 );
            $qty = max( 1, absint( $item['quantity'] ?? 1 ) );
            return $id > 0 ? [ 'id' => $id, 'quantity' => $qty ] : null;
        }, $raw ) ) );

        $results = [];

        foreach ( [ 'principal', 'interior' ] as $zone ) {
            $req = new WP_REST_Request( 'POST', '/vynk/v1/calcular-envio' );
            $req->set_body( wp_json_encode( [ 'items' => $items, 'destino' => $zone ] ) );
            $req->set_header( 'Content-Type', 'application/json' );

            $response = vynk_calcular_envio_cb( $req );
            $data     = is_wp_error( $response ) ? [ 'rates' => [] ] : $response->get_data();

            $results[ $zone ] = is_array( $data ) ? $data : [ 'rates' => [] ];
        }

        wp_send_json_success( $results );
    }

    public function ajax_font() {
        check_ajax_referer( 'vynk_qg', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( -1 );

        $weight  = in_array( absint( $_POST['weight'] ?? 400 ), [ 400, 700 ], true )
            ? absint( $_POST['weight'] )
            : 400;
        $ital    = ! empty( $_POST['ital'] ) ? 1 : 0;
        $key     = 'vynk_qg_barlow_' . $weight . ( $ital ? 'i' : '' );
        $cached  = get_transient( $key );

        if ( $cached ) {
            wp_send_json_success( $cached );
            return;
        }

        $family = $ital
            ? 'Barlow:ital,wght@1,' . $weight
            : 'Barlow:wght@' . $weight;

        $css = wp_remote_retrieve_body( wp_remote_get(
            'https://fonts.googleapis.com/css2?family=' . rawurlencode( $family ),
            [ 'user-agent' => 'Mozilla/5.0 (Linux; U; Android 2.2; en-us) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1' ]
        ) );

        preg_match( '/url\(([^)]+\.ttf)\)/i', $css, $m );

        if ( empty( $m[1] ) ) {
            wp_send_json_error( 'no_ttf' );
            return;
        }

        $body = wp_remote_retrieve_body( wp_remote_get( trim( $m[1], "' \"" ) ) );

        if ( ! $body ) {
            wp_send_json_error( 'no_body' );
            return;
        }

        $b64 = base64_encode( $body );
        set_transient( $key, $b64, DAY_IN_SECONDS );
        wp_send_json_success( $b64 );
    }

    public function render_page() {
        ?>
        <div class="wrap">
        <div id="vynk-qg-app">

            <div class="vynk-catalog">
                <div class="vynk-catalog-header">
                    <div class="vynk-brand">
                        <span class="vynk-logo-badge">VYNK</span>
                        <span class="vynk-header-title">Generador de Cotizaciones</span>
                    </div>
                    <div class="vynk-search-wrap">
                        <svg class="vynk-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" id="vynk-search" placeholder="Buscar por nombre o SKU…" autocomplete="off" />
                    </div>

                    <select id="vynk-category" class="vynk-category-select">
                        <option value="0">Todas las categorías</option>
                    </select>
                </div>

                <div id="vynk-loading" class="vynk-loading">
                    <div class="vynk-spinner"></div>
                    <p>Cargando productos…</p>
                </div>

                <div id="vynk-products-list-wrap" style="display:none;">
                    <div class="vynk-list-header">
                        <div class="vynk-col-thumb"></div>
                        <div class="vynk-col-name">Nombre</div>
                        <div class="vynk-col-sku">SKU</div>
                        <div class="vynk-col-stock">Inventario</div>
                        <div class="vynk-col-price">Precio</div>
                        <div class="vynk-col-action"></div>
                    </div>
                    <div id="vynk-products-grid" class="vynk-products-grid"></div>
                </div>

                <div id="vynk-load-more-wrap" class="vynk-load-more-wrap" style="display:none;">
                    <button id="vynk-load-more-btn" class="vynk-load-more-btn">Cargar más productos</button>
                </div>

                <div id="vynk-no-results" class="vynk-no-results" style="display:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M8 11h6M11 8v6" opacity=".4"/>
                    </svg>
                    <p>Sin resultados para tu búsqueda</p>
                </div>
            </div>

            <div class="vynk-quote-panel">
                <div class="vynk-quote-header">
                    <div class="vynk-quote-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        Cotización
                    </div>
                    <button id="vynk-clear-quote" class="vynk-clear-btn" title="Limpiar cotización">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6M14 11v6"/>
                        </svg>
                    </button>
                </div>

                <div id="vynk-quote-empty" class="vynk-quote-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <p>Agrega productos del catálogo<br>para crear una cotización</p>
                </div>

                <div id="vynk-quote-items" class="vynk-quote-items" style="display:none;"></div>

                <div id="vynk-quote-summary" class="vynk-quote-summary" style="display:none;">
                    <div class="vynk-subtotal-row">
                        <span>Subtotal</span>
                        <strong id="vynk-subtotal">$0.00</strong>
                    </div>

                    <div id="vynk-shipping-status" class="vynk-shipping-status" style="display:none;">
                        <div class="vynk-spinner vynk-spinner-sm"></div>
                        <span>Calculando envío…</span>
                    </div>

                    <div id="vynk-shipping-results" class="vynk-shipping-results" style="display:none;">
                        <div class="vynk-shipping-label">Opciones de envío</div>
                        <div id="vynk-rates-list" class="vynk-rates-simple"></div>
                    </div>

                    <button id="vynk-gen-pdf" class="vynk-btn vynk-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <line x1="10" y1="9" x2="8" y2="9"/>
                        </svg>
                        Generar PDF
                    </button>
                </div>
            </div>

        </div>
        </div>
        <?php
    }
}

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) return;
    Vynk_Quote_Generator::instance();
} );
