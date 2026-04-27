<?php
defined( 'ABSPATH' ) || exit;

/**
 * Vynk Checkout Settings Panel
 *
 * Adds a settings page under WooCommerce → Vynk Checkout
 * with controls for logo, colors, and feature toggles.
 */
class Vynk_Settings {

    const OPTION_KEY = 'vynk_checkout_options';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Add submenu page under WooCommerce.
     */
    public function add_menu_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Vynk Checkout', 'vynk-checkout' ),
            __( 'Vynk Checkout', 'vynk-checkout' ),
            'manage_woocommerce',
            'vynk-checkout-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register settings with the WordPress Settings API.
     */
    public function register_settings() {
        register_setting(
            'vynk_checkout_group',
            self::OPTION_KEY,
            [ $this, 'sanitize_options' ]
        );
    }

    /**
     * Sanitize all settings before saving.
     */
    public function sanitize_options( $input ) {
        $clean = [];

        // Logo — attachment ID
        $clean['logo_id'] = ! empty( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0;

        // Colors — hex only
        $color_fields = [ 'accent_color', 'button_color', 'bg_color', 'card_border_color' ];
        foreach ( $color_fields as $field ) {
            if ( ! empty( $input[ $field ] ) ) {
                $hex = sanitize_hex_color( $input[ $field ] );
                $clean[ $field ] = $hex ? $hex : '';
            } else {
                $clean[ $field ] = '';
            }
        }

        // Text fields
        $clean['page_title'] = sanitize_text_field( $input['page_title'] ?? '' );

        // Checkboxes (boolean)
        $clean['show_coupon']      = ! empty( $input['show_coupon'] );
        $clean['show_order_notes'] = ! empty( $input['show_order_notes'] );
        $clean['show_mock_gateway'] = ! empty( $input['show_mock_gateway'] );

        // Sidebar position
        $allowed_positions = [ 'right', 'left' ];
        $clean['sidebar_position'] = in_array( $input['sidebar_position'] ?? '', $allowed_positions, true )
            ? $input['sidebar_position']
            : 'right';

        return $clean;
    }

    /**
     * Enqueue WP color picker and media uploader on our settings page.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'woocommerce_page_vynk-checkout-settings' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_media();
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script(
            'vynk-admin',
            VYNK_CHECKOUT_URL . 'assets/js/vynk-admin.js',
            [ 'jquery', 'wp-color-picker' ],
            VYNK_CHECKOUT_VERSION,
            true
        );
        wp_enqueue_style(
            'vynk-admin',
            VYNK_CHECKOUT_URL . 'assets/css/vynk-admin.css',
            [],
            VYNK_CHECKOUT_VERSION
        );
    }

    /**
     * Render the settings page HTML.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'vynk-checkout' ) );
        }

        $opts = get_option( self::OPTION_KEY, [] );

        // Defaults
        $logo_id          = $opts['logo_id']          ?? 0;
        $accent_color     = $opts['accent_color']     ?: '#bf1f1f';
        $button_color     = $opts['button_color']     ?: '#bf1f1f';
        $bg_color         = $opts['bg_color']         ?: '#ffffff';
        $card_border      = $opts['card_border_color'] ?: '#f0f0f0';
        $page_title       = $opts['page_title']       ?? '';
        $show_coupon      = isset( $opts['show_coupon'] )       ? (bool) $opts['show_coupon']       : true;
        $show_notes       = isset( $opts['show_order_notes'] )  ? (bool) $opts['show_order_notes']  : true;
        $show_mock        = isset( $opts['show_mock_gateway'] ) ? (bool) $opts['show_mock_gateway'] : true;
        $sidebar_position = $opts['sidebar_position'] ?? 'right';

        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        ?>
        <div class="wrap vynk-admin-wrap">

            <div class="vynk-admin-header">
                <div class="vynk-admin-brand">
                    <?php if ( $logo_url ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="" class="vynk-admin-logo-preview-header">
                    <?php endif; ?>
                    <div>
                        <h1><?php esc_html_e( 'Vynk Checkout', 'vynk-checkout' ); ?></h1>
                        <p><?php esc_html_e( 'Customize your checkout appearance and behavior.', 'vynk-checkout' ); ?></p>
                    </div>
                </div>
                <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" target="_blank" class="vynk-admin-preview-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <?php esc_html_e( 'Preview Checkout', 'vynk-checkout' ); ?>
                </a>
            </div>

            <?php settings_errors( 'vynk_checkout_group' ); ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'vynk_checkout_group' ); ?>

                <div class="vynk-admin-grid">

                    <!-- ── COLUMN LEFT ── -->
                    <div class="vynk-admin-col">

                        <!-- SECTION: Branding -->
                        <div class="vynk-admin-card">
                            <h2 class="vynk-admin-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32"/></svg>
                                <?php esc_html_e( 'Branding', 'vynk-checkout' ); ?>
                            </h2>

                            <div class="vynk-admin-field">
                                <label><?php esc_html_e( 'Checkout Logo', 'vynk-checkout' ); ?></label>
                                <div class="vynk-logo-uploader">
                                    <?php if ( $logo_url ) : ?>
                                        <img id="vynk-logo-preview" src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-height:60px; display:block; margin-bottom:10px; border-radius:6px;">
                                    <?php else : ?>
                                        <img id="vynk-logo-preview" src="" alt="" style="max-height:60px; display:none; margin-bottom:10px; border-radius:6px;">
                                    <?php endif; ?>
                                    <input type="hidden" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[logo_id]" id="vynk-logo-id" value="<?php echo esc_attr( $logo_id ); ?>">
                                    <div class="vynk-logo-actions">
                                        <button type="button" id="vynk-upload-logo" class="button button-secondary">
                                            <?php esc_html_e( 'Upload Logo', 'vynk-checkout' ); ?>
                                        </button>
                                        <button type="button" id="vynk-remove-logo" class="button" style="<?php echo $logo_id ? '' : 'display:none'; ?>">
                                            <?php esc_html_e( 'Remove', 'vynk-checkout' ); ?>
                                        </button>
                                    </div>
                                </div>
                                <p class="description"><?php esc_html_e( 'Displayed above the checkout form. Recommended: transparent PNG, max 200px height.', 'vynk-checkout' ); ?></p>
                            </div>

                            <div class="vynk-admin-field">
                                <label for="vynk-page-title"><?php esc_html_e( 'Custom Checkout Title', 'vynk-checkout' ); ?></label>
                                <input type="text"
                                       id="vynk-page-title"
                                       name="<?php echo esc_attr( self::OPTION_KEY ); ?>[page_title]"
                                       value="<?php echo esc_attr( $page_title ); ?>"
                                       placeholder="<?php esc_attr_e( 'e.g. Complete Your Order', 'vynk-checkout' ); ?>"
                                       class="regular-text">
                                <p class="description"><?php esc_html_e( 'Leave empty to hide. Shown next to the logo.', 'vynk-checkout' ); ?></p>
                            </div>
                        </div>

                        <!-- SECTION: Colors -->
                        <div class="vynk-admin-card">
                            <h2 class="vynk-admin-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                                <?php esc_html_e( 'Colors', 'vynk-checkout' ); ?>
                            </h2>

                            <div class="vynk-color-grid">
                                <div class="vynk-admin-field">
                                    <label for="vynk-accent-color"><?php esc_html_e( 'Accent Color', 'vynk-checkout' ); ?></label>
                                    <input type="text"
                                           id="vynk-accent-color"
                                           name="<?php echo esc_attr( self::OPTION_KEY ); ?>[accent_color]"
                                           value="<?php echo esc_attr( $accent_color ); ?>"
                                           class="vynk-color-picker"
                                           data-default-color="#bf1f1f">
                                    <p class="description"><?php esc_html_e( 'Icons, input focus, links', 'vynk-checkout' ); ?></p>
                                </div>

                                <div class="vynk-admin-field">
                                    <label for="vynk-button-color"><?php esc_html_e( 'Button Color', 'vynk-checkout' ); ?></label>
                                    <input type="text"
                                           id="vynk-button-color"
                                           name="<?php echo esc_attr( self::OPTION_KEY ); ?>[button_color]"
                                           value="<?php echo esc_attr( $button_color ); ?>"
                                           class="vynk-color-picker"
                                           data-default-color="#bf1f1f">
                                    <p class="description"><?php esc_html_e( '"Place Order" button', 'vynk-checkout' ); ?></p>
                                </div>

                                <div class="vynk-admin-field">
                                    <label for="vynk-bg-color"><?php esc_html_e( 'Card Background', 'vynk-checkout' ); ?></label>
                                    <input type="text"
                                           id="vynk-bg-color"
                                           name="<?php echo esc_attr( self::OPTION_KEY ); ?>[bg_color]"
                                           value="<?php echo esc_attr( $bg_color ); ?>"
                                           class="vynk-color-picker"
                                           data-default-color="#ffffff">
                                    <p class="description"><?php esc_html_e( 'Section card background', 'vynk-checkout' ); ?></p>
                                </div>

                                <div class="vynk-admin-field">
                                    <label for="vynk-card-border"><?php esc_html_e( 'Card Border', 'vynk-checkout' ); ?></label>
                                    <input type="text"
                                           id="vynk-card-border"
                                           name="<?php echo esc_attr( self::OPTION_KEY ); ?>[card_border_color]"
                                           value="<?php echo esc_attr( $card_border ); ?>"
                                           class="vynk-color-picker"
                                           data-default-color="#f0f0f0">
                                    <p class="description"><?php esc_html_e( 'Card & input borders', 'vynk-checkout' ); ?></p>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- /.vynk-admin-col -->

                    <!-- ── COLUMN RIGHT ── -->
                    <div class="vynk-admin-col">

                        <!-- SECTION: Layout -->
                        <div class="vynk-admin-card">
                            <h2 class="vynk-admin-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                                <?php esc_html_e( 'Layout', 'vynk-checkout' ); ?>
                            </h2>

                            <div class="vynk-admin-field">
                                <label><?php esc_html_e( 'Order Summary Position', 'vynk-checkout' ); ?></label>
                                <div class="vynk-radio-group">
                                    <label class="vynk-radio-card <?php echo 'right' === $sidebar_position ? 'is-selected' : ''; ?>">
                                        <input type="radio"
                                               name="<?php echo esc_attr( self::OPTION_KEY ); ?>[sidebar_position]"
                                               value="right"
                                               <?php checked( $sidebar_position, 'right' ); ?>>
                                        <svg width="40" height="28" viewBox="0 0 40 28"><rect x="1" y="1" width="23" height="26" rx="2" fill="#f0f0f0" stroke="#ddd"/><rect x="27" y="1" width="12" height="26" rx="2" fill="#e2e2e2" stroke="#ddd"/></svg>
                                        <span><?php esc_html_e( 'Right', 'vynk-checkout' ); ?></span>
                                    </label>
                                    <label class="vynk-radio-card <?php echo 'left' === $sidebar_position ? 'is-selected' : ''; ?>">
                                        <input type="radio"
                                               name="<?php echo esc_attr( self::OPTION_KEY ); ?>[sidebar_position]"
                                               value="left"
                                               <?php checked( $sidebar_position, 'left' ); ?>>
                                        <svg width="40" height="28" viewBox="0 0 40 28"><rect x="1" y="1" width="12" height="26" rx="2" fill="#e2e2e2" stroke="#ddd"/><rect x="16" y="1" width="23" height="26" rx="2" fill="#f0f0f0" stroke="#ddd"/></svg>
                                        <span><?php esc_html_e( 'Left', 'vynk-checkout' ); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Features -->
                        <div class="vynk-admin-card">
                            <h2 class="vynk-admin-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                <?php esc_html_e( 'Features', 'vynk-checkout' ); ?>
                            </h2>

                            <div class="vynk-toggle-list">

                                <label class="vynk-toggle-row">
                                    <div class="vynk-toggle-info">
                                        <strong><?php esc_html_e( 'Coupon Code Toggle', 'vynk-checkout' ); ?></strong>
                                        <span><?php esc_html_e( 'Show "Have a coupon?" link that expands the coupon form.', 'vynk-checkout' ); ?></span>
                                    </div>
                                    <div class="vynk-toggle-switch">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_coupon]"
                                               id="vynk-show-coupon"
                                               value="1"
                                               <?php checked( $show_coupon ); ?>>
                                        <span class="vynk-toggle-slider"></span>
                                    </div>
                                </label>

                                <label class="vynk-toggle-row">
                                    <div class="vynk-toggle-info">
                                        <strong><?php esc_html_e( 'Order Notes Field', 'vynk-checkout' ); ?></strong>
                                        <span><?php esc_html_e( 'Allow customers to leave a note with their order.', 'vynk-checkout' ); ?></span>
                                    </div>
                                    <div class="vynk-toggle-switch">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_order_notes]"
                                               id="vynk-show-notes"
                                               value="1"
                                               <?php checked( $show_notes ); ?>>
                                        <span class="vynk-toggle-slider"></span>
                                    </div>
                                </label>

                                <label class="vynk-toggle-row">
                                    <div class="vynk-toggle-info">
                                        <strong><?php esc_html_e( 'Mockup Card Gateway', 'vynk-checkout' ); ?></strong>
                                        <span><?php esc_html_e( 'Enable the "Credit / Debit Card" placeholder gateway (for testing without a real processor).', 'vynk-checkout' ); ?></span>
                                    </div>
                                    <div class="vynk-toggle-switch">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_mock_gateway]"
                                               id="vynk-show-mock"
                                               value="1"
                                               <?php checked( $show_mock ); ?>>
                                        <span class="vynk-toggle-slider"></span>
                                    </div>
                                </label>

                            </div>
                        </div>

                        <!-- SECTION: Save -->
                        <div class="vynk-admin-card vynk-admin-card-save">
                            <?php submit_button( __( 'Save Settings', 'vynk-checkout' ), 'primary vynk-save-btn', 'submit', false ); ?>
                            <p class="vynk-admin-version">
                                Vynk Checkout v<?php echo esc_html( VYNK_CHECKOUT_VERSION ); ?>
                            </p>
                        </div>

                    </div>
                    <!-- /.vynk-admin-col -->

                </div>
                <!-- /.vynk-admin-grid -->

            </form>
        </div>
        <?php
    }

    /**
     * Return current options with defaults filled in.
     */
    public static function get( $key = null ) {
        $opts = get_option( self::OPTION_KEY, [] );
        $defaults = [
            'logo_id'          => 0,
            'accent_color'     => '#bf1f1f',
            'button_color'     => '#bf1f1f',
            'bg_color'         => '#ffffff',
            'card_border_color'=> '#f0f0f0',
            'page_title'       => '',
            'show_coupon'      => true,
            'show_order_notes' => true,
            'show_mock_gateway'=> true,
            'sidebar_position' => 'right',
        ];
        $opts = wp_parse_args( $opts, $defaults );

        if ( null !== $key ) {
            return $opts[ $key ] ?? null;
        }
        return $opts;
    }
}
