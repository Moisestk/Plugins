<?php
defined( 'ABSPATH' ) || exit;

class Vynk_Checkout {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Override checkout template (priority 100 → after CartFlows/other plugins)
        add_filter( 'woocommerce_locate_template', [ $this, 'locate_template' ], 100, 4 );

        // Override cart template
        add_filter( 'woocommerce_locate_template', [ $this, 'locate_cart_template' ], 100, 4 );

        // Enqueue frontend assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 20 );

        // Dynamic CSS from settings
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_dynamic_css' ], 30 );

        // Remove WC default coupon form (we place it in sidebar)
        add_action( 'woocommerce_before_checkout_form', [ $this, 'remove_default_coupon_form' ], 1 );

        // Remove WC's default order-details table on thankyou page — we render our
        // own layout in thankyou.php; removing here (constructor, priority 1) ensures
        // the hook is gone before any template or woocommerce_thankyou action fires.
        add_action( 'wp', [ $this, 'remove_thankyou_default_table' ], 1 );

        // Register mock gateway (conditional)
        add_filter( 'woocommerce_payment_gateways', [ $this, 'register_mock_gateway' ] );

        // Shipping methods fragment — updates left-column shipping section via AJAX
        add_filter( 'woocommerce_update_order_review_fragments', [ $this, 'shipping_fragment' ] );

        // Body class for sidebar position
        add_filter( 'body_class', [ $this, 'body_class' ] );

        // Cart qty AJAX endpoint (logged-in and guest)
        add_action( 'wc_ajax_vynk_update_cart_qty',        [ $this, 'ajax_update_cart_qty' ] );
        add_action( 'wc_ajax_nopriv_vynk_update_cart_qty', [ $this, 'ajax_update_cart_qty' ] );

        // Pre-set Venezuela as default shipping country before checkout renders
        add_action( 'template_redirect', [ $this, 'preset_shipping_country' ], 5 );

        // Default shipping_country field to VE when no value is stored yet
        add_filter( 'woocommerce_checkout_get_value', [ $this, 'default_shipping_country' ], 10, 2 );
    }

    public function locate_template( $template, $template_name, $template_path, $default_path ) {
        $overridable = [ 'checkout/form-checkout.php', 'checkout/thankyou.php' ];
        if ( ! in_array( $template_name, $overridable, true ) ) {
            return $template;
        }
        $plugin_tpl = VYNK_CHECKOUT_DIR . 'templates/' . $template_name;
        return file_exists( $plugin_tpl ) ? $plugin_tpl : $template;
    }

    public function locate_cart_template( $template, $template_name, $template_path, $default_path ) {
        if ( 'cart/cart.php' !== $template_name ) {
            return $template;
        }
        $plugin_tpl = VYNK_CHECKOUT_DIR . 'templates/cart/cart.php';
        return file_exists( $plugin_tpl ) ? $plugin_tpl : $template;
    }

    public function enqueue_assets() {
        $on_checkout = is_checkout();
        $on_cart     = is_cart();

        if ( ! $on_checkout && ! $on_cart ) {
            return;
        }

        wp_enqueue_style(
            'vynk-barlow',
            'https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap',
            [], null
        );

        wp_enqueue_style(
            'vynk-checkout',
            VYNK_CHECKOUT_URL . 'assets/css/vynk-checkout.css',
            [ 'woocommerce-layout', 'woocommerce-general', 'vynk-barlow' ],
            VYNK_CHECKOUT_VERSION
        );

        wp_enqueue_script(
            'vynk-checkout',
            VYNK_CHECKOUT_URL . 'assets/js/vynk-checkout.js',
            [ 'jquery', 'wc-checkout' ],
            VYNK_CHECKOUT_VERSION,
            true
        );

        wp_localize_script( 'vynk-checkout', 'vynkCheckout', [
            'sidebarPosition' => Vynk_Settings::get('sidebar_position'),
            'isCart'          => $on_cart ? '1' : '0',
            'cartUpdateUrl'   => add_query_arg( 'wc-ajax', 'vynk_update_cart_qty', trailingslashit( home_url() ) ),
            'cartNonce'       => wp_create_nonce( 'woocommerce-cart' ),
            'applyCouponUrl'  => add_query_arg( 'wc-ajax', 'apply_coupon', trailingslashit( home_url() ) ),
            'couponNonce'     => wp_create_nonce( 'apply-coupon' ),
            'removeCouponUrl' => add_query_arg( 'wc-ajax', 'remove_coupon', trailingslashit( home_url() ) ),
            'removeCouponNonce' => wp_create_nonce( 'remove-coupon' ),
        ] );
    }

    public function enqueue_dynamic_css() {
        if ( ! is_checkout() && ! is_cart() ) {
            return;
        }

        $opts        = Vynk_Settings::get();
        $accent      = sanitize_hex_color( $opts['accent_color'] )      ?: '#bf1f1f';
        $button      = sanitize_hex_color( $opts['button_color'] )      ?: '#bf1f1f';
        $bg          = sanitize_hex_color( $opts['bg_color'] )          ?: '#ffffff';
        $border      = sanitize_hex_color( $opts['card_border_color'] ) ?: '#f0f0f0';
        $btn_dark    = $this->darken_hex( $button, 15 );
        $sidebar_cols = $opts['sidebar_position'] === 'left' ? '380px 1fr' : '1fr 380px';

        $css = ":root{
    --vynk-accent:{$accent};
    --vynk-accent-dark:{$btn_dark};
    --vynk-bg:{$bg};
    --vynk-border:{$border};
}
.vynk-place-order-btn,
#payment #place_order{background:{$button};}
.vynk-place-order-btn:hover,
#payment #place_order:hover{background:{$btn_dark};}
@media(min-width:861px){.vynk-checkout-wrapper{grid-template-columns:{$sidebar_cols};}}";

        wp_add_inline_style( 'vynk-checkout', $css );
    }

    public function remove_default_coupon_form() {
        remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
    }

    public function remove_thankyou_default_table() {
        if ( is_wc_endpoint_url( 'order-received' ) ) {
            remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
        }
    }

    public function register_mock_gateway( $gateways ) {
        if ( Vynk_Settings::get('show_mock_gateway') ) {
            $gateways[] = 'Vynk_Mock_Gateway';
        }
        return $gateways;
    }

    /**
     * Register the shipping methods section as a WC fragment.
     * WooCommerce auto-updates #vynk-shipping-methods-inner on address change.
     * We render <ul>/<li> directly — NOT the WC template which renders <tr> tags.
     */
    public function shipping_fragment( $fragments ) {
        // Re-enforce VE on both shipping and billing before reading packages.
        // WooCommerce's update_checkout AJAX may have parsed an empty or non-VE
        // billing_country from the form. Shipping plugins (e.g. Skyway) that
        // check billing_country for rate eligibility need this set to VE to return
        // their rates; without it methods disappear until the customer manually
        // selects Venezuela in the billing form.
        WC()->customer->set_shipping_country( 'VE' );
        WC()->customer->set_billing_country( 'VE' );
        WC()->customer->set_calculated_shipping( true );
        WC()->cart->calculate_shipping();

        // IMPORTANT: The fragment value must include the wrapper element with the same
        // selector ID. WooCommerce uses replaceWith(), which swaps the entire element
        // (including its ID). Without the wrapper, the ID disappears after the first
        // update and subsequent AJAX calls can no longer find the element.
        ob_start();
        echo '<div id="vynk-shipping-methods-inner" class="vynk-shipping-cards-wrap">';
        $this->render_shipping_methods();
        echo '</div>';
        $fragments['#vynk-shipping-methods-inner'] = ob_get_clean();

        ob_start();
        $this->render_totals();
        $fragments['#vynk-summary-totals'] = ob_get_clean();

        return $fragments;
    }

    /**
     * Render shipping method radio buttons as a styled <ul> list.
     * Used both for initial PHP render and for AJAX fragments.
     */
    public function render_shipping_methods() {
        $packages = WC()->shipping()->get_packages();

        if ( empty( $packages ) || ! WC()->cart->needs_shipping() ) {
            echo '<p class="vynk-shipping-placeholder">'
                . esc_html__( 'Ingresa tu dirección para ver las opciones de envío.', 'vynk-checkout' )
                . '</p>';
            return;
        }

        $chosen = WC()->session->get( 'chosen_shipping_methods', [] );

        foreach ( $packages as $i => $package ) {
            $available = $package['rates'];

            if ( empty( $available ) ) {
                // Give a context-aware hint: if country=VE but no state → ask for state
                $state = WC()->customer->get_shipping_state();
                $msg   = empty( $state )
                    ? __( 'Selecciona tu estado/provincia para ver las opciones de envío.', 'vynk-checkout' )
                    : __( 'No hay opciones de envío disponibles para tu dirección.', 'vynk-checkout' );
                echo '<p class="vynk-shipping-placeholder">' . esc_html( $msg ) . '</p>';
                continue;
            }

            $chosen_method = isset( $chosen[ $i ] ) ? $chosen[ $i ] : current( array_keys( $available ) );

            echo '<ul class="woocommerce-shipping-methods">';
            foreach ( $available as $method ) {
                $method_id  = $method->id;
                $is_checked = ( $method_id === $chosen_method );
                $uid        = 'shipping_method_' . $i . '_' . sanitize_title( $method_id );
                $cost_html  = $method->cost > 0
                    ? ': <strong>' . wc_price( $method->cost ) . '</strong>'
                    : ': <strong>' . esc_html__( 'Gratis', 'vynk-checkout' ) . '</strong>';

                printf(
                    '<li class="%s">
                        <input type="radio"
                               name="shipping_method[%d]"
                               data-index="%d"
                               id="%s"
                               value="%s"
                               class="shipping_method"
                               %s>
                        <label for="%s">%s%s</label>
                    </li>',
                    esc_attr( $is_checked ? 'vynk-shipping-selected' : '' ),
                    absint( $i ),
                    absint( $i ),
                    esc_attr( $uid ),
                    esc_attr( $method_id ),
                    checked( $is_checked, true, false ),
                    esc_attr( $uid ),
                    wp_kses_post( $method->get_label() ),
                    $cost_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                );
            }
            echo '</ul>';
        }
    }

    /**
     * Render the totals section (subtotal, coupons, fees, shipping, tax, total).
     * Used both for initial PHP render (via template) and as a WC AJAX fragment.
     *
     * Shipping is read DIRECTLY from shipping packages + session chosen method to
     * bypass WC()->cart->shipping_total which can be stale between AJAX calls.
     * Grand total is adjusted by the diff: (actual shipping) - (cached shipping).
     */
    public function render_totals() {
        // ── Read shipping directly from packages + session ───────────────────
        $ship_cost  = 0.0;
        $ship_tax   = 0.0;
        $has_rates  = false; // true when at least one shipping zone returned methods
        if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
            $packages = WC()->shipping()->get_packages();
            $chosen   = WC()->session->get( 'chosen_shipping_methods', [] );
            foreach ( $packages as $i => $pkg ) {
                if ( ! empty( $pkg['rates'] ) ) {
                    $has_rates = true;
                }
                $mid  = isset( $chosen[ $i ] ) ? $chosen[ $i ] : '';
                $rate = ( $mid && isset( $pkg['rates'][ $mid ] ) )
                    ? $pkg['rates'][ $mid ]
                    : ( ! empty( $pkg['rates'] ) ? reset( $pkg['rates'] ) : null );
                if ( $rate ) {
                    $ship_cost += (float) $rate->cost;
                    $ship_tax  += (float) array_sum( (array) $rate->taxes );
                }
            }
        }
        $ship_display = WC()->cart->display_prices_including_tax()
            ? $ship_cost + $ship_tax
            : $ship_cost;

        // ── Render ────────────────────────────────────────────────────────────
        echo '<div class="vynk-summary-totals" id="vynk-summary-totals">';

        echo '<div class="vynk-totals-row">';
        echo '<span>' . esc_html__( 'Subtotal', 'vynk-checkout' ) . '</span>';
        echo '<span>'; wc_cart_totals_subtotal_html(); echo '</span>';
        echo '</div>';

        foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
            echo '<div class="vynk-totals-row vynk-totals-discount">';
            echo '<span>' . sprintf( esc_html__( 'Cupón: %s', 'vynk-checkout' ), '<em>' . esc_html( $code ) . '</em>' ) . '</span>';
            echo '<span class="vynk-discount">-'; wc_cart_totals_coupon_html( $coupon ); echo '</span>';
            echo '</div>';
        }

        foreach ( WC()->cart->get_fees() as $fee ) {
            echo '<div class="vynk-totals-row">';
            echo '<span>' . esc_html( $fee->name ) . '</span>';
            echo '<span>'; wc_cart_totals_fee_html( $fee ); echo '</span>';
            echo '</div>';
        }

        if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
            echo '<div class="vynk-totals-row">';
            echo '<span>' . esc_html__( 'Envío', 'vynk-checkout' ) . '</span>';
            echo '<span>';
            if ( ! $has_rates ) {
                // No matching shipping zone yet (e.g. no state selected) — don't lie
                echo '<em class="vynk-shipping-pending">' . esc_html__( 'Por definir', 'vynk-checkout' ) . '</em>';
            } elseif ( $ship_display > 0 ) {
                echo wc_price( $ship_display ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            } else {
                echo esc_html__( 'Gratis', 'vynk-checkout' );
            }
            echo '</span>';
            echo '</div>';
        }

        if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
            echo '<div class="vynk-totals-row">';
            echo '<span>' . esc_html__( 'Impuesto', 'vynk-checkout' ) . '</span>';
            echo '<span>'; wc_cart_totals_taxes_total_html(); echo '</span>';
            echo '</div>';
        }

        // Grand total: adjust WC cached total by (actual_ship - cached_ship)
        // so it's always correct even when WC()->cart->shipping_total is stale.
        $cached_ship = (float) WC()->cart->get_shipping_total();
        $wc_total    = (float) WC()->cart->get_total( 'edit' );
        $grand       = $wc_total - $cached_ship + $ship_cost;

        echo '<div class="vynk-totals-row vynk-totals-grand">';
        echo '<span>' . esc_html__( 'Total', 'vynk-checkout' ) . '</span>';
        echo '<span>' . wc_price( $grand ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render cart-page totals block (subtotal → shipping → tax → grand total).
     * Used both for initial PHP render and for the AJAX JSON response.
     */
    public function render_cart_totals() {
        echo '<div id="vynk-cart-totals-wrap">';

        // Read shipping directly from packages + session to avoid stale cached totals.
        // Initialized here (outside the needs_shipping block) so the grand total can
        // always use $cart_ship_cost regardless of whether the shipping block renders.
        $cart_ship_cost = 0.0;
        $cart_ship_tax  = 0.0;
        $cart_has_rates = false;
        if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
            $packages = WC()->shipping()->get_packages();
            $chosen   = WC()->session->get( 'chosen_shipping_methods', [] );
            foreach ( $packages as $i => $pkg ) {
                if ( ! empty( $pkg['rates'] ) ) { $cart_has_rates = true; }
                $mid  = isset( $chosen[ $i ] ) ? $chosen[ $i ] : '';
                $rate = ( $mid && isset( $pkg['rates'][ $mid ] ) )
                    ? $pkg['rates'][ $mid ]
                    : ( ! empty( $pkg['rates'] ) ? reset( $pkg['rates'] ) : null );
                if ( $rate ) {
                    $cart_ship_cost += (float) $rate->cost;
                    $cart_ship_tax  += (float) array_sum( (array) $rate->taxes );
                }
            }
        }

        echo '<div class="vynk-cart-totals-body">';

        echo '<div class="vynk-totals-row">';
        echo '<span>' . esc_html__( 'Subtotal', 'vynk-checkout' ) . '</span>';
        echo '<span>'; wc_cart_totals_subtotal_html(); echo '</span>';
        echo '</div>';

        foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
            echo '<div class="vynk-totals-row vynk-totals-discount">';
            echo '<span>' . sprintf( esc_html__( 'Cupón: %s', 'vynk-checkout' ), '<em>' . esc_html( $code ) . '</em>' ) . '</span>';
            echo '<span class="vynk-discount">-'; wc_cart_totals_coupon_html( $coupon ); echo '</span>';
            echo '</div>';
        }

        foreach ( WC()->cart->get_fees() as $fee ) {
            echo '<div class="vynk-totals-row">';
            echo '<span>' . esc_html( $fee->name ) . '</span>';
            echo '<span>'; wc_cart_totals_fee_html( $fee ); echo '</span>';
            echo '</div>';
        }

        if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
            $cart_ship_display = WC()->cart->display_prices_including_tax()
                ? $cart_ship_cost + $cart_ship_tax
                : $cart_ship_cost;

            echo '<div class="vynk-totals-row">';
            echo '<span>' . esc_html__( 'Envío', 'vynk-checkout' ) . '</span>';
            echo '<span>';
            if ( ! $cart_has_rates ) {
                echo '<em class="vynk-shipping-pending">' . esc_html__( 'Por definir', 'vynk-checkout' ) . '</em>';
            } elseif ( $cart_ship_display > 0 ) {
                echo wc_price( $cart_ship_display ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            } else {
                echo esc_html__( 'Gratis', 'vynk-checkout' );
            }
            echo '</span></div>';
        }

        if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
            echo '<div class="vynk-totals-row">';
            echo '<span>' . esc_html__( 'Impuesto', 'vynk-checkout' ) . '</span>';
            echo '<span>'; wc_cart_totals_taxes_total_html(); echo '</span>';
            echo '</div>';
        }

        echo '</div>'; // .vynk-cart-totals-body

        // Grand total: same stale-cache adjustment used in render_totals() for checkout.
        $cached_ship = (float) WC()->cart->get_shipping_total();
        $wc_total    = (float) WC()->cart->get_total( 'edit' );
        $grand       = $wc_total - $cached_ship + $cart_ship_cost;

        echo '<div class="vynk-cart-grand-total">';
        echo '<span>' . esc_html__( 'Total', 'vynk-checkout' ) . '</span>';
        echo '<span class="vynk-cart-grand-amount">' . wc_price( $grand ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';

        echo '</div>'; // #vynk-cart-totals-wrap
    }

    /**
     * WC AJAX: update cart quantities and return totals + row prices as JSON.
     * Endpoint: ?wc-ajax=vynk_update_cart_qty
     */
    public function ajax_update_cart_qty() {
        check_ajax_referer( 'woocommerce-cart', 'nonce' );

        // Re-enforce Venezuela before any recalculation. The AJAX context does not go
        // through template_redirect, so the customer object may not have VE set yet.
        // Shipping plugins that gate rates on billing_country need this to return
        // real rates instead of the $30 minimum.
        WC()->customer->set_shipping_country( 'VE' );
        WC()->customer->set_billing_country( 'VE' );
        WC()->customer->set_calculated_shipping( true );

        // Save chosen shipping method if provided (from the cart shipping selector)
        if ( isset( $_POST['shipping_method'] ) && is_array( $_POST['shipping_method'] ) ) { // phpcs:ignore
            $chosen = [];
            foreach ( wp_unslash( $_POST['shipping_method'] ) as $i => $method ) { // phpcs:ignore
                $chosen[ absint( $i ) ] = wc_clean( $method );
            }
            WC()->session->set( 'chosen_shipping_methods', $chosen );
            WC()->cart->calculate_shipping();
        }

        // Build qty map: product_id:variation_id => qty
        // (using product identity instead of cart_item_key which may differ when page is cached)
        $qty_map = isset( $_POST['qty'] ) ? (array) wp_unslash( $_POST['qty'] ) : []; // phpcs:ignore

        foreach ( WC()->cart->get_cart() as $ckey => $item ) {
            $pid    = (string) $item['product_id'];
            $vid    = (string) ( isset( $item['variation_id'] ) ? $item['variation_id'] : 0 );
            $lookup = $pid . ':' . $vid;
            if ( isset( $qty_map[ $lookup ] ) ) {
                WC()->cart->set_quantity( $ckey, max( 0, absint( $qty_map[ $lookup ] ) ), false );
            }
        }

        // Recalculate once after all updates
        WC()->cart->calculate_totals();

        // Build per-row subtotals map: { "product_id:variation_id" => html }
        // Keyed by product identity so the JS can find the DOM element regardless of cache
        $row_prices = [];
        foreach ( WC()->cart->get_cart() as $ckey => $item ) {
            $_product = $item['data'];
            if ( ! $_product || ! $_product->exists() || $item['quantity'] <= 0 ) {
                continue;
            }
            $pid    = (string) $item['product_id'];
            $vid    = (string) ( isset( $item['variation_id'] ) ? $item['variation_id'] : 0 );
            $row_prices[ $pid . ':' . $vid ] = apply_filters(
                'woocommerce_cart_item_subtotal',
                WC()->cart->get_product_subtotal( $_product, $item['quantity'] ),
                $item,
                $ckey
            );
        }

        // Render totals HTML
        ob_start();
        $this->render_cart_totals();
        $totals_html = ob_get_clean();

        // Render updated shipping methods (so the selector stays in sync with totals)
        ob_start();
        $this->render_shipping_methods();
        $shipping_html = ob_get_clean();

        wp_send_json_success( [
            'totals_html'   => $totals_html,
            'shipping_html' => $shipping_html,
            'row_prices'    => $row_prices,
            'cart_count'    => WC()->cart->get_cart_contents_count(),
        ] );
    }

    /**
     * Pre-set Venezuela as the customer's shipping country before checkout AND cart
     * templates render, so WC calculates shipping packages on the initial page load
     * regardless of whether the customer has previously entered an address.
     */
    public function preset_shipping_country() {
        if ( ! is_checkout() && ! is_cart() ) {
            return;
        }

        // Force VE as both shipping AND billing destination.
        // Some shipping plugins (e.g. Skyway) read billing_country to decide whether
        // to return rates. If billing_country is empty on initial page load the plugin
        // returns nothing, making the methods disappear after WC's auto update_checkout.
        WC()->customer->set_shipping_country( 'VE' );
        WC()->customer->set_billing_country( 'VE' );

        // Mark address as "calculated" so WC shows the shipping row even when
        // the store option "Hide shipping costs until an address is entered" is on.
        WC()->customer->set_calculated_shipping( true );

        // Reset the shipping cache before recalculating. Without this, WooCommerce may
        // return stale session-cached rates computed before VE was set as the country,
        // causing the shipping plugin to return its $30 minimum on initial page load.
        WC()->shipping()->reset_shipping();

        // Force WooCommerce to recalculate shipping packages with VE as destination
        // so render_shipping_methods() finds real rates on the initial page load.
        WC()->cart->calculate_shipping();
    }

    /**
     * Return 'VE' as the default value for shipping_country AND billing_country when
     * no value is stored. This ensures both fields default to Venezuela on initial
     * checkout render so shipping plugins that read billing_country also return rates.
     */
    public function default_shipping_country( $value, $input ) {
        if ( ( 'shipping_country' === $input || 'billing_country' === $input ) && empty( $value ) ) {
            return 'VE';
        }
        return $value;
    }

    public function body_class( $classes ) {
        if ( is_checkout() || is_cart() ) {
            $classes[] = 'vynk-sidebar-' . esc_attr( Vynk_Settings::get('sidebar_position') );
        }
        return $classes;
    }

    private function darken_hex( $hex, $percent ) {
        $hex = ltrim( $hex, '#' );
        if ( strlen($hex) === 3 ) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = max( 0, (int)( hexdec( substr($hex,0,2) ) * (1-$percent/100) ) );
        $g = max( 0, (int)( hexdec( substr($hex,2,2) ) * (1-$percent/100) ) );
        $b = max( 0, (int)( hexdec( substr($hex,4,2) ) * (1-$percent/100) ) );
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
