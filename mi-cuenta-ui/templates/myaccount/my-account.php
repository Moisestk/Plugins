<?php
/**
 * My Account page — Vynk dashboard shell
 *
 * Reemplaza woocommerce/myaccount/my-account.php
 */
defined( 'ABSPATH' ) || exit;
?>

<?php if ( is_user_logged_in() ) :
    $current_user = wp_get_current_user();
    $initial      = strtoupper( mb_substr( $current_user->display_name, 0, 1 ) );
?>

    <!-- Overlay mobile -->
    <div class="mc-sidebar-overlay" id="mc-overlay"></div>

    <div class="mc-dashboard">

        <!-- ── Sidebar ─────────────────────────────────────── -->
        <aside class="mc-sidebar" id="mc-sidebar">

            <div class="mc-sidebar-user">
                <div class="mc-sidebar-user__avatar"><?php echo esc_html( $initial ); ?></div>
                <p class="mc-sidebar-user__name"><?php echo esc_html( $current_user->display_name ); ?></p>
                <p class="mc-sidebar-user__email"><?php echo esc_html( $current_user->user_email ); ?></p>
            </div>

            <?php wc_get_template( 'myaccount/navigation.php' ); ?>

        </aside><!-- /.mc-sidebar -->

        <!-- ── Main ───────────────────────────────────────── -->
        <main class="mc-main" id="mc-main-content">

            <!-- Topbar mobile (solo visible en mobile) -->
            <div class="mc-mobile-topbar">
                <button class="mc-mobile-toggle" aria-label="Abrir menú" id="mc-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <span><?php esc_html_e( 'Menú', 'mi-cuenta-ui' ); ?></span>
                </button>
                <span class="mc-mobile-topbar__title">
                    <?php esc_html_e( 'Mi cuenta', 'mi-cuenta-ui' ); ?>
                </span>
            </div>

            <?php do_action( 'woocommerce_before_account_content' ); ?>

            <?php woocommerce_output_all_notices(); ?>

            <?php do_action( 'woocommerce_account_content' ); ?>

            <?php do_action( 'woocommerce_after_account_content' ); ?>

        </main><!-- /.mc-main -->

    </div><!-- /.mc-dashboard -->

<?php else :
    // Para endpoints de guest (lost-password, etc.) renderizar el contenido de WC.
    // Para el login normal, mostrar el formulario de login/registro.
    $current_endpoint = WC()->query ? WC()->query->get_current_endpoint() : '';
?>

    <?php if ( $current_endpoint && 'customer-logout' !== $current_endpoint ) : ?>

        <?php do_action( 'woocommerce_account_content' ); ?>

    <?php else : ?>

        <?php wc_get_template( 'myaccount/form-login.php' ); ?>

    <?php endif; ?>

<?php endif; ?>
