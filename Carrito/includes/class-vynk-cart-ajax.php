<?php
defined( 'ABSPATH' ) || exit;

/**
 * Maneja todos los endpoints WC AJAX del carrito lateral.
 */
class Vynk_Cart_Ajax {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wc_ajax_vynk_get_cart',    array( $this, 'get_cart' ) );
		add_action( 'wc_ajax_vynk_update_qty',  array( $this, 'update_qty' ) );
		add_action( 'wc_ajax_vynk_remove_item', array( $this, 'remove_item' ) );
	}

	/**
	 * Devuelve los datos completos del carrito en formato JSON.
	 */
	public function get_cart() {
		check_ajax_referer( 'vynk_cart_nonce', 'nonce' );

		if ( ! WC()->cart ) {
			wp_send_json_error( array( 'message' => 'Cart not initialized' ) );
		}

		wp_send_json_success( $this->build_cart_data() );
	}

	/**
	 * Actualiza la cantidad de un item del carrito.
	 */
	public function update_qty() {
		check_ajax_referer( 'vynk_cart_nonce', 'nonce' );

		if ( ! WC()->cart ) {
			wp_send_json_error( array( 'message' => 'Cart not initialized' ) );
		}

		$key = sanitize_text_field( $_POST['key'] ?? '' );
		$qty = max( 0, intval( $_POST['qty'] ?? 0 ) );

		if ( empty( $key ) ) {
			wp_send_json_error( array( 'message' => 'Invalid cart item key' ) );
		}

		if ( $qty <= 0 ) {
			WC()->cart->remove_cart_item( $key );
		} else {
			WC()->cart->set_quantity( $key, $qty );
		}

		WC()->cart->calculate_totals();

		wp_send_json_success( $this->build_cart_data() );
	}

	/**
	 * Elimina un item del carrito.
	 */
	public function remove_item() {
		check_ajax_referer( 'vynk_cart_nonce', 'nonce' );

		if ( ! WC()->cart ) {
			wp_send_json_error( array( 'message' => 'Cart not initialized' ) );
		}

		$key = sanitize_text_field( $_POST['key'] ?? '' );

		if ( empty( $key ) ) {
			wp_send_json_error( array( 'message' => 'Invalid cart item key' ) );
		}

		WC()->cart->remove_cart_item( $key );
		WC()->cart->calculate_totals();

		wp_send_json_success( $this->build_cart_data() );
	}

	/**
	 * Construye el array de datos del carrito para la respuesta JSON.
	 *
	 * @return array
	 */
	private function build_cart_data() {
		$cart  = WC()->cart;
		$items = array();

		foreach ( $cart->get_cart() as $key => $cart_item ) {
			/** @var WC_Product $product */
			$product = $cart_item['data'];

			if ( ! $product || ! $product->exists() ) {
				continue;
			}

			$image_id  = $product->get_image_id();
			$image_url = $image_id
				? wp_get_attachment_image_url( $image_id, 'thumbnail' )
				: wc_placeholder_img_src( 'thumbnail' );

			$items[] = array(
				'key'        => $key,
				'name'       => $product->get_name(),
				'permalink'  => $product->get_permalink(),
				'image'      => $image_url,
				'price'      => WC()->cart->get_product_price( $product ),
				'subtotal'   => WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ),
				'qty'        => $cart_item['quantity'],
				'min'        => 1,
				'max'        => $product->get_max_purchase_quantity(),
				'attributes' => wc_get_formatted_cart_item_data( $cart_item, true ),
			);
		}

		return array(
			'items'    => $items,
			'count'    => $cart->get_cart_contents_count(),
			'subtotal' => $cart->get_cart_subtotal(),
			'isEmpty'  => $cart->is_empty(),
		);
	}
}
