/**
 * Mi Cuenta UI — Frontend JS
 * Sidebar toggle, WC notices, nav active state, password toggle.
 */
( function () {
    'use strict';

    document.addEventListener( 'DOMContentLoaded', function () {

        /* ── 1. Mobile sidebar toggle ──────────────────────────── */
        var sidebar  = document.getElementById( 'mc-sidebar' );
        var overlay  = document.getElementById( 'mc-overlay' );
        var toggle   = document.getElementById( 'mc-toggle' );

        function openSidebar() {
            if ( ! sidebar ) return;
            sidebar.classList.add( 'mc-sidebar--open' );
            if ( overlay ) overlay.classList.add( 'is-visible' );
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            if ( ! sidebar ) return;
            sidebar.classList.remove( 'mc-sidebar--open' );
            if ( overlay ) overlay.classList.remove( 'is-visible' );
            document.body.style.overflow = '';
        }

        if ( toggle ) {
            toggle.addEventListener( 'click', function () {
                if ( sidebar && sidebar.classList.contains( 'mc-sidebar--open' ) ) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } );
        }

        if ( overlay ) {
            overlay.addEventListener( 'click', closeSidebar );
        }

        // Cerrar con Escape
        document.addEventListener( 'keydown', function ( e ) {
            if ( 'Escape' === e.key ) closeSidebar();
        } );

        // Cerrar sidebar al hacer click en un nav link (mobile)
        document.querySelectorAll( '.mc-nav__link' ).forEach( function ( link ) {
            link.addEventListener( 'click', function () {
                if ( window.innerWidth <= 768 ) closeSidebar();
            } );
        } );

        /* ── 2. Nav active state ───────────────────────────────── */
        var currentPath = window.location.pathname.replace( /\/$/, '' );
        var navLinks    = document.querySelectorAll( '.mc-nav__link' );

        navLinks.forEach( function ( link ) {
            var linkPath = ( link.getAttribute( 'href' ) || '' ).replace( /\/$/, '' );
            var item     = link.closest( '.mc-nav__item' );
            if ( ! item ) return;

            // Marcar activo si el path actual termina con la ruta del link
            if ( linkPath && currentPath.indexOf( linkPath ) !== -1 && linkPath.length > 1 ) {
                item.classList.add( 'is-active' );
            }
        } );

        // Si ninguno activo y estamos en dashboard, activar el primer item
        var hasActive = document.querySelector( '.mc-nav__item.is-active' );
        if ( ! hasActive ) {
            var dashLink = document.querySelector( '.mc-nav__link[data-endpoint="dashboard"]' );
            if ( dashLink ) {
                var dashItem = dashLink.closest( '.mc-nav__item' );
                if ( dashItem ) dashItem.classList.add( 'is-active' );
            }
        }

        /* ── 3. Auto-dismiss WooCommerce notices ───────────────── */
        var notices = document.querySelectorAll(
            '.mc-main .woocommerce-message, .mc-main .woocommerce-error, .mc-main .woocommerce-info'
        );

        notices.forEach( function ( notice ) {
            // Botón de cerrar
            var closeBtn       = document.createElement( 'button' );
            closeBtn.className = 'mc-notice-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.setAttribute( 'type', 'button' );
            closeBtn.addEventListener( 'click', function () {
                fadeOut( notice );
            } );
            notice.appendChild( closeBtn );

            // Auto-dismiss después de 6 segundos
            setTimeout( function () {
                fadeOut( notice );
            }, 6000 );
        } );

        function fadeOut( el ) {
            if ( ! el || ! el.parentNode ) return;
            el.style.transition = 'opacity 0.3s ease';
            el.style.opacity    = '0';
            setTimeout( function () {
                if ( el.parentNode ) el.parentNode.removeChild( el );
            }, 300 );
        }

        /* ── 4. Password toggle (show/hide) ────────────────────── */
        var pwdToggles = document.querySelectorAll( '.mc-password-toggle' );

        pwdToggles.forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                var wrapper = btn.closest( '.mc-password-wrapper' );
                if ( ! wrapper ) return;
                var input = wrapper.querySelector( 'input' );
                if ( ! input ) return;

                if ( 'password' === input.getAttribute( 'type' ) ) {
                    input.setAttribute( 'type', 'text' );
                    btn.setAttribute( 'aria-label', 'Ocultar contraseña' );
                    btn.style.color = 'var(--vynk-accent)';
                } else {
                    input.setAttribute( 'type', 'password' );
                    btn.setAttribute( 'aria-label', 'Mostrar contraseña' );
                    btn.style.color = '';
                }
            } );
        } );

    } ); // DOMContentLoaded

} )();
