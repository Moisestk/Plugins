<?php
/**
 * My Account — View order
 *
 * Reemplaza woocommerce/myaccount/view-order.php
 *
 * @var int    $order_id
 * @var object $order
 */
defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id );

if ( ! $order || ! current_user_can( 'view_order', $order_id ) ) {
    echo '<div class="mc-card"><p>' . esc_html__( 'Acceso no válido.', 'mi-cuenta-ui' ) . '</p></div>';
    return;
}

$status        = $order->get_status();
$status_map    = [
    'pending'    => 'mc-status--pending',
    'processing' => 'mc-status--processing',
    'on-hold'    => 'mc-status--on-hold',
    'completed'  => 'mc-status--completed',
    'cancelled'  => 'mc-status--cancelled',
    'refunded'   => 'mc-status--refunded',
    'failed'     => 'mc-status--failed',
];
$status_cls = $status_map[ $status ] ?? 'mc-status--pending';
?>

<!-- Breadcrumb -->
<nav class="mc-breadcrumb">
    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">
        <?php esc_html_e( 'Mis Pedidos', 'mi-cuenta-ui' ); ?>
    </a>
    <span class="mc-breadcrumb__sep">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
    </span>
    <span class="mc-breadcrumb__current">
        <?php printf( esc_html__( 'Pedido #%s', 'mi-cuenta-ui' ), esc_html( $order->get_order_number() ) ); ?>
    </span>
</nav>

<!-- Header del pedido -->
<div class="mc-section-header">
    <div class="mc-order-detail-header">
        <h1 class="mc-section-title">
            <?php printf( esc_html__( 'Pedido #%s', 'mi-cuenta-ui' ), esc_html( $order->get_order_number() ) ); ?>
        </h1>
        <span class="mc-status <?php echo esc_attr( $status_cls ); ?>">
            <?php echo esc_html( wc_get_order_status_name( $status ) ); ?>
        </span>
    </div>
    <div class="mc-order-detail-meta">
        <span><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
        <span><?php printf( esc_html__( '%d artículos', 'mi-cuenta-ui' ), absint( $order->get_item_count() ) ); ?></span>
        <span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
    </div>
</div>

<!-- Artículos del pedido -->
<div class="mc-card">
    <div class="mc-card__header">
        <h3 class="mc-card__title"><?php esc_html_e( 'Artículos', 'mi-cuenta-ui' ); ?></h3>
    </div>

    <table class="mc-order-items-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Producto', 'mi-cuenta-ui' ); ?></th>
                <th><?php esc_html_e( 'Cantidad', 'mi-cuenta-ui' ); ?></th>
                <th style="text-align:right"><?php esc_html_e( 'Total', 'mi-cuenta-ui' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ( $order->get_items() as $item_id => $item ) {
                $product      = $item->get_product();
                $product_name = $item->get_name();
                $qty          = $item->get_quantity();
                $subtotal     = $order->get_formatted_line_subtotal( $item );
                ?>
                <tr>
                    <td>
                        <?php if ( $product && $product->is_visible() ) : ?>
                            <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
                                <?php echo esc_html( $product_name ); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html( $product_name ); ?>
                        <?php endif; ?>

                        <?php
                        // Meta del item (variaciones)
                        $item_data = $item->get_formatted_meta_data( '_', true );
                        if ( $item_data ) {
                            echo '<dl class="variation" style="font-size:12px;color:#6b7280;margin:4px 0 0">';
                            foreach ( $item_data as $meta ) {
                                echo '<dt style="display:inline;font-weight:600">' . wp_kses_post( $meta->display_key ) . ':</dt> ';
                                echo '<dd style="display:inline;margin:0">' . wp_kses_post( $meta->display_value ) . '</dd> ';
                            }
                            echo '</dl>';
                        }
                        ?>
                    </td>
                    <td><?php echo esc_html( $qty ); ?></td>
                    <td style="text-align:right"><?php echo wp_kses_post( $subtotal ); ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>

    <!-- Totales -->
    <table class="mc-order-totals">
        <?php foreach ( $order->get_order_item_totals() as $key => $total ) : ?>
            <tr class="<?php echo ( 'order_total' === $key ) ? 'mc-totals-grand' : ''; ?>">
                <td><?php echo esc_html( $total['label'] ); ?></td>
                <td><?php echo wp_kses_post( $total['value'] ); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Nota del pedido -->
<?php if ( $order->get_customer_note() ) : ?>
    <div class="mc-card">
        <div class="mc-card__header">
            <h3 class="mc-card__title"><?php esc_html_e( 'Nota del pedido', 'mi-cuenta-ui' ); ?></h3>
        </div>
        <p style="font-size:14px;color:#6b7280;margin:0"><?php echo esc_html( $order->get_customer_note() ); ?></p>
    </div>
<?php endif; ?>

<!-- Direcciones -->
<div class="mc-card">
    <div class="mc-card__header">
        <h3 class="mc-card__title"><?php esc_html_e( 'Direcciones', 'mi-cuenta-ui' ); ?></h3>
    </div>
    <div class="mc-order-addresses">
        <div class="mc-address-block">
            <h4><?php esc_html_e( 'Dirección de facturación', 'mi-cuenta-ui' ); ?></h4>
            <address><?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'No proporcionada', 'mi-cuenta-ui' ) ) ); ?></address>
        </div>
        <?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() ) : ?>
            <div class="mc-address-block">
                <h4><?php esc_html_e( 'Dirección de envío', 'mi-cuenta-ui' ); ?></h4>
                <address><?php echo wp_kses_post( $order->get_formatted_shipping_address( esc_html__( 'No proporcionada', 'mi-cuenta-ui' ) ) ); ?></address>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Acciones -->
<?php
$actions = wc_get_account_orders_actions( $order );
if ( ! empty( $actions ) ) :
?>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <?php foreach ( $actions as $key => $action ) : ?>
            <a href="<?php echo esc_url( $action['url'] ); ?>" class="mc-btn <?php echo 'pay' === $key ? 'mc-btn--accent' : 'mc-btn--outline'; ?>">
                <?php echo esc_html( $action['name'] ); ?>
            </a>
        <?php endforeach; ?>
        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="mc-btn mc-btn--outline">
            &larr; <?php esc_html_e( 'Volver a pedidos', 'mi-cuenta-ui' ); ?>
        </a>
    </div>
<?php endif; ?>
