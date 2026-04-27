/**
 * Vynk Checkout — Admin Settings JS
 * Handles: color pickers, logo upload/remove, radio card selection.
 */
jQuery( function ( $ ) {

    // ── Color Pickers ──────────────────────────────────────────────
    $( '.vynk-color-picker' ).wpColorPicker();

    // ── Logo Media Uploader ────────────────────────────────────────
    var mediaFrame;

    $( '#vynk-upload-logo' ).on( 'click', function ( e ) {
        e.preventDefault();

        if ( mediaFrame ) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media( {
            title: 'Select Checkout Logo',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' }
        } );

        mediaFrame.on( 'select', function () {
            var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
            $( '#vynk-logo-id' ).val( attachment.id );
            $( '#vynk-logo-preview' ).attr( 'src', attachment.url ).show();
            $( '#vynk-remove-logo' ).show();
        } );

        mediaFrame.open();
    } );

    $( '#vynk-remove-logo' ).on( 'click', function ( e ) {
        e.preventDefault();
        $( '#vynk-logo-id' ).val( '0' );
        $( '#vynk-logo-preview' ).attr( 'src', '' ).hide();
        $( this ).hide();
    } );

    // ── Radio Card Selection Highlight ────────────────────────────
    $( '.vynk-radio-card input[type="radio"]' ).on( 'change', function () {
        $( '.vynk-radio-card' ).removeClass( 'is-selected' );
        $( this ).closest( '.vynk-radio-card' ).addClass( 'is-selected' );
    } );

} );
