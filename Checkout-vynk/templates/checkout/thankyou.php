<?php
/**
 * Vynk Checkout — Thank You / Order Received page.
 * @package Vynk_Checkout
 */
defined( 'ABSPATH' ) || exit;

$opts    = Vynk_Settings::get();
$logo_id = absint( $opts['logo_id'] );
?>

<nav class="vynk-topnav" aria-label="Navegación">
    <div class="vynk-topnav-inner">
        <a href="<?php echo esc_url( home_url( '/tienda' ) ); ?>" class="vynk-topnav-back">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            <span><?php esc_html_e( 'Seguir comprando', 'vynk-checkout' ); ?></span>
        </a>

        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="vynk-topnav-logo">
            <img src="<?php echo esc_url( VYNK_CHECKOUT_URL . 'assets/img/vynk-logo-web.webp' ); ?>"
                 alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
        </a>

        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="vynk-topnav-home">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span><?php esc_html_e( 'Inicio', 'vynk-checkout' ); ?></span>
        </a>
    </div>
</nav>

<div class="vynk-thankyou-wrapper">

<?php if ( $order ) :

    $status       = $order->get_status();
    $is_failed    = ( 'failed' === $status );
    $billing_name = $order->get_billing_first_name();

?>

    <!-- ── Hero ─────────────────────────────────────────────── -->
    <div class="vynk-thankyou-hero <?php echo $is_failed ? 'vynk-thankyou-hero--failed' : ''; ?>">
        <div class="vynk-thankyou-icon">
            <?php if ( $is_failed ) : ?>
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            <?php else : ?>
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?php endif; ?>
        </div>

        <?php if ( $is_failed ) : ?>
            <h1 class="vynk-thankyou-title"><?php esc_html_e( 'El pago no se completó', 'vynk-checkout' ); ?></h1>
            <p class="vynk-thankyou-subtitle"><?php esc_html_e( 'Lamentablemente tu pedido no pudo procesarse. Puedes intentarlo nuevamente.', 'vynk-checkout' ); ?></p>
        <?php else : ?>
            <h1 class="vynk-thankyou-title">
                <?php if ( $billing_name ) :
                    printf( esc_html__( '¡Gracias, %s!', 'vynk-checkout' ), esc_html( $billing_name ) );
                else :
                    esc_html_e( '¡Gracias por tu pedido!', 'vynk-checkout' );
                endif; ?>
            </h1>
            <p class="vynk-thankyou-subtitle">
                <?php printf(
                    esc_html__( 'Tu pedido #%s fue confirmado. Recibirás un correo en %s con los detalles.', 'vynk-checkout' ),
                    '<strong>' . esc_html( $order->get_order_number() ) . '</strong>',
                    '<strong>' . esc_html( $order->get_billing_email() ) . '</strong>'
                ); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- ── Info bar ──────────────────────────────────────────── -->
    <div class="vynk-thankyou-infobar">
        <div class="vynk-thankyou-info-item">
            <span class="vynk-thankyou-info-label">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                <?php esc_html_e( 'Pedido', 'vynk-checkout' ); ?>
            </span>
            <span class="vynk-thankyou-info-value">#<?php echo esc_html( $order->get_order_number() ); ?></span>
        </div>
        <div class="vynk-thankyou-info-item">
            <span class="vynk-thankyou-info-label">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?php esc_html_e( 'Fecha', 'vynk-checkout' ); ?>
            </span>
            <span class="vynk-thankyou-info-value"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
        </div>
        <div class="vynk-thankyou-info-item">
            <span class="vynk-thankyou-info-label">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?php esc_html_e( 'Estado', 'vynk-checkout' ); ?>
            </span>
            <span class="vynk-thankyou-info-value vynk-order-status vynk-order-status--<?php echo esc_attr( $status ); ?>">
                <?php echo esc_html( wc_get_order_status_name( $status ) ); ?>
            </span>
        </div>
        <div class="vynk-thankyou-info-item">
            <span class="vynk-thankyou-info-label">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                <?php esc_html_e( 'Pago', 'vynk-checkout' ); ?>
            </span>
            <span class="vynk-thankyou-info-value"><?php echo esc_html( $order->get_payment_method_title() ); ?></span>
        </div>
    </div>

    <!-- ── Two-column grid ───────────────────────────────────── -->
    <div class="vynk-thankyou-grid">

        <!-- LEFT: productos + dirección -->
        <div class="vynk-thankyou-main">

            <!-- Productos -->
            <div class="vynk-thankyou-card">
                <div class="vynk-thankyou-card-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <?php esc_html_e( 'Artículos del Pedido', 'vynk-checkout' ); ?>
                </div>
                <div class="vynk-thankyou-items">
                    <?php foreach ( $order->get_items() as $item_id => $item ) :
                        $product   = $item->get_product();
                        $thumbnail = $product ? $product->get_image( 'thumbnail' ) : '';
                    ?>
                    <div class="vynk-thankyou-item">
                        <?php if ( $thumbnail ) : ?>
                        <div class="vynk-thankyou-item-img">
                            <?php echo $thumbnail; // phpcs:ignore ?>
                            <span class="vynk-item-qty-badge"><?php echo esc_html( $item->get_quantity() ); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="vynk-thankyou-item-info">
                            <span class="vynk-thankyou-item-name"><?php echo wp_kses_post( $item->get_name() ); ?></span>
                            <?php
                            // Variation data
                            $meta_data = $item->get_formatted_meta_data( '_', true );
                            foreach ( $meta_data as $meta ) :
                                echo '<span class="vynk-thankyou-item-meta">'
                                    . wp_kses_post( $meta->display_key ) . ': '
                                    . wp_kses_post( $meta->display_value )
                                    . '</span>';
                            endforeach;
                            ?>
                        </div>
                        <span class="vynk-thankyou-item-price"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dirección de envío -->
            <?php $shipping_addr = $order->get_formatted_shipping_address(); ?>
            <?php if ( $shipping_addr ) : ?>
            <div class="vynk-thankyou-card">
                <div class="vynk-thankyou-card-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?php esc_html_e( 'Dirección de Envío', 'vynk-checkout' ); ?>
                </div>
                <address class="vynk-thankyou-address">
                    <?php echo wp_kses_post( $shipping_addr ); ?>
                </address>
            </div>
            <?php endif; ?>

            <!-- CTA -->
            <div class="vynk-thankyou-actions">
                <?php if ( $is_failed ) : ?>
                    <a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="vynk-thankyou-retry-btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.29"/></svg>
                        <?php esc_html_e( 'Reintentar Pago', 'vynk-checkout' ); ?>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="vynk-thankyou-shop-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <?php esc_html_e( 'Seguir Comprando', 'vynk-checkout' ); ?>
                </a>
            </div>

        </div>
        <!-- /.vynk-thankyou-main -->

        <!-- RIGHT: resumen de totales -->
        <div class="vynk-thankyou-sidebar">
            <div class="vynk-thankyou-card vynk-thankyou-totals-card">
                <div class="vynk-thankyou-card-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <?php esc_html_e( 'Resumen del Pedido', 'vynk-checkout' ); ?>
                </div>
                <div class="vynk-thankyou-totals-body">

                    <div class="vynk-totals-row">
                        <span><?php esc_html_e( 'Subtotal', 'vynk-checkout' ); ?></span>
                        <span><?php echo wp_kses_post( wc_price( $order->get_subtotal() ) ); ?></span>
                    </div>

                    <?php foreach ( $order->get_coupons() as $coupon ) : ?>
                    <div class="vynk-totals-row vynk-totals-discount">
                        <span><?php printf( esc_html__( 'Cupón: %s', 'vynk-checkout' ), '<em>' . esc_html( $coupon->get_code() ) . '</em>' ); ?></span>
                        <span class="vynk-discount">-<?php echo wp_kses_post( wc_price( $coupon->get_discount() ) ); ?></span>
                    </div>
                    <?php endforeach; ?>

                    <?php foreach ( $order->get_items( 'fee' ) as $fee ) : ?>
                    <div class="vynk-totals-row">
                        <span><?php echo esc_html( $fee->get_name() ); ?></span>
                        <span><?php echo wp_kses_post( wc_price( $fee->get_total() ) ); ?></span>
                    </div>
                    <?php endforeach; ?>

                    <?php if ( (float) $order->get_shipping_total() > 0 ) : ?>
                    <div class="vynk-totals-row">
                        <span><?php esc_html_e( 'Envío', 'vynk-checkout' ); ?></span>
                        <span><?php echo wp_kses_post( wc_price( $order->get_shipping_total() ) ); ?></span>
                    </div>
                    <?php elseif ( $order->get_shipping_method() ) : ?>
                    <div class="vynk-totals-row">
                        <span><?php esc_html_e( 'Envío', 'vynk-checkout' ); ?></span>
                        <span><?php esc_html_e( 'Gratis', 'vynk-checkout' ); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ( wc_tax_enabled() && (float) $order->get_total_tax() > 0 ) : ?>
                    <div class="vynk-totals-row">
                        <span><?php esc_html_e( 'Impuesto', 'vynk-checkout' ); ?></span>
                        <span><?php echo wp_kses_post( wc_price( $order->get_total_tax() ) ); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="vynk-totals-row vynk-totals-grand">
                        <span><?php esc_html_e( 'Total', 'vynk-checkout' ); ?></span>
                        <span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
                    </div>

                </div>
            </div>
        </div>
        <!-- /.vynk-thankyou-sidebar -->

    </div>
    <!-- /.vynk-thankyou-grid -->

<?php else : ?>

    <div class="vynk-thankyou-card" style="text-align:center;padding:48px 24px;">
        <p style="font-size:15px;color:var(--vynk-secondary);"><?php esc_html_e( 'Gracias por tu compra.', 'vynk-checkout' ); ?></p>
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="vynk-thankyou-shop-btn" style="margin-top:20px;display:inline-flex;">
            <?php esc_html_e( 'Ir a la Tienda', 'vynk-checkout' ); ?>
        </a>
    </div>

<?php endif; ?>

</div>
<!-- /.vynk-thankyou-wrapper -->

<?php
// Payment gateway hooks on woocommerce_thankyou are intentionally NOT fired here.
// Third-party gateways (e.g. Zelle) that hook into woocommerce_thankyou_{gateway}
// output their own payment instruction blocks which conflict with our custom layout.
// All order info (items, address, totals, status) is already rendered above.
?>
