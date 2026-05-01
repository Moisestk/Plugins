<?php
/**
 * Vynk Checkout — Modern accordion single-page checkout.
 * @package Vynk_Checkout
 */
defined( 'ABSPATH' ) || exit;

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Debes iniciar sesión para finalizar la compra.', 'vynk-checkout' ) ) );
    return;
}

$opts             = Vynk_Settings::get();
$logo_id          = absint( $opts['logo_id'] );
$show_coupon      = (bool) $opts['show_coupon'];
$page_title       = $opts['page_title'];
$has_shipping     = WC()->cart->needs_shipping();
$cart_count       = WC()->cart->get_cart_contents_count();
?>

<?php
$_nav_logo = absint( $opts['logo_id'] );
?>
<nav class="vynk-topnav" aria-label="Navegación">
    <div class="vynk-topnav-inner">
        <div class="vynk-topnav-left">
            <a href="<?php echo esc_url( home_url( '/carrito' ) ); ?>" class="vynk-topnav-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                <span><?php esc_html_e( 'Volver al carrito', 'vynk-checkout' ); ?></span>
            </a>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="vynk-topnav-home">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span><?php esc_html_e( 'Inicio', 'vynk-checkout' ); ?></span>
            </a>
        </div>

        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="vynk-topnav-logo">
            <img src="<?php echo esc_url( VYNK_CHECKOUT_URL . 'assets/img/vynk-logo-web.webp' ); ?>"
                 alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
        </a>

        <div class="vynk-topnav-right">
            <div class="vynk-nav-payment-methods">
                <span class="vynk-nav-payment-label"><?php esc_html_e( 'ACEPTAMOS', 'vynk-checkout' ); ?></span>
                <div class="vynk-payment-icons">
                    <img src="<?php echo esc_url( VYNK_CHECKOUT_URL . 'assets/img/zelle.png' ); ?>" alt="Zelle" class="vynk-pay-img" title="Zelle">
                    <img src="<?php echo esc_url( VYNK_CHECKOUT_URL . 'assets/img/binance.png' ); ?>" alt="Binance Pay" class="vynk-pay-img" title="Binance Pay">
                    <img src="<?php echo esc_url( VYNK_CHECKOUT_URL . 'assets/img/paypal.png' ); ?>" alt="PayPal" class="vynk-pay-img" title="PayPal">
                </div>
            </div>
        </div>
    </div>
</nav>

<?php do_action( 'woocommerce_before_checkout_form', $checkout ); ?>

<form name="checkout" method="post"
      class="checkout woocommerce-checkout"
      action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
      enctype="multipart/form-data">

    <div class="vynk-checkout-wrapper">

        <?php if ( $logo_id || $page_title ) : ?>
        <div class="vynk-checkout-branding">
            <?php if ( $logo_id ) : ?>
                <div class="vynk-checkout-logo">
                    <?php echo wp_get_attachment_image( $logo_id, 'medium', false, [ 'alt' => get_bloginfo('name') ] ); ?>
                </div>
            <?php endif; ?>
            <?php if ( $page_title ) : ?>
                <h1 class="vynk-checkout-page-title"><?php echo esc_html( $page_title ); ?></h1>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ═══════════════════ LEFT COLUMN ═══════════════════ -->
        <div class="vynk-checkout-main">

            <!-- Zona de avisos de WC (cupón, etc.) — posicionada dentro de la columna principal -->
            <div class="woocommerce-notices-wrapper"></div>

            <!-- PASO 1: Envío — dirección + método juntos -->
            <?php if ( $has_shipping ) : ?>
            <div class="vynk-accordion-card is-open" id="vynk-step-shipping">
                <button type="button" class="vynk-accordion-header" aria-expanded="true" aria-controls="vynk-step-shipping-body">
                    <span class="vynk-step-num">1</span>
                    <span class="vynk-accordion-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <?php esc_html_e( 'Envío', 'vynk-checkout' ); ?>
                    </span>
                    <span class="vynk-accordion-summary"></span>
                    <svg class="vynk-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="vynk-accordion-body" id="vynk-step-shipping-body">

                    <!-- Siempre usar dirección de envío separada para calcular zonas correctamente -->
                    <input type="hidden" name="ship_to_different_address" value="1">

                    <!-- País de envío fijo: Venezuela -->
                    <input type="hidden" id="shipping_country" name="shipping_country" value="VE">

                    <div class="vynk-shipping-address-fields">
                        <?php
                        foreach ( WC()->checkout()->get_checkout_fields( 'shipping' ) as $key => $field ) {
                            if ( 'shipping_country' === $key ) {
                                ?>
                                <p class="form-row form-row-wide address-field">
                                    <label><?php esc_html_e( 'País / Región', 'woocommerce' ); ?></label>
                                    <span class="woocommerce-input-wrapper">
                                        <span class="vynk-country-fixed-value">Venezuela</span>
                                    </span>
                                </p>
                                <?php
                                continue;
                            }
                            woocommerce_form_field( $key, $field, WC()->checkout()->get_value( $key ) );
                        }
                        ?>
                    </div>

                    <!-- Divisor -->
                    <div class="vynk-shipping-section-divider">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <span><?php esc_html_e( 'Tipo de Envío', 'vynk-checkout' ); ?></span>
                    </div>

                    <!-- Métodos de envío (se actualiza cuando cambia país/estado) -->
                    <div id="vynk-shipping-methods-inner" class="vynk-shipping-cards-wrap">
                        <?php Vynk_Checkout::instance()->render_shipping_methods(); ?>
                    </div>

                </div>
            </div>
            <?php endif; ?>

            <!-- PASO: Contacto y Facturación -->
            <?php $step_num = $has_shipping ? 2 : 1; ?>
            <div class="vynk-accordion-card" id="vynk-step-1">
                <button type="button" class="vynk-accordion-header" aria-expanded="false" aria-controls="vynk-step-1-body">
                    <span class="vynk-step-num"><?php echo esc_html( $step_num ); ?></span>
                    <span class="vynk-accordion-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <?php esc_html_e( 'Contacto y Facturación', 'vynk-checkout' ); ?>
                    </span>
                    <span class="vynk-accordion-summary"></span>
                    <svg class="vynk-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="vynk-accordion-body" id="vynk-step-1-body">
                    <?php do_action( 'woocommerce_checkout_billing' ); ?>
                </div>
            </div>

            <!-- PASO: Pago -->
            <?php $step_num++; ?>
            <div class="vynk-accordion-card" id="vynk-step-payment">
                <button type="button" class="vynk-accordion-header" aria-expanded="false" aria-controls="vynk-step-payment-body">
                    <span class="vynk-step-num"><?php echo esc_html( $step_num ); ?></span>
                    <span class="vynk-accordion-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        <?php esc_html_e( 'Pago', 'vynk-checkout' ); ?>
                    </span>
                    <span class="vynk-payment-secure-badge">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <?php esc_html_e( 'Seguro', 'vynk-checkout' ); ?>
                    </span>
                    <svg class="vynk-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="vynk-accordion-body" id="vynk-step-payment-body">
                    <?php woocommerce_checkout_payment(); ?>
                </div>
            </div>

        </div>
        <!-- /.vynk-checkout-main -->

        <!-- ═══════════════════ RIGHT SIDEBAR ═══════════════════ -->
        <div class="vynk-checkout-sidebar">
            <div class="vynk-summary-card">

                <!-- Encabezado -->
                <div class="vynk-summary-header">
                    <div class="vynk-summary-title">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <?php esc_html_e( 'Resumen del Pedido', 'vynk-checkout' ); ?>
                    </div>
                    <span class="vynk-cart-count"><?php echo esc_html( $cart_count . ' ' . _n('artículo','artículos', $cart_count, 'vynk-checkout') ); ?></span>
                </div>

                <!-- Productos -->
                <div class="vynk-summary-items">
                    <?php foreach ( WC()->cart->get_cart() as $key => $item ) :
                        $_product = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );
                        if ( ! $_product || ! $_product->exists() || $item['quantity'] <= 0 ) continue;
                    ?>
                    <div class="vynk-summary-item">
                        <div class="vynk-summary-item-img">
                            <?php echo apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image('thumbnail'), $item, $key ); // phpcs:ignore ?>
                            <span class="vynk-item-qty-badge"><?php echo esc_html( $item['quantity'] ); ?></span>
                        </div>
                        <div class="vynk-summary-item-info">
                            <span class="vynk-summary-item-name"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $item, $key ) ); ?></span>
                            <?php echo wc_get_formatted_cart_item_data( $item ); // phpcs:ignore ?>
                        </div>
                        <span class="vynk-summary-item-price"><?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $item['quantity']), $item, $key ); // phpcs:ignore ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>


                <!-- Cupón (dentro del sidebar) -->
                <?php if ( $show_coupon ) : ?>
                <div class="vynk-coupon-area">
                    <button type="button" id="vynk-coupon-toggle" class="vynk-coupon-toggle-btn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        <span><?php esc_html_e( '¿Tienes un código de cupón?', 'vynk-checkout' ); ?></span>
                        <svg class="vynk-coupon-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div id="vynk-coupon-form" style="display:none;">
                        <?php
                        /*
                         * IMPORTANTE: NO usar woocommerce_checkout_coupon_form() aquí.
                         * Ese helper renderiza un <form> anidado dentro del <form> de checkout,
                         * lo cual es HTML inválido. El navegador ignora el <form> interno y el
                         * botón "Aplicar" queda como submit del formulario de checkout, creando
                         * pedidos accidentales al pulsar "Aplicar cupón".
                         *
                         * Solución: implementación propia con type="button" + AJAX.
                         */
                        ?>
                        <div class="vynk-coupon-inner">
                            <div class="vynk-coupon-row">
                                <input type="text"
                                       id="vynk-coupon-code"
                                       class="vynk-coupon-input"
                                       placeholder="<?php esc_attr_e( 'Código de cupón', 'vynk-checkout' ); ?>"
                                       autocomplete="off">
                                <button type="button" id="vynk-coupon-apply" class="vynk-coupon-btn">
                                    <?php esc_html_e( 'Aplicar', 'vynk-checkout' ); ?>
                                </button>
                            </div>
                            <div id="vynk-coupon-msg" aria-live="polite"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Totales (fragmento WC para actualizaciones en tiempo real) -->
                <?php Vynk_Checkout::instance()->render_totals(); ?>

                <!-- Métodos de pago aceptados -->
                <div class="vynk-summary-payment-methods">
                    <span class="vynk-summary-payment-label"><?php esc_html_e( 'MÉTODOS DE PAGO ACEPTADOS', 'vynk-checkout' ); ?></span>
                    <div class="vynk-payment-icons">
                        <img src="<?php echo esc_url( VYNK_CHECKOUT_URL . 'assets/img/zelle.png' ); ?>" alt="Zelle" class="vynk-pay-img" title="Zelle">
                        <img src="<?php echo esc_url( VYNK_CHECKOUT_URL . 'assets/img/binance.png' ); ?>" alt="Binance Pay" class="vynk-pay-img" title="Binance Pay">
                        <img src="<?php echo esc_url( VYNK_CHECKOUT_URL . 'assets/img/paypal.png' ); ?>" alt="PayPal" class="vynk-pay-img" title="PayPal">
                    </div>
                </div>

            </div>
        </div>
        <!-- /.vynk-checkout-sidebar -->

    </div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
