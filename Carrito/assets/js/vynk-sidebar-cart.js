/**
 * Vynk Sidebar Cart — JavaScript
 * Controla el panel lateral: abrir/cerrar, fetch AJAX, render de items,
 * actualización de cantidades, eliminación de items y accesibilidad.
 */
( function ( $ ) {
	'use strict';

	/* -------------------------------------------------------------------------
	   1. Config global (disponible antes del DOM)
	   ---------------------------------------------------------------------- */
	var cfg      = window.vynkSidebarCart || {};
	var WC_AJAX  = '/?wc-ajax=';
	var nonce    = cfg.nonce || '';
	var autoOpen = cfg.autoOpen === 'yes';

	/* -------------------------------------------------------------------------
	   2. Todo lo demás se inicializa cuando el DOM está listo
	      (DOMContentLoaded asegura que el panel de wp_footer p.99 ya existe)
	   ---------------------------------------------------------------------- */
	$( function () {

		var isLoading = false;
		var isOpen    = false;

		// Selectores — cacheados DESPUÉS de que el DOM está completo
		var $panel    = $( '#vynk-cart-panel' );
		var $overlay  = $( '#vynk-cart-overlay' );
		var $body     = $( 'body' );
		var $items    = $( '#vynk-cart-items' );
		var $empty    = $( '#vynk-cart-empty' );
		var $footer   = $( '#vynk-cart-footer' );
		var $subtotal = $( '#vynk-cart-subtotal' );

		/* ---------------------------------------------------------------------
		   3. Abrir / Cerrar panel
		   ------------------------------------------------------------------ */
		function openCart() {
			isOpen = true;
			$panel.addClass( 'is-open' ).attr( 'aria-hidden', 'false' );
			$overlay.addClass( 'is-visible' ).attr( 'aria-hidden', 'false' );
			$body.addClass( 'vynk-cart-open' );
			setTimeout( function () {
				$( '#vynk-cart-close' ).trigger( 'focus' );
			}, 320 );
		}

		function closeCart() {
			isOpen = false;
			$panel.removeClass( 'is-open' ).attr( 'aria-hidden', 'true' );
			$overlay.removeClass( 'is-visible' ).attr( 'aria-hidden', 'true' );
			$body.removeClass( 'vynk-cart-open' );
		}

		function openAndRefresh() {
			openCart();
			fetchCart();
		}

		/* ---------------------------------------------------------------------
		   4. Fetch del carrito vía WC AJAX
		   ------------------------------------------------------------------ */
		function fetchCart( callback ) {
			if ( isLoading ) {
				return;
			}
			isLoading = true;
			setLoading( true );

			$.ajax( {
				url: WC_AJAX + 'vynk_get_cart',
				method: 'POST',
				data: { nonce: nonce },
				success: function ( response ) {
					if ( response && response.success ) {
						renderCart( response.data );
						if ( typeof callback === 'function' ) {
							callback( response.data );
						}
					}
				},
				error: function () {
					// Error silencioso
				},
				complete: function () {
					isLoading = false;
					setLoading( false );
				}
			} );
		}

		/* ---------------------------------------------------------------------
		   5. Render del carrito
		   ------------------------------------------------------------------ */
		function renderCart( data ) {
			updateAllBadges( data.count );

			if ( data.isEmpty ) {
				$items.empty();
				$empty.removeAttr( 'aria-hidden' );
				$footer.hide();
				return;
			}

			$empty.attr( 'aria-hidden', 'true' );
			$footer.show();
			$subtotal.html( data.subtotal );

			var html = '';
			$.each( data.items, function ( i, item ) {
				html += buildItemHTML( item );
			} );
			$items.html( html );
		}

		function updateAllBadges( count ) {
			$( '.vynk-cart-count-badge' ).each( function () {
				$( this ).text( count );
				if ( count === 0 ) {
					$( this ).attr( 'data-empty', 'true' );
				} else {
					$( this ).removeAttr( 'data-empty' );
				}
			} );
		}

		/* ---------------------------------------------------------------------
		   6. Construir HTML de un item
		   ------------------------------------------------------------------ */
		function buildItemHTML( item ) {
			var maxQty        = item.max === -1 ? 999 : item.max;
			var minusDisabled = item.qty <= item.min ? ' disabled' : '';
			var plusDisabled  = maxQty !== 999 && item.qty >= maxQty ? ' disabled' : '';

			var attributesHTML = item.attributes
				? '<span class="vynk-cart-item-attributes">' + escHtml( item.attributes ) + '</span>'
				: '';

			return '<div class="vynk-cart-item" data-key="' + escAttr( item.key ) + '">' +

				'<div class="vynk-cart-item-image">' +
					'<img src="' + escAttr( item.image ) + '" alt="' + escAttr( item.name ) + '" loading="lazy" />' +
				'</div>' +

				'<div class="vynk-cart-item-info">' +
					'<a href="' + escAttr( item.permalink ) + '" class="vynk-cart-item-name">' + escHtml( item.name ) + '</a>' +
					attributesHTML +
					'<span class="vynk-cart-item-price">' + item.price + '</span>' +
				'</div>' +

				'<div class="vynk-cart-item-controls">' +
					'<div class="vynk-qty-wrap">' +
						'<button class="vynk-qty-btn vynk-qty-minus" aria-label="Reducir cantidad"' + minusDisabled + '>&minus;</button>' +
						'<span class="vynk-qty-display">' + parseInt( item.qty, 10 ) + '</span>' +
						'<button class="vynk-qty-btn vynk-qty-plus" aria-label="Aumentar cantidad"' + plusDisabled + '>&#43;</button>' +
					'</div>' +
					'<span class="vynk-cart-item-subtotal">' + item.subtotal + '</span>' +
					'<button class="vynk-cart-item-remove" aria-label="Eliminar ' + escAttr( item.name ) + '">' +
						'<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
							'<polyline points="3 6 5 6 21 6"/>' +
							'<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>' +
							'<path d="M10 11v6"/><path d="M14 11v6"/>' +
							'<path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>' +
						'</svg>' +
					'</button>' +
				'</div>' +

			'</div>';
		}

		/* ---------------------------------------------------------------------
		   7. Actualizar cantidad
		   ------------------------------------------------------------------ */
		function updateQty( key, qty ) {
			if ( isLoading ) {
				return;
			}
			isLoading = true;
			setLoading( true );

			$.ajax( {
				url: WC_AJAX + 'vynk_update_qty',
				method: 'POST',
				data: { nonce: nonce, key: key, qty: qty },
				success: function ( response ) {
					if ( response && response.success ) {
						renderCart( response.data );
					}
				},
				complete: function () {
					isLoading = false;
					setLoading( false );
				}
			} );
		}

		/* ---------------------------------------------------------------------
		   8. Eliminar item
		   ------------------------------------------------------------------ */
		function removeItem( key ) {
			if ( isLoading ) {
				return;
			}

			$( '.vynk-cart-item[data-key="' + key + '"]' ).css( {
				opacity: 0.4,
				'pointer-events': 'none'
			} );

			isLoading = true;
			setLoading( true );

			$.ajax( {
				url: WC_AJAX + 'vynk_remove_item',
				method: 'POST',
				data: { nonce: nonce, key: key },
				success: function ( response ) {
					if ( response && response.success ) {
						renderCart( response.data );
					}
				},
				complete: function () {
					isLoading = false;
					setLoading( false );
				}
			} );
		}

		/* ---------------------------------------------------------------------
		   9. Loading state
		   ------------------------------------------------------------------ */
		function setLoading( state ) {
			$panel.toggleClass( 'vynk-cart-loading', state );
		}

		/* ---------------------------------------------------------------------
		   10. Focus trap (accesibilidad)
		   ------------------------------------------------------------------ */
		function trapFocus( e ) {
			if ( ! isOpen || e.key !== 'Tab' ) {
				return;
			}

			var focusables = $panel.find(
				'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'
			).filter( ':visible' ).toArray();

			if ( focusables.length === 0 ) {
				return;
			}

			var first = focusables[ 0 ];
			var last  = focusables[ focusables.length - 1 ];

			if ( e.shiftKey ) {
				if ( document.activeElement === first ) {
					e.preventDefault();
					last.focus();
				}
			} else {
				if ( document.activeElement === last ) {
					e.preventDefault();
					first.focus();
				}
			}
		}

		/* ---------------------------------------------------------------------
		   11. Helpers de escape
		   ------------------------------------------------------------------ */
		function escHtml( str ) {
			return $( '<div>' ).text( String( str ) ).html();
		}

		function escAttr( str ) {
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /"/g, '&quot;' )
				.replace( /'/g, '&#39;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' );
		}

		/* ---------------------------------------------------------------------
		   12. Binding de eventos
		   ------------------------------------------------------------------ */
		// Abrir panel
		$( document ).on( 'click', '.vynk-cart-trigger', function ( e ) {
			e.preventDefault();
			openAndRefresh();
		} );

		// Cerrar — botón X
		$( document ).on( 'click', '#vynk-cart-close', function () {
			closeCart();
		} );

		// Cerrar — overlay
		$( document ).on( 'click', '#vynk-cart-overlay', function () {
			closeCart();
		} );

		// Cerrar — Escape
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && isOpen ) {
				closeCart();
			}
			trapFocus( e );
		} );

		// Cantidad +
		$( document ).on( 'click', '.vynk-qty-btn.vynk-qty-plus', function () {
			var $item   = $( this ).closest( '.vynk-cart-item' );
			var key     = $item.data( 'key' );
			var current = parseInt( $item.find( '.vynk-qty-display' ).text(), 10 );
			updateQty( key, current + 1 );
		} );

		// Cantidad -
		$( document ).on( 'click', '.vynk-qty-btn.vynk-qty-minus', function () {
			var $item   = $( this ).closest( '.vynk-cart-item' );
			var key     = $item.data( 'key' );
			var current = parseInt( $item.find( '.vynk-qty-display' ).text(), 10 );
			updateQty( key, current - 1 );
		} );

		// Eliminar item
		$( document ).on( 'click', '.vynk-cart-item-remove', function () {
			var key = $( this ).closest( '.vynk-cart-item' ).data( 'key' );
			removeItem( key );
		} );

		// WooCommerce: producto agregado (clásico + Blocks)
		$( document.body ).on( 'added_to_cart wc-blocks_added_to_cart', function () {
			if ( autoOpen ) {
				openAndRefresh();
			} else {
				fetchCart();
			}
		} );

		// WooCommerce: fragmentos refrescados
		$( document.body ).on( 'wc_fragments_refreshed wc_fragments_loaded', function () {
			if ( isOpen ) {
				fetchCart();
			}
		} );

		/* ---------------------------------------------------------------------
		   13. Cargar badge inicial en segundo plano
		   ------------------------------------------------------------------ */
		fetchCart();

	} ); // fin $(function)

} )( jQuery );
