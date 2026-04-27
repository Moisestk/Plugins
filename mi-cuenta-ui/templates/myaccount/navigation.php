<?php
/**
 * My Account navigation — Vynk sidebar nav
 *
 * Reemplaza woocommerce/myaccount/navigation.php
 */
defined( 'ABSPATH' ) || exit;

$current_endpoint = Mi_Cuenta_UI::get_current_endpoint();

/**
 * Íconos SVG por endpoint.
 */
$icons = [
    'dashboard'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    'orders'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
    'downloads'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>',
    'edit-address' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    'edit-account' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    'payment-methods' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
    'customer-logout' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>',
    'default'         => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
];

$menu_items = wc_get_account_menu_items();

// Separar el logout del resto
$logout_url  = '';
$logout_item = '';
if ( isset( $menu_items['customer-logout'] ) ) {
    $logout_item = $menu_items['customer-logout'];
    $logout_url  = wc_get_account_endpoint_url( 'customer-logout' );
    unset( $menu_items['customer-logout'] );
}
?>

<nav class="mc-nav" aria-label="<?php esc_attr_e( 'Mi cuenta', 'mi-cuenta-ui' ); ?>">
    <ul class="mc-nav__list">
        <?php foreach ( $menu_items as $endpoint => $label ) :
            $url       = wc_get_account_endpoint_url( $endpoint );
            $is_active = ( $current_endpoint === $endpoint ) || ( 'dashboard' === $endpoint && 'dashboard' === $current_endpoint );
            $icon      = $icons[ $endpoint ] ?? $icons['default'];
        ?>
        <li class="mc-nav__item <?php echo $is_active ? 'is-active' : ''; ?>">
            <a href="<?php echo esc_url( $url ); ?>" class="mc-nav__link" data-endpoint="<?php echo esc_attr( $endpoint ); ?>">
                <span class="mc-nav__icon"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                <span class="mc-nav__label"><?php echo esc_html( $label ); ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php if ( $logout_item ) : ?>
        <hr class="mc-nav__sep">
        <ul class="mc-nav__list">
            <li class="mc-nav__item">
                <a href="<?php echo esc_url( $logout_url ); ?>" class="mc-nav__link" data-endpoint="customer-logout">
                    <span class="mc-nav__icon"><?php echo $icons['customer-logout']; // phpcs:ignore ?></span>
                    <span class="mc-nav__label"><?php echo esc_html( $logout_item ); ?></span>
                </a>
            </li>
        </ul>
    <?php endif; ?>
</nav>
