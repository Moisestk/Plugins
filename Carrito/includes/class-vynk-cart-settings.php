<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gestiona los ajustes del plugin Vynk Sidebar Cart.
 */
class Vynk_Cart_Settings {

	private static $instance = null;
	const OPTION_KEY = 'vynk_sidebar_cart_options';

	private static $defaults = array(
		'accent_color'           => '#bf1f1f',
		'auto_open'              => true,
		'show_floating_trigger'  => true,
		'trigger_position'       => 'right',
		'panel_title'            => 'Tu Carrito',
		'checkout_btn_text'      => 'Finalizar Compra',
		'continue_shopping_text' => 'Seguir comprando',
	);

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Obtiene un valor de ajuste (o todos si no se pasa clave).
	 *
	 * @param string|null $key
	 * @return mixed
	 */
	public static function get( $key = null ) {
		$saved   = get_option( self::OPTION_KEY, array() );
		$options = wp_parse_args( $saved, self::$defaults );

		if ( null === $key ) {
			return $options;
		}

		return isset( $options[ $key ] ) ? $options[ $key ] : null;
	}

	public function add_menu_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Vynk Sidebar Cart', 'vynk-sidebar-cart' ),
			__( 'Sidebar Cart', 'vynk-sidebar-cart' ),
			'manage_options',
			'vynk-sidebar-cart',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'vynk_sidebar_cart_group',
			self::OPTION_KEY,
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'vynk_cart_general',
			__( 'Configuración General', 'vynk-sidebar-cart' ),
			null,
			'vynk-sidebar-cart'
		);

		$fields = array(
			array( 'id' => 'accent_color',           'label' => 'Color de Acento',           'type' => 'color'    ),
			array( 'id' => 'auto_open',              'label' => 'Abrir al agregar producto',  'type' => 'checkbox' ),
			array( 'id' => 'show_floating_trigger',  'label' => 'Mostrar botón flotante',     'type' => 'checkbox' ),
			array( 'id' => 'trigger_position',       'label' => 'Posición del botón flotante','type' => 'select',
				'options' => array( 'right' => 'Derecha', 'left' => 'Izquierda' ) ),
			array( 'id' => 'panel_title',            'label' => 'Título del panel',           'type' => 'text'     ),
			array( 'id' => 'checkout_btn_text',      'label' => 'Texto botón checkout',       'type' => 'text'     ),
			array( 'id' => 'continue_shopping_text', 'label' => 'Texto "Seguir comprando"',   'type' => 'text'     ),
		);

		foreach ( $fields as $field ) {
			add_settings_field(
				$field['id'],
				__( $field['label'], 'vynk-sidebar-cart' ),
				array( $this, 'render_field' ),
				'vynk-sidebar-cart',
				'vynk_cart_general',
				$field
			);
		}
	}

	public function render_field( $args ) {
		$options = self::get();
		$id      = esc_attr( $args['id'] );
		$value   = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : '';
		$name    = self::OPTION_KEY . '[' . $id . ']';

		switch ( $args['type'] ) {
			case 'color':
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="vynk-color-picker" data-default-color="%s" />',
					$id,
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( self::$defaults[ $args['id'] ] )
				);
				break;

			case 'checkbox':
				printf(
					'<input type="checkbox" id="%s" name="%s" value="1" %s />',
					$id,
					esc_attr( $name ),
					checked( $value, true, false )
				);
				break;

			case 'select':
				echo '<select id="' . $id . '" name="' . esc_attr( $name ) . '">';
				foreach ( $args['options'] as $val => $label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $val ),
						selected( $value, $val, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				break;

			default:
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="regular-text" />',
					$id,
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;
		}
	}

	public function sanitize_options( $input ) {
		$clean = array();

		$clean['accent_color'] = isset( $input['accent_color'] )
			? sanitize_hex_color( $input['accent_color'] )
			: self::$defaults['accent_color'];

		$clean['auto_open']             = ! empty( $input['auto_open'] );
		$clean['show_floating_trigger'] = ! empty( $input['show_floating_trigger'] );

		$clean['trigger_position'] = in_array( $input['trigger_position'] ?? '', array( 'right', 'left' ), true )
			? $input['trigger_position']
			: 'right';

		$clean['panel_title']            = sanitize_text_field( $input['panel_title'] ?? '' );
		$clean['checkout_btn_text']      = sanitize_text_field( $input['checkout_btn_text'] ?? '' );
		$clean['continue_shopping_text'] = sanitize_text_field( $input['continue_shopping_text'] ?? '' );

		return $clean;
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( 'woocommerce_page_vynk-sidebar-cart' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', '
			jQuery(function($) {
				$(".vynk-color-picker").wpColorPicker();
			});
		' );
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Vynk Sidebar Cart — Ajustes', 'vynk-sidebar-cart' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'vynk_sidebar_cart_group' );
				do_settings_sections( 'vynk-sidebar-cart' );
				submit_button( __( 'Guardar Cambios', 'vynk-sidebar-cart' ) );
				?>
			</form>
			<hr>
			<h2><?php esc_html_e( 'Uso del Shortcode', 'vynk-sidebar-cart' ); ?></h2>
			<p><?php esc_html_e( 'Agrega el ícono del carrito en cualquier parte de tu tema con:', 'vynk-sidebar-cart' ); ?></p>
			<code>[vynk_cart_icon]</code>
		</div>
		<?php
	}
}
