<?php
/**
 * My Account — Edit address form
 *
 * Reemplaza woocommerce/myaccount/form-edit-address.php
 *
 * @var string $load_address  billing|shipping
 * @var array  $address       Address fields
 * @var array  $formatted_address
 */
defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address )
    ? esc_html__( 'Dirección de facturación', 'mi-cuenta-ui' )
    : esc_html__( 'Dirección de envío', 'mi-cuenta-ui' );
?>

<!-- Breadcrumb -->
<nav class="mc-breadcrumb">
    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>">
        <?php esc_html_e( 'Mis direcciones', 'mi-cuenta-ui' ); ?>
    </a>
    <span class="mc-breadcrumb__sep">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
    </span>
    <span class="mc-breadcrumb__current"><?php echo esc_html( $page_title ); ?></span>
</nav>

<div class="mc-section-header">
    <h1 class="mc-section-title"><?php echo esc_html( $page_title ); ?></h1>
</div>

<?php if ( ! $load_address ) : ?>
    <!-- Vista de las dos direcciones (billing + shipping) -->

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <?php foreach ( [ 'billing', 'shipping' ] as $adr_type ) :
            $adr_title   = 'billing' === $adr_type ? __( 'Facturación', 'mi-cuenta-ui' ) : __( 'Envío', 'mi-cuenta-ui' );
            $adr_address = wc_get_account_formatted_address( $adr_type );
            $edit_url    = wc_get_account_endpoint_url( 'edit-address' ) . $adr_type . '/';
        ?>
            <div class="mc-card">
                <div class="mc-card__header">
                    <h3 class="mc-card__title"><?php echo esc_html( $adr_title ); ?></h3>
                    <a href="<?php echo esc_url( $edit_url ); ?>" class="mc-card__action">
                        <?php esc_html_e( 'Editar', 'mi-cuenta-ui' ); ?>
                    </a>
                </div>
                <?php if ( $adr_address ) : ?>
                    <address style="font-style:normal;font-size:14px;line-height:1.7;color:#374151">
                        <?php echo wp_kses_post( $adr_address ); ?>
                    </address>
                <?php else : ?>
                    <p style="font-size:14px;color:#6b7280;margin:0">
                        <?php esc_html_e( 'No tienes una dirección guardada aún.', 'mi-cuenta-ui' ); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    </div>

<?php else : ?>
    <!-- Formulario de edición -->

    <form method="post" class="woocommerce-address-fields">
        <?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

        <div class="mc-card">
            <div class="mc-form-grid">
                <?php foreach ( $address as $key => $field ) :
                    if ( isset( $field['country_field'], $address[ $field['country_field'] ] ) ) {
                        $field['country'] = wc_get_post_data_by_key( $field['country_field'], $address[ $field['country_field'] ]['value'] );
                    }
                    // Marcar campo completo si es textarea o country/state select
                    $full = in_array( $field['type'] ?? 'text', [ 'textarea', 'country', 'state' ], true );
                ?>
                    <div class="mc-field <?php echo $full ? 'mc-field--full' : ''; ?>">
                        <?php woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) ); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

        <div style="display:flex;gap:10px;align-items:center;margin-top:4px">
            <button type="submit" class="mc-btn mc-btn--primary"
                    name="save_address" value="<?php esc_attr_e( 'Guardar dirección', 'mi-cuenta-ui' ); ?>">
                <?php esc_html_e( 'Guardar dirección', 'mi-cuenta-ui' ); ?>
            </button>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>" class="mc-btn mc-btn--outline">
                <?php esc_html_e( 'Cancelar', 'mi-cuenta-ui' ); ?>
            </a>

            <?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
            <input type="hidden" name="action" value="edit_address" />
        </div>

    </form>

<?php endif; ?>
