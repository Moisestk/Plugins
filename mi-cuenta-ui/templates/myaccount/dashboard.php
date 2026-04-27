<?php
/**
 * My Account — Dashboard
 *
 * Reemplaza woocommerce/myaccount/dashboard.php
 */
defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();

// Datos del usuario
$customer_orders = wc_get_orders( [
    'customer' => get_current_user_id(),
    'limit'    => -1,
    'return'   => 'ids',
] );
$total_orders = count( $customer_orders );

// Total gastado
$total_spent = wc_get_customer_total_spent( get_current_user_id() );

// Miembro desde
$registered = strtotime( $current_user->user_registered );
$member_since = date_i18n( 'M Y', $registered );

// Último pedido
$last_orders = wc_get_orders( [
    'customer' => get_current_user_id(),
    'limit'    => 1,
    'orderby'  => 'date',
    'order'    => 'DESC',
] );
$last_order = ! empty( $last_orders ) ? $last_orders[0] : null;
?>

<div class="mc-section-header">
    <h1 class="mc-section-title"><?php esc_html_e( 'Dashboard', 'mi-cuenta-ui' ); ?></h1>
    <p class="mc-section-subtitle">
        <?php
        printf(
            /* translators: %s: user display name */
            esc_html__( 'Bienvenido de vuelta, %s', 'mi-cuenta-ui' ),
            '<strong>' . esc_html( $current_user->display_name ) . '</strong>'
        );
        ?>
    </p>
</div>

<!-- Stats grid -->
<div class="mc-stats-grid">

    <div class="mc-stat-card">
        <div class="mc-stat-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <div>
            <div class="mc-stat-card__value"><?php echo esc_html( $total_orders ); ?></div>
            <div class="mc-stat-card__label"><?php esc_html_e( 'Pedidos realizados', 'mi-cuenta-ui' ); ?></div>
        </div>
    </div>

    <div class="mc-stat-card">
        <div class="mc-stat-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="mc-stat-card__value"><?php echo wp_kses_post( wc_price( $total_spent ) ); ?></div>
            <div class="mc-stat-card__label"><?php esc_html_e( 'Total gastado', 'mi-cuenta-ui' ); ?></div>
        </div>
    </div>

    <div class="mc-stat-card">
        <div class="mc-stat-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <div class="mc-stat-card__value" style="font-size:18px"><?php echo esc_html( $member_since ); ?></div>
            <div class="mc-stat-card__label"><?php esc_html_e( 'Miembro desde', 'mi-cuenta-ui' ); ?></div>
        </div>
    </div>

</div><!-- /.mc-stats-grid -->

<!-- Tarjeta de bienvenida / acciones rápidas -->
<div class="mc-card mc-greeting">
    <div class="mc-greeting__text">
        <?php if ( $last_order ) : ?>
            <h2><?php esc_html_e( 'Tu último pedido', 'mi-cuenta-ui' ); ?></h2>
            <p>
                <?php
                printf(
                    /* translators: 1: order number, 2: order date, 3: order status */
                    esc_html__( 'Pedido #%1$s del %2$s — Estado: %3$s', 'mi-cuenta-ui' ),
                    esc_html( $last_order->get_order_number() ),
                    esc_html( wc_format_datetime( $last_order->get_date_created() ) ),
                    '<strong>' . esc_html( wc_get_order_status_name( $last_order->get_status() ) ) . '</strong>'
                );
                ?>
            </p>
        <?php else : ?>
            <h2><?php esc_html_e( '¡Empieza a explorar!', 'mi-cuenta-ui' ); ?></h2>
            <p><?php esc_html_e( 'Aún no tienes pedidos. Visita nuestra tienda y encuentra lo que buscas.', 'mi-cuenta-ui' ); ?></p>
        <?php endif; ?>
    </div>
    <div class="mc-greeting__cta">
        <?php if ( $last_order ) : ?>
            <a href="<?php echo esc_url( $last_order->get_view_order_url() ); ?>" class="mc-btn mc-btn--outline mc-btn--sm">
                <?php esc_html_e( 'Ver pedido', 'mi-cuenta-ui' ); ?>
            </a>
        <?php endif; ?>
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="mc-btn mc-btn--primary mc-btn--sm">
            <?php esc_html_e( 'Ir a la tienda', 'mi-cuenta-ui' ); ?>
        </a>
    </div>
</div>

<?php
/**
 * Hook: woocommerce_account_dashboard.
 */
do_action( 'woocommerce_account_dashboard' );

/**
 * Deprecated woocommerce_before_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_before_my_account' );

/**
 * Deprecated woocommerce_after_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_after_my_account' );
