<?php
defined( 'ABSPATH' ) || exit;

$panel_title            = Vynk_Cart_Settings::get( 'panel_title' ) ?: 'Tu Carrito';
$checkout_btn_text      = Vynk_Cart_Settings::get( 'checkout_btn_text' ) ?: 'Finalizar Compra';
$continue_shopping_text = Vynk_Cart_Settings::get( 'continue_shopping_text' ) ?: 'Seguir comprando';
$show_floating          = Vynk_Cart_Settings::get( 'show_floating_trigger' );
$trigger_position       = Vynk_Cart_Settings::get( 'trigger_position' ) ?: 'right';
$count                  = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$checkout_url           = wc_get_checkout_url();
$shop_url               = get_permalink( wc_get_page_id( 'shop' ) );
?>

<!-- Vynk Sidebar Cart -->
<div class="vynk-cart-overlay" id="vynk-cart-overlay" aria-hidden="true"></div>

<div class="vynk-cart-panel" id="vynk-cart-panel"
	role="dialog"
	aria-modal="true"
	aria-label="<?php esc_attr_e( 'Carrito de compras', 'vynk-sidebar-cart' ); ?>"
	aria-hidden="true">

	<!-- Header -->
	<div class="vynk-cart-panel-header">
		<h2 class="vynk-cart-panel-title">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
				<line x1="3" y1="6" x2="21" y2="6"/>
				<path d="M16 10a4 4 0 0 1-8 0"/>
			</svg>
			<?php echo esc_html( $panel_title ); ?>
			<span class="vynk-cart-count-badge" id="vynk-cart-badge"<?php echo $count === 0 ? ' data-empty="true"' : ''; ?>>
				<?php echo esc_html( $count ); ?>
			</span>
		</h2>
		<button class="vynk-cart-close" id="vynk-cart-close" aria-label="<?php esc_attr_e( 'Cerrar carrito', 'vynk-sidebar-cart' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<line x1="18" y1="6" x2="6" y2="18"/>
				<line x1="6" y1="6" x2="18" y2="18"/>
			</svg>
		</button>
	</div>

	<!-- Body: lista de items -->
	<div class="vynk-cart-panel-body" id="vynk-cart-body">

		<div class="vynk-cart-items" id="vynk-cart-items">
			<!-- Poblado por JavaScript -->
		</div>

		<!-- Estado vacío -->
		<div class="vynk-cart-empty-state" id="vynk-cart-empty" aria-hidden="true">
			<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
				<line x1="3" y1="6" x2="21" y2="6"/>
				<path d="M16 10a4 4 0 0 1-8 0"/>
			</svg>
			<p><?php esc_html_e( 'Tu carrito está vacío', 'vynk-sidebar-cart' ); ?></p>
			<a href="<?php echo esc_url( $shop_url ); ?>" class="vynk-cart-continue-link">
				<?php echo esc_html( $continue_shopping_text ); ?>
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<line x1="5" y1="12" x2="19" y2="12"/>
					<polyline points="12 5 19 12 12 19"/>
				</svg>
			</a>
		</div>

	</div>

	<!-- Footer: subtotal + CTA -->
	<div class="vynk-cart-panel-footer" id="vynk-cart-footer" style="display:none;">

		<div class="vynk-cart-subtotal-row">
			<span class="vynk-cart-subtotal-label"><?php esc_html_e( 'Subtotal', 'vynk-sidebar-cart' ); ?></span>
			<span class="vynk-cart-subtotal-amount" id="vynk-cart-subtotal">&mdash;</span>
		</div>

		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="vynk-cart-view-cart-link">
			<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
				<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
			</svg>
			<?php esc_html_e( 'Ver carrito', 'vynk-sidebar-cart' ); ?>
		</a>

		<a href="<?php echo esc_url( $checkout_url ); ?>"
			class="vynk-cart-checkout-btn"
			id="vynk-cart-cta">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
				<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
			</svg>
			<?php echo esc_html( $checkout_btn_text ); ?>
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<line x1="5" y1="12" x2="19" y2="12"/>
				<polyline points="12 5 19 12 12 19"/>
			</svg>
		</a>

		<p class="vynk-cart-secure-note">
			<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
			</svg>
			<?php esc_html_e( 'Pago seguro y encriptado', 'vynk-sidebar-cart' ); ?>
		</p>

	</div>

</div>

<?php if ( $show_floating ) : ?>
<!-- Botón flotante -->
<button class="vynk-cart-trigger vynk-cart-trigger--floating vynk-cart-trigger--<?php echo esc_attr( $trigger_position ); ?>"
	id="vynk-cart-trigger-floating"
	aria-label="<?php esc_attr_e( 'Abrir carrito', 'vynk-sidebar-cart' ); ?>">
	<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
		<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
		<line x1="3" y1="6" x2="21" y2="6"/>
		<path d="M16 10a4 4 0 0 1-8 0"/>
	</svg>
	<span class="vynk-cart-count-badge"<?php echo $count === 0 ? ' data-empty="true"' : ''; ?>>
		<?php echo esc_html( $count ); ?>
	</span>
</button>
<?php endif; ?>
