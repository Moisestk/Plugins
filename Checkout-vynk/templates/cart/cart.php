<?php
/**
 * Vynk Cart — Custom cart page template.
 * @package Vynk_Checkout
 */
defined( 'ABSPATH' ) || exit;

$opts          = Vynk_Settings::get();
$logo_id       = absint( $opts['logo_id'] );
$show_coupon   = (bool) $opts['show_coupon'];
$cart_count    = WC()->cart->get_cart_contents_count();
$cart_is_empty = WC()->cart->is_empty();

do_action( 'woocommerce_before_cart' );

if ( $logo_id ) : ?>
<div class="vynk-cart-branding">
    <?php echo wp_get_attachment_image( $logo_id, 'medium', false, [ 'alt' => get_bloginfo('name') ] ); ?>
</div>
<?php endif; ?>

<?php if ( $cart_is_empty ) : ?>
<div class="vynk-cart-card">
    <div class="vynk-cart-empty">
        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <h3><?php esc_html_e( 'Tu carrito está vacío', 'vynk-checkout' ); ?></h3>
        <p><?php esc_html_e( 'Parece que aún no has agregado nada.', 'vynk-checkout' ); ?></p>
        <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop') ) ); ?>">
            <?php esc_html_e( 'Seguir comprando', 'vynk-checkout' ); ?>
        </a>
    </div>
</div>
<?php do_action( 'woocommerce_after_cart' ); return; endif; ?>

<form class="woocommerce-cart-form vynk-cart-wrapper" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

    <!-- ═══ LEFT: Cart items ═══ -->
    <div class="vynk-cart-main">
        <div class="vynk-cart-card">
            <div class="vynk-cart-card-header">
                <div class="vynk-cart-card-title">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <?php printf( esc_html( _n( '%d artículo', '%d artículos', $cart_count, 'vynk-checkout' ) ), $cart_count ); ?>
                </div>
                <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop') ) ); ?>" class="vynk-cart-continue">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    <?php esc_html_e( 'Seguir comprando', 'vynk-checkout' ); ?>
                </a>
            </div>

            <div class="vynk-cart-items-list">
                <?php do_action( 'woocommerce_before_cart_contents' ); ?>

                <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
                    $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                    $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

                    if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) continue;
                    if ( ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) continue;

                    $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key );
                    $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image('thumbnail'), $cart_item, $cart_item_key );
                    $product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key );
                    $product_subtotal  = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key );
                ?>
                <div class="vynk-cart-row <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

                    <!-- Imagen -->
                    <div class="vynk-cart-row-img">
                        <?php if ( ! $product_permalink ) : ?>
                            <?php echo $thumbnail; // phpcs:ignore ?>
                        <?php else : ?>
                            <a href="<?php echo esc_url($product_permalink); ?>"><?php echo $thumbnail; // phpcs:ignore ?></a>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="vynk-cart-row-info">
                        <?php if ( ! $product_permalink ) : ?>
                            <span class="vynk-cart-row-name"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?></span>
                        <?php else : ?>
                            <a href="<?php echo esc_url($product_permalink); ?>" class="vynk-cart-row-name">
                                <?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
                            </a>
                        <?php endif; ?>

                        <?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore ?>

                        <div class="vynk-cart-row-meta"><?php echo $product_price; // phpcs:ignore ?> <?php esc_html_e( 'c/u', 'vynk-checkout' ); ?></div>

                        <a href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>"
                           class="vynk-cart-row-remove"
                           aria-label="<?php esc_attr_e( 'Eliminar', 'vynk-checkout' ); ?>"
                           data-product_id="<?php echo esc_attr( $product_id ); ?>"
                           data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>">
                            ✕ <?php esc_html_e( 'Eliminar', 'vynk-checkout' ); ?>
                        </a>
                    </div>

                    <!-- Cantidad -->
                    <div class="vynk-qty-wrap">
                        <button type="button" class="vynk-qty-btn vynk-qty-minus" aria-label="-">−</button>
                        <?php
                        $min_value   = apply_filters( 'woocommerce_quantity_input_min', $_product->get_min_purchase_quantity(), $_product );
                        $max_value   = apply_filters( 'woocommerce_quantity_input_max', $_product->get_max_purchase_quantity(), $_product );
                        $input_name  = "cart[{$cart_item_key}][qty]";
                        ?>
                        <input type="number"
                               name="<?php echo esc_attr($input_name); ?>"
                               value="<?php echo esc_attr($cart_item['quantity']); ?>"
                               min="<?php echo esc_attr( max( 1, $min_value ) ); ?>"
                               max="<?php echo ( $max_value > 0 ) ? esc_attr( $max_value ) : ''; ?>"
                               step="1"
                               data-product-id="<?php echo esc_attr( $product_id ); ?>"
                               data-variation-id="<?php echo esc_attr( isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0 ); ?>"
                               aria-label="<?php esc_attr_e('Cantidad','vynk-checkout'); ?>">
                        <button type="button" class="vynk-qty-btn vynk-qty-plus" aria-label="+">+</button>
                    </div>

                    <!-- Subtotal -->
                    <div class="vynk-cart-row-price"
                         data-product-id="<?php echo esc_attr( $product_id ); ?>"
                         data-variation-id="<?php echo esc_attr( isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0 ); ?>"><?php echo $product_subtotal; // phpcs:ignore ?></div>
                </div>
                <?php endforeach; ?>

                <?php do_action( 'woocommerce_cart_contents' ); ?>
                <?php do_action( 'woocommerce_after_cart_contents' ); ?>
            </div>

            <?php do_action( 'woocommerce_cart_coupon' ); ?>
            <?php do_action( 'woocommerce_cart_actions' ); ?>
            <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
            <input type="hidden" name="update_cart" value="Update Cart">
        </div>
    </div>

    <!-- ═══ RIGHT: Order Summary ═══ -->
    <div class="vynk-cart-sidebar">
        <div class="vynk-cart-summary-card">

            <!-- Encabezado -->
            <div class="vynk-cart-summary-header">
                <div class="vynk-summary-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                    <?php esc_html_e( 'Resumen del Pedido', 'vynk-checkout' ); ?>
                </div>
                <span class="vynk-cart-count"><?php echo esc_html( $cart_count . ' ' . _n('artículo','artículos',$cart_count,'vynk-checkout') ); ?></span>
            </div>

            <!-- Filas de totales + Total Grand -->
            <?php Vynk_Checkout::instance()->render_cart_totals(); ?>

            <!-- Opciones de Envío -->
            <?php if ( WC()->cart->needs_shipping() ) : ?>
            <div class="vynk-sidebar-shipping">
                <div class="vynk-sidebar-shipping-header">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <?php esc_html_e( 'Opciones de Envío', 'vynk-checkout' ); ?>
                </div>
                <div id="vynk-cart-shipping-inner">
                    <?php Vynk_Checkout::instance()->render_shipping_methods(); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Cupón directo -->
            <?php if ( $show_coupon && wc_coupons_enabled() ) : ?>
            <div class="vynk-cart-coupon">
                <div class="vynk-cart-coupon-input-group">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <input type="text" name="coupon_code" class="input-text" placeholder="<?php esc_attr_e( 'Código de cupón', 'vynk-checkout' ); ?>">
                    <button type="submit" class="vynk-cart-coupon-btn" name="apply_coupon" value="<?php esc_attr_e( 'Aplicar', 'vynk-checkout' ); ?>">
                        <?php esc_html_e( 'Aplicar', 'vynk-checkout' ); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Botón finalizar compra -->
            <div class="vynk-cart-checkout-wrap">
                <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="vynk-cart-checkout-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <?php esc_html_e( 'Finalizar Compra', 'vynk-checkout' ); ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
                <p class="vynk-cart-secure-note">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <?php esc_html_e( 'Pago seguro y protegido', 'vynk-checkout' ); ?>
                </p>
            </div>

            <?php // do_action( 'woocommerce_proceed_to_checkout' ); — disabled: renders WC default button ?>
        </div>
    </div>

</form>

<?php do_action( 'woocommerce_after_cart' ); ?>
