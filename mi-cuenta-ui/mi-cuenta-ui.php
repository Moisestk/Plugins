<?php
/**
 * Plugin Name: Mi Cuenta UI
 * Plugin URI:  https://vynk.us
 * Description: Custom WooCommerce My Account dashboard with Vynk design system.
 * Version:     1.0.0
 * Author:      Vynk
 * Author URI:  https://vynk.us
 * Text Domain: mi-cuenta-ui
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 9.9
 */

defined( 'ABSPATH' ) || exit;

define( 'MI_CUENTA_VERSION', '1.0.0' );
define( 'MI_CUENTA_DIR', plugin_dir_path( __FILE__ ) );
define( 'MI_CUENTA_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare WooCommerce HPOS compatibility.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
} );

/**
 * Bootstrap the plugin after WooCommerce is loaded.
 */
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>'
                . esc_html__( 'Mi Cuenta UI requires WooCommerce to be active.', 'mi-cuenta-ui' )
                . '</p></div>';
        } );
        return;
    }

    require_once MI_CUENTA_DIR . 'includes/class-mi-cuenta-ui.php';

    Mi_Cuenta_UI::instance();
} );
