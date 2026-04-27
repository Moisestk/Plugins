<?php
defined( 'ABSPATH' ) || exit;

class Mi_Cuenta_UI {

    private static $instance = null;

    /** Templates que este plugin sobreescribe */
    private $overrides = [
        'myaccount/my-account.php',
        'myaccount/navigation.php',
        'myaccount/dashboard.php',
        'myaccount/orders.php',
        'myaccount/view-order.php',
        'myaccount/form-edit-account.php',
        'myaccount/form-edit-address.php',
        'myaccount/form-login.php',
        'myaccount/lost-password.php',
    ];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'woocommerce_locate_template', [ $this, 'locate_template' ], 100, 3 );
        add_action( 'wp_enqueue_scripts',           [ $this, 'enqueue_assets' ], 20 );
        add_filter( 'body_class',                   [ $this, 'body_class' ], 10 );
    }

    /**
     * Redirige los templates de myaccount a la carpeta del plugin.
     */
    public function locate_template( $template, $template_name, $template_path ) {
        if ( ! in_array( $template_name, $this->overrides, true ) ) {
            return $template;
        }

        $plugin_tpl = MI_CUENTA_DIR . 'templates/' . $template_name;

        return file_exists( $plugin_tpl ) ? $plugin_tpl : $template;
    }

    /**
     * Encola CSS y JS solo en páginas de cuenta.
     */
    public function enqueue_assets() {
        if ( ! is_account_page() ) {
            return;
        }

        wp_enqueue_style(
            'vynk-barlow',
            'https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap',
            [],
            null
        );

        wp_enqueue_style(
            'mi-cuenta-ui',
            MI_CUENTA_URL . 'assets/css/mi-cuenta.css',
            [ 'woocommerce-layout', 'woocommerce-general', 'vynk-barlow' ],
            MI_CUENTA_VERSION
        );

        wp_enqueue_script(
            'mi-cuenta-ui',
            MI_CUENTA_URL . 'assets/js/mi-cuenta.js',
            [],
            MI_CUENTA_VERSION,
            true
        );

        $endpoint = WC()->query ? WC()->query->get_current_endpoint() : '';
        $endpoint = $endpoint ?: 'dashboard';

        wp_localize_script( 'mi-cuenta-ui', 'miCuenta', [
            'currentEndpoint' => esc_js( $endpoint ),
            'ajaxUrl'         => esc_url( admin_url( 'admin-ajax.php' ) ),
            'nonce'           => wp_create_nonce( 'mi_cuenta_nonce' ),
            'isLoggedIn'      => is_user_logged_in() ? 'yes' : 'no',
        ] );
    }

    /**
     * Añade clases al <body> para scoping CSS.
     */
    public function body_class( $classes ) {
        if ( ! is_account_page() ) {
            return $classes;
        }

        $classes[] = 'mi-cuenta-active';

        if ( WC()->query ) {
            $endpoint = WC()->query->get_current_endpoint();
            if ( $endpoint ) {
                $classes[] = 'mc-endpoint--' . sanitize_html_class( $endpoint );
            } else {
                $classes[] = 'mc-endpoint--dashboard';
            }
        }

        return $classes;
    }

    /**
     * Devuelve el endpoint activo para uso en templates.
     */
    public static function get_current_endpoint() {
        if ( ! WC()->query ) {
            return 'dashboard';
        }
        $ep = WC()->query->get_current_endpoint();
        return $ep ?: 'dashboard';
    }
}
