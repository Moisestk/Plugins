/**
 * Vynk Checkout & Cart — UI JavaScript
 */
jQuery( function ( $ ) {

    // ═══════════════════════════════════════════════════════
    // ACCORDION
    // ═══════════════════════════════════════════════════════
    var $cards = $( '.vynk-accordion-card' );

    // Open first card by default
    $cards.first().addClass( 'is-open' );

    $( '.vynk-accordion-header' ).on( 'click', function () {
        var $card = $( this ).closest( '.vynk-accordion-card' );
        var isOpen = $card.hasClass( 'is-open' );

        // Close all, open clicked
        $cards.removeClass( 'is-open' );
        $( '.vynk-accordion-header' ).attr( 'aria-expanded', 'false' );

        if ( ! isOpen ) {
            $card.addClass( 'is-open' );
            $( this ).attr( 'aria-expanded', 'true' );
        }
    } );

    // Auto-advance: when billing email is filled, open next card
    $( '#billing_email' ).on( 'blur', function () {
        if ( $( this ).val() ) {
            var $step2 = $( '#vynk-step-2, #vynk-step-shipping' ).first();
            if ( $step2.length && ! $step2.hasClass( 'is-open' ) ) {
                $cards.removeClass( 'is-open' );
                $step2.addClass( 'is-open' );
                $( '#vynk-step-1' ).addClass( 'is-done' ).removeClass( 'is-open' );
            }
        }
    } );

    // ═══════════════════════════════════════════════════════
    // SHIPPING ADDRESS TOGGLE
    // ═══════════════════════════════════════════════════════
    var $shipToggle  = $( '#ship-to-different-address-checkbox' );
    var $shipAddress = $( '.shipping_address' );

    $shipAddress.hide();
    if ( $shipToggle.is( ':checked' ) ) {
        $shipAddress.show();
    }

    $shipToggle.on( 'change', function () {
        if ( $( this ).is( ':checked' ) ) {
            $shipAddress.slideDown( 200 );
            var $card = $( '#vynk-step-2' );
            $cards.removeClass( 'is-open' );
            $card.addClass( 'is-open' );
        } else {
            $shipAddress.slideUp( 200 );
        }
    } );

    // ═══════════════════════════════════════════════════════
    // COUPON TOGGLE + AJAX APPLY
    // IMPORTANT: The coupon input/button are plain HTML (not a
    // nested <form>) — buttons are type="button" so they never
    // submit the checkout form accidentally.
    // ═══════════════════════════════════════════════════════
    $( '#vynk-coupon-toggle' ).on( 'click', function () {
        var $form    = $( '#vynk-coupon-form' );
        var $area    = $( '.vynk-coupon-area' );
        var $label   = $( this ).find( 'span' );
        var isHidden = $form.is( ':hidden' );

        $form.slideToggle( 220 );
        $area.toggleClass( 'vynk-coupon-open', isHidden );
        $label.text( isHidden ? 'Ocultar cupón' : '¿Tienes un código de cupón?' );
    } );

    function showCouponMsg( html, isError ) {
        var $msg = $( '#vynk-coupon-msg' );
        $msg.html( html );
        $msg.removeClass( 'vynk-coupon-msg--ok vynk-coupon-msg--error' );
        $msg.addClass( isError ? 'vynk-coupon-msg--error' : 'vynk-coupon-msg--ok' );
    }

    $( document ).on( 'click', '#vynk-coupon-apply', function () {
        var $btn  = $( this );
        var code  = $( '#vynk-coupon-code' ).val().trim();

        if ( ! code ) {
            showCouponMsg( 'Ingresa un código de cupón.', true );
            return;
        }

        $btn.prop( 'disabled', true ).text( '…' );
        $( '#vynk-coupon-msg' ).html( '' );

        $.ajax( {
            url:  vynkCheckout.applyCouponUrl,
            type: 'POST',
            data: { coupon_code: code, security: vynkCheckout.couponNonce },
            success: function ( res ) {
                // WC returns raw HTML notices
                var hasError = res.indexOf( 'woocommerce-error' ) !== -1
                            || res.indexOf( 'error' ) !== -1;
                showCouponMsg( res, hasError );
                if ( ! hasError ) {
                    $( '#vynk-coupon-code' ).val( '' );
                    $( document.body ).trigger( 'applied_coupon', [ code ] );
                    $( document.body ).trigger( 'update_checkout' );
                }
            },
            error: function () {
                showCouponMsg( 'Error al aplicar el cupón. Inténtalo de nuevo.', true );
            },
            complete: function () {
                $btn.prop( 'disabled', false ).text( 'Aplicar' );
            }
        } );
    } );

    // Allow submit with Enter key inside coupon input
    $( document ).on( 'keydown', '#vynk-coupon-code', function ( e ) {
        if ( e.key === 'Enter' ) {
            e.preventDefault();
            $( '#vynk-coupon-apply' ).trigger( 'click' );
        }
    } );

    // ═══════════════════════════════════════════════════════
    // SHIPPING COUNTRY/STATE CHANGE → recalculate shipping
    // shipping_country is fixed to VE (hidden input), so only
    // state changes need to trigger recalculation.
    // Billing country changes do NOT affect shipping zones.
    // ═══════════════════════════════════════════════════════
    $( document ).on( 'change', '#shipping_state', function () {
        $( document.body ).trigger( 'update_checkout', { update_shipping_method: true } );
    } );

    // ═══════════════════════════════════════════════════════
    // HIGHLIGHT SELECTED SHIPPING METHOD
    // ═══════════════════════════════════════════════════════
    function highlightShipping() {
        $( '.woocommerce-shipping-methods li' )
            .css( { 'border-color': '', 'background': '' } )
            .removeClass( 'vynk-shipping-selected' );
        $( '.woocommerce-shipping-methods input:checked' )
            .closest( 'li' )
            .addClass( 'vynk-shipping-selected' );
    }
    $( document ).on( 'change', '.woocommerce-shipping-methods input', highlightShipping );
    highlightShipping();

    // ═══════════════════════════════════════════════════════
    // HIGHLIGHT SELECTED PAYMENT GATEWAY
    // ═══════════════════════════════════════════════════════
    function highlightGateway() {
        $( '#payment ul.payment_methods li' ).removeClass( 'payment_method_selected' );
        $( '#payment ul.payment_methods input:checked' )
            .closest( 'li' ).addClass( 'payment_method_selected' );
    }
    $( document ).on( 'change', '#payment ul.payment_methods input[type="radio"]', highlightGateway );
    highlightGateway();

    // Re-apply highlight states after WC AJAX checkout update
    $( document.body ).on( 'updated_checkout', function () {
        highlightShipping();
        highlightGateway();
        if ( $( '#vynk-shipping-methods-inner .woocommerce-shipping-methods' ).length ) {
            var $shippingCard = $( '#vynk-step-shipping' );
            if ( ! $shippingCard.hasClass( 'is-open' ) ) {
                $shippingCard.addClass( 'is-open' );
            }
        }
    } );

    // ═══════════════════════════════════════════════════════
    // INLINE VALIDATION ERRORS
    // WC prepends .woocommerce-NoticeGroup-checkout to the form
    // on failed checkout. We hide that block and show small
    // error messages directly below each invalid field instead.
    // ═══════════════════════════════════════════════════════

    function showInlineErrors() {
        $( '.woocommerce-invalid' ).each( function () {
            var $row = $( this );
            if ( $row.find( '.vynk-field-error' ).length ) return;
            var msg = $row.hasClass( 'woocommerce-invalid-email' )
                ? 'Ingresa un correo electrónico válido.'
                : 'Este campo es requerido.';
            $row.find( '.woocommerce-input-wrapper' ).after(
                '<span class="vynk-field-error" aria-live="polite">' + msg + '</span>'
            );
        } );

        // Open the first accordion card that contains an invalid field
        var $firstInvalid = $( '.woocommerce-invalid' ).first();
        if ( $firstInvalid.length ) {
            var $targetCard = $firstInvalid.closest( '.vynk-accordion-card' );
            if ( $targetCard.length && ! $targetCard.hasClass( 'is-open' ) ) {
                $cards.removeClass( 'is-open' );
                $( '.vynk-accordion-header' ).attr( 'aria-expanded', 'false' );
                $targetCard.addClass( 'is-open' );
                $targetCard.find( '.vynk-accordion-header' ).attr( 'aria-expanded', 'true' );
            }
            $( 'html, body' ).animate(
                { scrollTop: $firstInvalid.offset().top - 140 }, 280
            );
        }
    }

    $( document.body ).on( 'checkout_error', function () {
        showInlineErrors();
    } );

    // Remove inline error when the user corrects the field
    $( document ).on(
        'input change',
        '.woocommerce-invalid input, .woocommerce-invalid select, .woocommerce-invalid textarea',
        function () {
            var $row = $( this ).closest( '.form-row' );
            $row.removeClass(
                'woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email'
            );
            $row.find( '.vynk-field-error' ).remove();
        }
    );

    // ═══════════════════════════════════════════════════════
    // SIDEBAR POSITION (left/right)
    // ═══════════════════════════════════════════════════════
    if ( typeof vynkCheckout !== 'undefined' && vynkCheckout.sidebarPosition === 'left' ) {
        $( '.vynk-checkout-sidebar, .vynk-cart-sidebar' ).css( 'order', '-1' );
        $( '.vynk-checkout-main, .vynk-cart-main' ).css( 'order', '1' );
    }

    // ═══════════════════════════════════════════════════════
    // MOCK GATEWAY — Card input formatting
    // ═══════════════════════════════════════════════════════

    // Card number: groups of 4
    $( document ).on( 'input', '#vynk-card-number', function () {
        var raw = $( this ).val().replace( /\D/g, '' ).substring( 0, 16 );
        $( this ).val( raw.replace( /(.{4})/g, '$1 ' ).trim() );
    } );

    // Expiry: MM / YY
    $( document ).on( 'input', '#vynk-card-expiry', function () {
        var raw = $( this ).val().replace( /\D/g, '' ).substring( 0, 4 );
        $( this ).val( raw.length >= 3 ? raw.slice(0,2) + ' / ' + raw.slice(2) : raw );
    } );
    $( document ).on( 'keydown', '#vynk-card-expiry', function ( e ) {
        if ( e.key === 'Backspace' && $( this ).val().endsWith( ' / ' ) ) {
            $( this ).val( $( this ).val().slice( 0, -3 ) );
            e.preventDefault();
        }
    } );

    // CVV: digits only
    $( document ).on( 'input', '#vynk-card-cvv', function () {
        $( this ).val( $( this ).val().replace( /\D/g, '' ).substring( 0, 4 ) );
    } );

    // ═══════════════════════════════════════════════════════
    // CART PAGE — qty stepper + AJAX update
    // ═══════════════════════════════════════════════════════
    if ( typeof vynkCheckout !== 'undefined' && vynkCheckout.isCart === '1' ) {

        var cartUpdateTimer;

        function ajaxUpdateCart( snapshot ) {
            var $form = $( 'form.woocommerce-cart-form' );
            if ( ! $form.length ) return;

            $form.addClass( 'vynk-cart-updating' );

            var postData = $.extend( { nonce: vynkCheckout.cartNonce }, snapshot );

            $.ajax( {
                url:     vynkCheckout.cartUpdateUrl,
                type:    'POST',
                data:    postData,
                success: function ( res ) {
                    if ( ! res || ! res.success ) {
                        $form.removeClass( 'vynk-cart-updating' );
                        return;
                    }
                    var d = res.data;

                    if ( d.totals_html ) {
                        $( '#vynk-cart-totals-wrap' ).replaceWith( d.totals_html );
                    }

                    if ( d.shipping_html ) {
                        $( '#vynk-cart-shipping-inner' ).html( d.shipping_html );
                        highlightShipping();
                    }

                    if ( d.row_prices ) {
                        $.each( d.row_prices, function ( key, html ) {
                            var parts = key.split( ':' );
                            var pid   = parts[0];
                            var vid   = parts[1] || '0';
                            $( '.vynk-cart-row-price[data-product-id="' + pid + '"][data-variation-id="' + vid + '"]' ).html( html );
                        } );
                    }

                    $form.removeClass( 'vynk-cart-updating' );
                },
                error: function () {
                    $form.removeClass( 'vynk-cart-updating' );
                }
            } );
        }

        // When a shipping method is selected in the cart, update totals via AJAX
        $( document ).on( 'change', '#vynk-cart-shipping-inner .shipping_method', function () {
            var $form = $( 'form.woocommerce-cart-form' );
            if ( ! $form.length ) return;
            $form.addClass( 'vynk-cart-updating' );

            var snapshot = {};
            $form.find( 'input[type="number"][data-product-id]' ).each( function () {
                var pid = $( this ).data( 'product-id' );
                var vid = $( this ).data( 'variation-id' ) || 0;
                snapshot[ 'qty[' + pid + ':' + vid + ']' ] = $( this ).val();
            } );

            // Include chosen shipping method so the server session is updated
            snapshot[ 'shipping_method[0]' ] = $( this ).val();

            ajaxUpdateCart( snapshot );
        } );

        $( document ).on( 'click', '.vynk-qty-btn', function () {
            var $input  = $( this ).siblings( 'input[type="number"]' );
            var val     = parseInt( $input.val(), 10 ) || 1;
            var step    = parseInt( $input.attr( 'step' ), 10 ) || 1;
            var min     = parseInt( $input.attr( 'min' ), 10 );
            var maxAttr = parseInt( $input.attr( 'max' ), 10 );
            if ( isNaN( min ) || min < 0 ) min = 1;
            var max = ( ! isNaN( maxAttr ) && maxAttr > 0 ) ? maxAttr : 9999;

            if ( $( this ).hasClass( 'vynk-qty-minus' ) ) {
                val = Math.max( min, val - step );
            } else {
                val = Math.min( max, val + step );
            }
            $input.val( val );

            // Capture qty snapshot at click time (before WC JS can reset inputs)
            var snapshot = {};
            $( 'form.woocommerce-cart-form' ).find( 'input[type="number"][data-product-id]' ).each( function () {
                var pid = $( this ).data( 'product-id' );
                var vid = $( this ).data( 'variation-id' ) || 0;
                snapshot[ 'qty[' + pid + ':' + vid + ']' ] = $( this ).val();
            } );

            clearTimeout( cartUpdateTimer );
            cartUpdateTimer = setTimeout( function () {
                ajaxUpdateCart( snapshot );
            }, 600 );
        } );
    }

} );
