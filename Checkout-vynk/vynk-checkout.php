<?php
/**
 * Plugin Name: Vynk Checkout
 * Plugin URI:  https://vynk.us
 * Description: Custom single-page WooCommerce checkout with Vynk design system.
 * Version:     1.0.0
 * Author:      Vynk
 * Author URI:  https://vynk.us
 * Text Domain: vynk-checkout
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 9.9
 */

defined( 'ABSPATH' ) || exit;

define( 'VYNK_CHECKOUT_VERSION', '1.0.5' );
define( 'VYNK_CHECKOUT_DIR', plugin_dir_path( __FILE__ ) );
define( 'VYNK_CHECKOUT_URL', plugin_dir_url( __FILE__ ) );

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
                . esc_html__( 'Vynk Checkout requires WooCommerce to be active.', 'vynk-checkout' )
                . '</p></div>';
        } );
        return;
    }

    require_once VYNK_CHECKOUT_DIR . 'includes/class-vynk-settings.php';
    require_once VYNK_CHECKOUT_DIR . 'includes/class-vynk-mock-gateway.php';
    require_once VYNK_CHECKOUT_DIR . 'includes/class-vynk-checkout.php';

    Vynk_Settings::instance();
    Vynk_Checkout::instance();
} );
