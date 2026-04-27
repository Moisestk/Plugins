<?php
/**
 * Plugin Name: Product Weight & Dimensions Audit
 * Description: Analiza todos los productos WooCommerce e indica cuáles tienen peso y medidas y cuáles no.
 * Version:     1.0.0
 * Author:      Vynk
 * Text Domain: product-weight-audit
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PWA_VERSION', '1.0.0' );
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

        $admin = new PWA_Admin_Page();
        $admin->init();
    }

    public function notice_woo_missing() {
        echo '<div class="notice notice-warning is-dismissible"><p><strong>Product Weight & Dimensions Audit</strong> requiere que WooCommerce esté activo e instalado.</p></div>';
    }
}

Product_Weight_Audit::instance();
