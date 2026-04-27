<?php
/**
 * Plugin Name: Custom Product Filter
 * Description: Filtro moderno de productos WooCommerce via shortcode [custom_product_filter]
 * Version:     1.0.0
 * Author:      Custom
 * Text Domain: custom-product-filter
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CPF_VERSION', '1.0.0' );
define( 'CPF_PATH', plugin_dir_path( __FILE__ ) );
define( 'CPF_URL',  plugin_dir_url( __FILE__ ) );

final class Custom_Product_Filter {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'notice_woo_missing' ] );
            return;
        }

        require_once CPF_PATH . 'includes/class-rest-api.php';
        require_once CPF_PATH . 'includes/class-shortcode.php';

        add_shortcode( 'custom_product_filter', [ 'CPF_Shortcode', 'render' ] );
        add_action( 'wp_enqueue_scripts',             [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_cpf_add_to_cart',        [ $this, 'ajax_add_to_cart' ] );
        add_action( 'wp_ajax_nopriv_cpf_add_to_cart', [ $this, 'ajax_add_to_cart' ] );

        // Suprimir banner nativo
        add_filter( 'wc_add_to_cart_message_html', '__return_empty_string' );
        add_action( 'wp', [ $this, 'suppress_wc_notice' ], 999 );

        // Guardar último producto añadido en sesión (funciona con y sin AJAX)
        add_action( 'woocommerce_add_to_cart', [ $this, 'store_last_added' ], 10, 2 );

        // Inyectar datos del toast en el footer de CUALQUIER página
        add_action( 'wp_footer', [ $this, 'maybe_inject_toast_data' ] );

        ( new CPF_Rest_API() )->init();
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'cpf-widget',
            CPF_URL . 'assets/css/widget.css',
            [],
            CPF_VERSION
        );
        wp_enqueue_script(
            'cpf-filter',
            CPF_URL . 'assets/js/filter.js',
            [ 'jquery' ],
            CPF_VERSION,
            true
        );
        wp_localize_script( 'cpf-filter', 'CPF_CONFIG', [
            'rest_url'    => rest_url( 'cpf/v1' ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'currency'    => get_woocommerce_currency_symbol(),
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'wc_nonce'    => wp_create_nonce( 'add_to_cart_nonce' ),
            'cart_url'    => wc_get_cart_url(),
        ] );
    }

    public function ajax_add_to_cart() {
        check_ajax_referer( 'add_to_cart_nonce', 'nonce' );

        $product_id = intval( $_POST['product_id'] ?? 0 );
        $quantity   = intval( $_POST['quantity']   ?? 1 );

        if ( ! $product_id ) {
            wp_send_json_error( [ 'message' => 'Producto inválido.' ] );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
            wp_send_json_error( [ 'message' => 'Producto no disponible.' ] );
        }

        $added = WC()->cart->add_to_cart( $product_id, $quantity );

        if ( $added ) {
            $image_id  = $product->get_image_id();
            $image_url = $image_id
                ? wp_get_attachment_image_url( $image_id, [ 64, 64 ] )
                : wc_placeholder_img_src();

            wp_send_json_success( [
                'product_name'  => $product->get_name(),
                'product_image' => $image_url,
                'cart_count'    => WC()->cart->get_cart_contents_count(),
                'cart_url'      => wc_get_cart_url(),
            ] );
        } else {
            wp_send_json_error( [ 'message' => 'No se pudo agregar al carrito.' ] );
        }
    }

    public function suppress_wc_notice() {
        // Solo actuar cuando la URL indica que se acaba de añadir al carrito
        // y NO estamos en checkout, carrito ni páginas de cuenta
        if ( is_admin() || ! WC()->session ) return;
        if ( ! isset( $_GET['added-to-cart'] ) ) return;
        if ( is_checkout() || is_cart() || is_account_page() ) return;

        $notices = WC()->session->get( 'wc_notices', [] );
        if ( empty( $notices['success'] ) ) return;

        // Solo eliminar el mensaje que contiene "añadido" / "added" — no tocar otros notices
        foreach ( $notices['success'] as $key => $notice ) {
            $text = is_array( $notice ) ? ( $notice['notice'] ?? '' ) : $notice;
            if ( stripos( $text, 'carrito' ) !== false || stripos( $text, 'cart' ) !== false ) {
                unset( $notices['success'][ $key ] );
            }
        }
        $notices['success'] = array_values( $notices['success'] );
        WC()->session->set( 'wc_notices', $notices );
    }

    public function store_last_added( $cart_item_key, $product_id ) {
        if ( WC()->session ) {
            WC()->session->set( 'cpf_last_added', intval( $product_id ) );
        }
    }

    public function maybe_inject_toast_data() {
        if ( ! WC()->session ) return;

        $product_id = (int) WC()->session->get( 'cpf_last_added', 0 );
        if ( ! $product_id ) return;

        // Limpiar de sesión para que no se muestre dos veces
        WC()->session->set( 'cpf_last_added', 0 );

        $product = wc_get_product( $product_id );
        if ( ! $product ) return;

        $image_id  = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();

        $data = wp_json_encode( [
            'name'    => $product->get_name(),
            'image'   => $image_url,
            'cartUrl' => wc_get_cart_url(),
        ] );
        ?>
        <script>
        (function() {
            function showCPFToast() {
                if (typeof window.CPFToast !== 'undefined') {
                    window.CPFToast.show(<?php echo $data; ?>);
                } else {
                    // Reintentar hasta que el script cargue
                    setTimeout(showCPFToast, 100);
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showCPFToast);
            } else {
                showCPFToast();
            }
        })();
        </script>
        <?php
    }

    public function notice_woo_missing() {
        echo '<div class="notice notice-warning is-dismissible"><p><strong>Custom Product Filter</strong> requiere que WooCommerce esté activo e instalado.</p></div>';
    }
}

Custom_Product_Filter::instance();
