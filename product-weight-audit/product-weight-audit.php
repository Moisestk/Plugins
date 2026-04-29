<?php
/**
 * Plugin Name: Product Weight & Dimensions Audit
 * Description: Analiza todos los productos WooCommerce e indica cuáles tienen peso y medidas y cuáles no.
 * Version:     1.1.0
 * Author:      Vynk
 * Text Domain: product-weight-audit
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PWA_VERSION', '1.1.0' );
define( 'PWA_PATH', plugin_dir_path( __FILE__ ) );
define( 'PWA_URL',  plugin_dir_url( __FILE__ ) );

final class Product_Weight_Audit {

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

        require_once PWA_PATH . 'includes/class-admin-page.php';
        require_once PWA_PATH . 'includes/class-gemini.php';

        $admin = new PWA_Admin_Page();
        $admin->init();

        add_action( 'wp_ajax_pwa_gemini_analyze',        [ $this, 'ajax_analyze' ] );
        add_action( 'wp_ajax_pwa_gemini_save',           [ $this, 'ajax_save' ] );
        add_action( 'wp_ajax_pwa_gemini_get_incomplete', [ $this, 'ajax_get_incomplete' ] );
    }

    public function ajax_analyze() {
        check_ajax_referer( 'pwa_gemini_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Sin permisos.' );
        }

        $id = intval( $_POST['product_id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( 'ID de producto inválido.' );
        }

        $result = PWA_Gemini::analyze( $id );

        if ( isset( $result['error'] ) ) {
            wp_send_json_error( [
                'message' => $result['error'],
                'retry'   => ! empty( $result['retry'] ),
            ] );
        }

        wp_send_json_success( $result );
    }

    public function ajax_save() {
        check_ajax_referer( 'pwa_gemini_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Sin permisos.' );
        }

        $id     = intval( $_POST['product_id'] ?? 0 );
        $weight = sanitize_text_field( $_POST['weight'] ?? '' );
        $length = sanitize_text_field( $_POST['length'] ?? '' );
        $width  = sanitize_text_field( $_POST['width']  ?? '' );
        $height = sanitize_text_field( $_POST['height'] ?? '' );

        $product = wc_get_product( $id );
        if ( ! $product ) {
            wp_send_json_error( 'Producto no encontrado.' );
        }

        if ( $weight !== '' ) $product->set_weight( $weight );
        if ( $length !== '' ) $product->set_length( $length );
        if ( $width  !== '' ) $product->set_width( $width );
        if ( $height !== '' ) $product->set_height( $height );

        $product->save();
        wp_send_json_success();
    }

    public function ajax_get_incomplete() {
        check_ajax_referer( 'pwa_gemini_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Sin permisos.' );
        }

        $filter  = sanitize_key( $_POST['filter'] ?? 'incomplete' );
        $allowed = [ 'all', 'incomplete', 'complete', 'partial', 'empty' ];
        if ( ! in_array( $filter, $allowed, true ) ) $filter = 'incomplete';

        $all_ids = get_posts( [
            'post_type'      => [ 'product', 'product_variation' ],
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ] );

        $result = [];
        foreach ( $all_ids as $id ) {
            $product = wc_get_product( $id );
            if ( ! $product ) continue;

            $weight = $product->get_weight();
            $length = $product->get_length();
            $width  = $product->get_width();
            $height = $product->get_height();

            $has_weight = $weight !== '' && $weight !== null && $weight !== '0';
            $has_dims   = $length !== '' && $length !== null && $length !== '0'
                       && $width  !== '' && $width  !== null && $width  !== '0'
                       && $height !== '' && $height !== null && $height !== '0';

            if ( $has_weight && $has_dims ) {
                $status = 'complete';
            } else {
                $any    = $has_weight
                       || ( $length !== '' && $length !== null && $length !== '0' )
                       || ( $width  !== '' && $width  !== null && $width  !== '0' )
                       || ( $height !== '' && $height !== null && $height !== '0' );
                $status = $any ? 'partial' : 'empty';
            }

            $include = false;
            switch ( $filter ) {
                case 'all':        $include = true;                    break;
                case 'incomplete': $include = $status !== 'complete';  break;
                case 'complete':   $include = $status === 'complete';  break;
                case 'partial':    $include = $status === 'partial';   break;
                case 'empty':      $include = $status === 'empty';     break;
            }

            if ( $include ) $result[] = $id;
        }

        wp_send_json_success( [ 'ids' => $result, 'total' => count( $result ) ] );
    }

    public function notice_woo_missing() {
        echo '<div class="notice notice-warning is-dismissible"><p><strong>Product Weight &amp; Dimensions Audit</strong> requiere que WooCommerce esté activo e instalado.</p></div>';
    }
}

Product_Weight_Audit::instance();
