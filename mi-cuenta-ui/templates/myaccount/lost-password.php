<?php
/**
 * Lost password page — Vynk style
 *
 * Reemplaza woocommerce/myaccount/lost-password.php
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_form' );
?>

<div class="mc-auth-wrapper">
    <div class="mc-auth-card">

        <h2><?php esc_html_e( '¿Olvidaste tu contraseña?', 'mi-cuenta-ui' ); ?></h2>

        <p style="font-size:14px;color:#6b7280;margin:0 0 24px;line-height:1.6">
            <?php esc_html_e( 'Ingresa tu nombre de usuario o correo electrónico y te enviaremos un enlace para restablecer tu contraseña.', 'mi-cuenta-ui' ); ?>
        </p>

        <form method="post" class="woocommerce-ResetPassword lost_reset_password">

            <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
                <label for="user_login">
                    <?php esc_html_e( 'Usuario o correo electrónico', 'mi-cuenta-ui' ); ?>
                    <abbr class="required" title="required" style="color:#bf1f1f">*</abbr>
                </label>
                <input class="woocommerce-Input woocommerce-Input--text input-text"
                       type="text" name="user_login" id="user_login"
                       autocomplete="username email" />
            </p>

            <div style="margin-top:8px">
                <?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>
                <button type="submit"
                        class="woocommerce-Button button"
                        name="wc_reset_password"
                        value="true">
                    <?php esc_html_e( 'Restablecer contraseña', 'mi-cuenta-ui' ); ?>
                </button>
            </div>

        </form>

        <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"
           class="mc-lost-password" style="margin-top:16px">
            &larr; <?php esc_html_e( 'Volver al inicio de sesión', 'mi-cuenta-ui' ); ?>
        </a>

    </div>
</div>

<?php do_action( 'woocommerce_after_lost_password_form' ); ?>
