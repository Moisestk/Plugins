<?php
/**
 * My Account — Edit account form
 *
 * Reemplaza woocommerce/myaccount/form-edit-account.php
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_edit_account_form' );
?>

<div class="mc-section-header">
    <h1 class="mc-section-title"><?php esc_html_e( 'Editar perfil', 'mi-cuenta-ui' ); ?></h1>
    <p class="mc-section-subtitle"><?php esc_html_e( 'Actualiza tu información personal y contraseña.', 'mi-cuenta-ui' ); ?></p>
</div>

<form class="woocommerce-EditAccountForm edit-account" action="" method="post"
      <?php do_action( 'woocommerce_edit_account_form_tag' ); ?>>

    <?php do_action( 'woocommerce_edit_account_form_start' ); ?>

    <!-- Datos personales -->
    <div class="mc-card">
        <div class="mc-card__header">
            <h3 class="mc-card__title"><?php esc_html_e( 'Datos personales', 'mi-cuenta-ui' ); ?></h3>
        </div>

        <div class="mc-form-grid">

            <div class="mc-field">
                <label for="account_first_name" class="mc-label">
                    <?php esc_html_e( 'Nombre', 'mi-cuenta-ui' ); ?> <abbr class="required" title="required">*</abbr>
                </label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text"
                       name="account_first_name" id="account_first_name" autocomplete="given-name"
                       value="<?php echo esc_attr( $user->first_name ); ?>" />
            </div>

            <div class="mc-field">
                <label for="account_last_name" class="mc-label">
                    <?php esc_html_e( 'Apellido', 'mi-cuenta-ui' ); ?> <abbr class="required" title="required">*</abbr>
                </label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text"
                       name="account_last_name" id="account_last_name" autocomplete="family-name"
                       value="<?php echo esc_attr( $user->last_name ); ?>" />
            </div>

            <div class="mc-field mc-field--full">
                <label for="account_display_name" class="mc-label">
                    <?php esc_html_e( 'Nombre visible', 'mi-cuenta-ui' ); ?> <abbr class="required" title="required">*</abbr>
                </label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text"
                       name="account_display_name" id="account_display_name"
                       value="<?php echo esc_attr( $user->display_name ); ?>" />
                <p style="font-size:12px;color:#6b7280;margin:6px 0 0">
                    <?php esc_html_e( 'Así aparecerás en la tienda.', 'mi-cuenta-ui' ); ?>
                </p>
            </div>

            <div class="mc-field mc-field--full">
                <label for="account_email" class="mc-label">
                    <?php esc_html_e( 'Correo electrónico', 'mi-cuenta-ui' ); ?> <abbr class="required" title="required">*</abbr>
                </label>
                <input type="email" class="woocommerce-Input woocommerce-Input--email input-text"
                       name="account_email" id="account_email" autocomplete="email"
                       value="<?php echo esc_attr( $user->user_email ); ?>" />
            </div>

        </div>
    </div><!-- /.mc-card -->

    <!-- Cambio de contraseña -->
    <div class="mc-card">
        <div class="mc-card__header">
            <h3 class="mc-card__title"><?php esc_html_e( 'Cambiar contraseña', 'mi-cuenta-ui' ); ?></h3>
        </div>
        <p style="font-size:13px;color:#6b7280;margin:0 0 20px">
            <?php esc_html_e( 'Deja los campos en blanco si no deseas cambiar tu contraseña.', 'mi-cuenta-ui' ); ?>
        </p>

        <div class="mc-form-grid">

            <div class="mc-field mc-field--full">
                <label for="password_current" class="mc-label"><?php esc_html_e( 'Contraseña actual', 'mi-cuenta-ui' ); ?></label>
                <div class="mc-password-wrapper">
                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text mc-pwd-field"
                           name="password_current" id="password_current" autocomplete="current-password" />
                    <button type="button" class="mc-password-toggle" aria-label="<?php esc_attr_e( 'Mostrar/ocultar contraseña', 'mi-cuenta-ui' ); ?>">
                        <svg class="mc-eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mc-field">
                <label for="password_1" class="mc-label"><?php esc_html_e( 'Nueva contraseña', 'mi-cuenta-ui' ); ?></label>
                <div class="mc-password-wrapper">
                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text mc-pwd-field"
                           name="password_1" id="password_1" autocomplete="new-password" />
                    <button type="button" class="mc-password-toggle" aria-label="<?php esc_attr_e( 'Mostrar/ocultar contraseña', 'mi-cuenta-ui' ); ?>">
                        <svg class="mc-eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mc-field">
                <label for="password_2" class="mc-label"><?php esc_html_e( 'Confirmar contraseña', 'mi-cuenta-ui' ); ?></label>
                <div class="mc-password-wrapper">
                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text mc-pwd-field"
                           name="password_2" id="password_2" autocomplete="new-password" />
                    <button type="button" class="mc-password-toggle" aria-label="<?php esc_attr_e( 'Mostrar/ocultar contraseña', 'mi-cuenta-ui' ); ?>">
                        <svg class="mc-eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div><!-- /.mc-card -->

    <?php do_action( 'woocommerce_edit_account_form' ); ?>

    <div style="display:flex;gap:10px;align-items:center">
        <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit mc-btn mc-btn--primary"
                name="save_account_details" value="<?php esc_attr_e( 'Guardar cambios', 'mi-cuenta-ui' ); ?>">
            <?php esc_html_e( 'Guardar cambios', 'mi-cuenta-ui' ); ?>
        </button>
        <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
        <input type="hidden" name="action" value="save_account_details" />
    </div>

    <?php do_action( 'woocommerce_edit_account_form_end' ); ?>

</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
