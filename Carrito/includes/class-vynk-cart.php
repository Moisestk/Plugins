<?php
defined( 'ABSPATH' ) || exit;

/**
 * Clase principal del Vynk Sidebar Cart.
 * Gestiona assets, template, shortcode y fragmentos WooCommerce.
 */
class Vynk_Cart {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts',                array( $this, 'enqueue_assets' ),         20 );
		add_action( 'wp_enqueue_scripts',                array( $this, 'enqueue_dynamic_css' ),    30 );
		add_action( 'wp_footer',                         array( $this, 'render_sidebar_panel' ),   99 );
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_count_fragment' ),    10 );
		add_action( 'init',                              array( $this, 'register_shortcode' ),     10 );
		$this->disable_cartflows_cart();
	}

	/**
	 * Desactiva el carrito lateral/mini-cart de CartFlows para evitar conflictos.
	 */
	private function disable_cartflows_cart() {
		// Desactivar Side Cart de CartFlows Pro
		add_filter( 'cartflows_side_cart_enable',        '__return_false', 99 );
		add_filter( 'cartflows_enable_side_cart',        '__return_false', 99 );

		// Desactivar Mini Cart de CartFlows
		add_filter( 'cartflows_enable_mini_cart',        '__return_false', 99 );
		add_filter( 'cartflows_mini_cart_enable',        '__return_false', 99 );

		// Quitar scripts y estilos de CartFlows cart
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_cartflows_cart_assets' ), 100 );

		// Quitar el HTML del carrito de CartFlows del footer
		add_action( 'init', array( $this, 'remove_cartflows_cart_hooks' ), 20 );
	}

	/**
	 * Elimina los hooks de CartFlows que renderizan su carrito en el footer.
	 */
	public function remove_cartflows_cart_hooks() {
		// CartFlows Pro renderiza su side cart en wp_footer
		remove_action( 'wp_footer', array( 'CARTFLOWS_Side_Cart', 'render_side_cart' ),    10 );
		remove_action( 'wp_footer', array( 'Cartflows_Side_Cart', 'render_side_cart' ),    10 );
		remove_action( 'wp_footer', array( 'CartFlows_Side_Cart', 'render_side_cart' ),    10 );
		remove_action( 'wp_footer', 'cartflows_render_side_cart',                          10 );

		// CartFlows Mini Cart
		remove_action( 'wp_footer', array( 'CARTFLOWS_Mini_Cart', 'render_mini_cart' ),    10 );
		remove_action( 'wp_footer', array( 'Cartflows_Mini_Cart', 'render_mini_cart' ),    10 );
		remove_action( 'wp_footer', 'cartflows_render_mini_cart',                          10 );
	}

	/**
	 * Desencola los scripts y estilos del carrito de CartFlows.
	 */
	public function dequeue_cartflows_cart_assets() {
		$handles = array(
			'cartflows-side-cart',
			'cartflows-side-cart-style',
			'cartflows-mini-cart',
			'cartflows-mini-cart-style',
			'cartflows-cart',
			'cartflows-cart-style',
		);

		foreach ( $handles as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	/**
	 * Encola CSS y JS del plugin.
	 */
	public function enqueue_assets() {
		// Fuente Barlow de Google Fonts
		wp_enqueue_style(
			'vynk-font-barlow',
			'https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap',
			array(),
			null
		);

		// Estilos del plugin
		wp_enqueue_style(
			'vynk-sidebar-cart',
			VYNK_CART_URL . 'assets/css/vynk-sidebar-cart.css',
			array( 'vynk-font-barlow' ),
			VYNK_CART_VERSION
		);

		// Script del plugin (en footer, depende de jQuery)
		wp_enqueue_script(
			'vynk-sidebar-cart',
			VYNK_CART_URL . 'assets/js/vynk-sidebar-cart.js',
			array( 'jquery' ),
			VYNK_CART_VERSION,
			true
		);

		// Pasar datos al JS
		wp_localize_script(
			'vynk-sidebar-cart',
			'vynkSidebarCart',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'vynk_cart_nonce' ),
				'checkoutUrl' => wc_get_checkout_url(),
				'cartUrl'     => wc_get_cart_url(),
				'autoOpen'    => Vynk_Cart_Settings::get( 'auto_open' ) ? 'yes' : 'no',
				'i18n'        => array(
					'removing' => __( 'Eliminando...', 'vynk-sidebar-cart' ),
					'updating' => __( 'Actualizando...', 'vynk-sidebar-cart' ),
					'empty'    => __( 'Tu carrito está vacío', 'vynk-sidebar-cart' ),
				),
			)
		);
	}

	/**
	 * Inyecta CSS dinámico con el color de acento configurado en el admin.
	 */
	public function enqueue_dynamic_css() {
		$accent = Vynk_Cart_Settings::get( 'accent_color' );

		if ( ! $accent ) {
			return;
		}

		// Calcular variante oscura del acento (oscurecer ~15%)
		$accent_dark = $this->darken_hex_color( $accent, 15 );

		$css = "
			:root {
				--vynk-accent: {$accent};
				--vynk-accent-dark: {$accent_dark};
				--vynk-accent-soft: " . $this->hex_to_rgba( $accent, 0.08 ) . ";
			}
		";

		wp_add_inline_style( 'vynk-sidebar-cart', $css );
	}

	/**
	 * Renderiza el HTML del panel lateral en el footer.
	 */
	public function render_sidebar_panel() {
		require VYNK_CART_DIR . 'templates/sidebar-cart.php';
	}

	/**
	 * Actualiza el badge de conteo como fragmento WooCommerce.
	 *
	 * @param array $fragments
	 * @return array
	 */
	public function cart_count_fragment( $fragments ) {
		$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
		$fragments['.vynk-cart-count-badge'] = '<span class="vynk-cart-count-badge"' .
			( $count === 0 ? ' data-empty="true"' : '' ) .
			'>' . esc_html( $count ) . '</span>';
		return $fragments;
	}

	/**
	 * Registra el shortcode [vynk_cart_icon].
	 */
	public function register_shortcode() {
		add_shortcode( 'vynk_cart_icon', array( $this, 'render_cart_icon_shortcode' ) );
	}

	/**
	 * Callback del shortcode — renderiza el botón disparador del carrito.
	 *
	 * @return string
	 */
	public function render_cart_icon_shortcode() {
		$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

		ob_start();
		?>
		<button class="vynk-cart-trigger" aria-label="<?php esc_attr_e( 'Abrir carrito', 'vynk-sidebar-cart' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
				<line x1="3" y1="6" x2="21" y2="6"/>
				<path d="M16 10a4 4 0 0 1-8 0"/>
			</svg>
			<span class="vynk-cart-count-badge"<?php echo $count === 0 ? ' data-empty="true"' : ''; ?>>
				<?php echo esc_html( $count ); ?>
			</span>
		</button>
		<?php
		return ob_get_clean();
	}

	/**
	 * Oscurece un color hexadecimal un porcentaje dado.
	 *
	 * @param string $hex
	 * @param int    $percent
	 * @return string
	 */
	private function darken_hex_color( $hex, $percent ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = max( 0, hexdec( substr( $hex, 0, 2 ) ) - round( 255 * $percent / 100 ) );
		$g = max( 0, hexdec( substr( $hex, 2, 2 ) ) - round( 255 * $percent / 100 ) );
		$b = max( 0, hexdec( substr( $hex, 4, 2 ) ) - round( 255 * $percent / 100 ) );
		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}

	/**
	 * Convierte un color hex a rgba con opacidad.
	 *
	 * @param string $hex
	 * @param float  $alpha
	 * @return string
	 */
	private function hex_to_rgba( $hex, $alpha ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return "rgba({$r},{$g},{$b},{$alpha})";
	}
}
