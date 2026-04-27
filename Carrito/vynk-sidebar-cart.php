<?php
/**
 * Plugin Name:  Vynk Sidebar Cart
 * Plugin URI:   https://vynk.us
 * Description:  Carrito lateral moderno con deslizamiento para WooCommerce. Se abre automáticamente al agregar productos con controles AJAX de cantidad y eliminación.
 * Version:      1.0.0
 * Author:       Vynk
 * Author URI:   https://vynk.us
 * Text Domain:  vynk-sidebar-cart
 * Domain Path:  /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 9.9
 */

defined( 'ABSPATH' ) || exit;

define( 'VYNK_CART_VERSION', '1.0.0' );
define( 'VYNK_CART_DIR',     plugin_dir_path( __FILE__ ) );
define( 'VYNK_CART_URL',     plugin_dir_url( __FILE__ ) );

/**
 * Declarar compatibilidad con High-Performance Order Storage (HPOS).
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
 * Inicializar el plugin solo si WooCommerce está activo.
 */
add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Vynk Sidebar Cart requiere que WooCommerce esté instalado y activo.', 'vynk-sidebar-cart' );
			echo '</p></div>';
		} );
		return;
	}

	require_once VYNK_CART_DIR . 'includes/class-vynk-cart-settings.php';
	require_once VYNK_CART_DIR . 'includes/class-vynk-cart-ajax.php';
	require_once VYNK_CART_DIR . 'includes/class-vynk-cart.php';

	Vynk_Cart_Settings::instance();
	Vynk_Cart_Ajax::instance();
	Vynk_Cart::instance();
} );
