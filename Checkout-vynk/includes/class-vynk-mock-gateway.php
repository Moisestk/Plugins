<?php
defined( 'ABSPATH' ) || exit;

class Vynk_Mock_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'vynk_mockup';
        $this->method_title       = __( 'Vynk Card (Mockup)', 'vynk-checkout' );
        $this->method_description = __( 'UI-only card payment placeholder. No real transaction is processed.', 'vynk-checkout' );
        $this->has_fields         = true;
        $this->supports           = [ 'products' ];

        $this->init_form_fields();
        $this->init_settings();

        $this->title   = $this->get_option( 'title', __( 'Credit / Debit Card', 'vynk-checkout' ) );
        $this->enabled = $this->get_option( 'enabled', 'yes' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Enable', 'vynk-checkout' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Vynk Card (Mockup)', 'vynk-checkout' ),
                'default' => 'yes',
            ],
            'title' => [
                'title'   => __( 'Title', 'vynk-checkout' ),
                'type'    => 'text',
                'default' => __( 'Credit / Debit Card', 'vynk-checkout' ),
            ],
        ];
    }

    /**
     * Render the decorative card fields inside the gateway payment box.
     */
    public function payment_fields() {
        ?>
        <div class="vynk-card-fields">
            <div class="vynk-card-brands">
                <svg viewBox="0 0 48 16" width="38" height="13" aria-label="Visa"><text x="0" y="13" font-family="Barlow,Arial,sans-serif" font-size="14" font-weight="700" fill="#1a1f71">VISA</text></svg>
                <svg viewBox="0 0 38 24" width="32" height="20" aria-label="Mastercard"><circle cx="15" cy="12" r="10" fill="#EB001B"/><circle cx="23" cy="12" r="10" fill="#F79E1B"/><path d="M19 5.3a10 10 0 0 1 0 13.4A10 10 0 0 1 19 5.3z" fill="#FF5F00"/></svg>
                <svg viewBox="0 0 50 16" width="40" height="13" aria-label="Amex"><text x="0" y="13" font-family="Arial,sans-serif" font-size="11" font-weight="700" fill="#2E77BC">AMEX</text></svg>
            </div>

            <div class="vynk-cf-row">
                <label class="vynk-cf-label" for="vynk-card-number"><?php esc_html_e( 'Card Number', 'vynk-checkout' ); ?></label>
                <div class="vynk-cf-input-wrap">
                    <input type="text" id="vynk-card-number" class="vynk-cf-input vynk-card-number-input"
                           placeholder="0000 0000 0000 0000" maxlength="19"
                           autocomplete="cc-number" inputmode="numeric" />
                    <svg class="vynk-cf-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
            </div>

            <div class="vynk-cf-row-2col">
                <div class="vynk-cf-row">
                    <label class="vynk-cf-label" for="vynk-card-expiry"><?php esc_html_e( 'Expiry', 'vynk-checkout' ); ?></label>
                    <input type="text" id="vynk-card-expiry" class="vynk-cf-input"
                           placeholder="MM / YY" maxlength="7"
                           autocomplete="cc-exp" inputmode="numeric" />
                </div>
                <div class="vynk-cf-row">
                    <label class="vynk-cf-label" for="vynk-card-cvv">CVV</label>
                    <div class="vynk-cf-input-wrap">
                        <input type="text" id="vynk-card-cvv" class="vynk-cf-input"
                               placeholder="•••" maxlength="4"
                               autocomplete="cc-csc" inputmode="numeric" />
                        <svg class="vynk-cf-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                </div>
            </div>

            <p class="vynk-cf-note">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <?php esc_html_e( 'Your payment info is secure and encrypted.', 'vynk-checkout' ); ?>
            </p>
        </div>
        <?php
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wc_add_notice( __( 'Order not found.', 'vynk-checkout' ), 'error' );
            return [ 'result' => 'failure' ];
        }
        $order->payment_complete();
        $order->add_order_note( __( 'Order placed via Vynk Checkout (mockup gateway).', 'vynk-checkout' ) );
        WC()->cart->empty_cart();
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        ];
    }
}
