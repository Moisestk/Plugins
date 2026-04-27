<?php
/**
 * My Account — Orders list
 *
 * Reemplaza woocommerce/myaccount/orders.php
 *
 * @var array  $customer_orders
 * @var int    $current_page
 * @var bool   $has_orders
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders );

// Mapeo de estado → clase CSS
$status_classes = [
    'pending'    => 'mc-status--pending',
    'processing' => 'mc-status--processing',
    'on-hold'    => 'mc-status--on-hold',
    'completed'  => 'mc-status--completed',
    'cancelled'  => 'mc-status--cancelled',
    'refunded'   => 'mc-status--refunded',
    'failed'     => 'mc-status--failed',
];
?>

<div class="mc-section-header">
    <h1 class="mc-section-title"><?php esc_html_e( 'Mis Pedidos', 'mi-cuenta-ui' ); ?></h1>
    <?php if ( $has_orders ) : ?>
        <p class="mc-section-subtitle">
            <?php
            printf(
                esc_html( _n( '%d pedido encontrado', '%d pedidos encontrados', $customer_orders->total, 'mi-cuenta-ui' ) ),
                absint( $customer_orders->total )
            );
            ?>
        </p>
    <?php endif; ?>
</div>

<?php if ( $has_orders ) : ?>

    <div class="mc-card" style="padding: 0; overflow: hidden;">
        <table class="mc-orders-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Pedido', 'mi-cuenta-ui' ); ?></th>
                    <th><?php esc_html_e( 'Fecha', 'mi-cuenta-ui' ); ?></th>
                    <th><?php esc_html_e( 'Estado', 'mi-cuenta-ui' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'mi-cuenta-ui' ); ?></th>
                    <th><?php esc_html_e( 'Acciones', 'mi-cuenta-ui' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ( $customer_orders->orders as $customer_order ) {
                    $order      = wc_get_order( $customer_order );
                    $item_count = $order->get_item_count() - $order->get_item_count_refunded();
                    $status     = $order->get_status();
                    $status_cls = $status_classes[ $status ] ?? 'mc-status--pending';
                    ?>
                    <tr>
                        <td data-label="<?php esc_attr_e( 'Pedido', 'mi-cuenta-ui' ); ?>">
                            <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="mc-order-number">
                                #<?php echo esc_html( $order->get_order_number() ); ?>
                            </a>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Fecha', 'mi-cuenta-ui' ); ?>" class="mc-order-date">
                            <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Estado', 'mi-cuenta-ui' ); ?>">
                            <span class="mc-status <?php echo esc_attr( $status_cls ); ?>">
                                <?php echo esc_html( wc_get_order_status_name( $status ) ); ?>
                            </span>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Total', 'mi-cuenta-ui' ); ?>">
                            <?php
                            printf(
                                /* translators: 1: order total 2: item count */
                                _n( '%1$s por %2$d artículo', '%1$s por %2$d artículos', $item_count, 'mi-cuenta-ui' ),
                                wp_kses_post( $order->get_formatted_order_total() ),
                                absint( $item_count )
                            );
                            ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Acciones', 'mi-cuenta-ui' ); ?>">
                            <?php
                            $actions = wc_get_account_orders_actions( $order );
                            if ( ! empty( $actions ) ) {
                                foreach ( $actions as $key => $action ) {
                                    echo '<a href="' . esc_url( $action['url'] ) . '" class="mc-btn mc-btn--sm ' . ( 'view' === $key ? 'mc-btn--primary' : 'mc-btn--outline' ) . '" style="margin-right:6px">' . esc_html( $action['name'] ) . '</a>';
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div><!-- /.mc-card -->

    <?php if ( 1 < $customer_orders->max_num_pages ) : ?>
        <div class="mc-pagination" style="display:flex;gap:8px;justify-content:center;margin-top:20px;">
            <?php if ( 1 !== $current_page ) : ?>
                <a href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>" class="mc-btn mc-btn--outline mc-btn--sm">
                    &larr; <?php esc_html_e( 'Anterior', 'mi-cuenta-ui' ); ?>
                </a>
            <?php endif; ?>
            <?php if ( $current_page < $customer_orders->max_num_pages ) : ?>
                <a href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>" class="mc-btn mc-btn--outline mc-btn--sm">
                    <?php esc_html_e( 'Siguiente', 'mi-cuenta-ui' ); ?> &rarr;
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else : ?>

    <div class="mc-card mc-empty-orders">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p><?php esc_html_e( 'Todavía no has realizado ningún pedido.', 'mi-cuenta-ui' ); ?></p>
        <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="mc-btn mc-btn--primary">
            <?php esc_html_e( 'Ir a la tienda', 'mi-cuenta-ui' ); ?>
        </a>
    </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
