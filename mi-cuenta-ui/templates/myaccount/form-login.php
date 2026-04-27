<?php
/**
 * My Account — Login / Register form (guest view)
 *
 * Reemplaza woocommerce/myaccount/form-login.php
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );
?>

<div class="mc-auth-wrapper">

    <!-- ── Login ───────────────────────────────────────────── -->
    <div class="mc-auth-card">
        <h2><?php esc_html_e( 'Iniciar sesión', 'mi-cuenta-ui' ); ?></h2>

        <form class="woocommerce-form woocommerce-form-login login" method="post">

            <?php do_action( 'woocommerce_login_form_start' ); ?>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="username">
                    <?php esc_html_e( 'Usuario o correo electrónico', 'mi-cuenta-ui' ); ?> <abbr class="required" title="required">*</abbr>
                </label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text"
                       name="username" id="username" autocomplete="username"
                       value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
            </p>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="password">
                    <?php esc_html_e( 'Contraseña', 'mi-cuenta-ui' ); ?> <abbr class="required" title="required">*</abbr>
                </label>
                <input class="woocommerce-Input woocommerce-Input--password input-text"
                       type="password" name="password" id="password" autocomplete="current-password" />
            </p>

            <?php do_action( 'woocommerce_login_form' ); ?>

            <p class="form-row" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                    <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme"
                           type="checkbox" id="rememberme" value="forever" />
                    <span><?php esc_html_e( 'Recordarme', 'mi-cuenta-ui' ); ?></span>
                </label>
            </p>

            <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

            <button type="submit" class="woocommerce-button button woocommerce-form-login__submit"
                    name="login" value="<?php esc_attr_e( 'Ingresar', 'mi-cuenta-ui' ); ?>">
                <?php esc_html_e( 'Ingresar', 'mi-cuenta-ui' ); ?>
            </button>

            <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="mc-lost-password">
                <?php esc_html_e( '¿Olvidaste tu contraseña?', 'mi-cuenta-ui' ); ?>
            </a>

            <?php do_action( 'woocommerce_login_form_end' ); ?>

        </form>
    </div><!-- /.mc-auth-card -->

    <!-- ── Registro ───────────────────────────────────────── -->
    <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
    <div class="mc-auth-card">
        <h2><?php esc_html_e( 'Crear una cuenta', 'mi-cuenta-ui' ); ?></h2>

        <form method="post" class="woocommerce-form woocommerce-form-register register">

            <?php do_action( 'woocommerce_register_form_start' ); ?>

            <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_username">
                    <?php esc_html_e( 'Usuario', 'mi-cuenta-ui' ); ?> <abbr class="required" title="required">*</abbr>
                </label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text"
                       name="username" id="reg_username" autocomplete="username"
                       value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
            </p>
            <?php endif; ?>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_email">
                    <?php esc_html_e( 'Correo electrónico', 'mi-cuenta-ui' ); ?> <abbr class="required" title="required">*</abbr>
                </label>
                <input type="email" class="woocommerce-Input woocommerce-Input--text input-text"
                       name="email" id="reg_email" autocomplete="email"
                       value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" />
            </p>

            <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_password">
                    <?php esc_html_e( 'Contraseña', 'mi-cuenta-ui' ); ?> <abbr class="required" title="required">*</abbr>
                </label>
                <input type="password" class="woocommerce-Input woocommerce-Input--password input-text"
                       name="password" id="reg_password" autocomplete="new-password" />
            </p>
            <?php else : ?>
                <p style="font-size:13px;color:#6b7280;margin-bottom:16px">
                    <?php esc_html_e( 'Se enviará una contraseña automática a tu correo.', 'mi-cuenta-ui' ); ?>
                </p>
            <?php endif; ?>

            <?php do_action( 'woocommerce_register_form' ); ?>

            <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>

            <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit"
                    name="register" value="<?php esc_attr_e( 'Registrarme', 'mi-cuenta-ui' ); ?>">
                <?php esc_html_e( 'Registrarme', 'mi-cuenta-ui' ); ?>
            </button>

            <?php do_action( 'woocommerce_register_form_end' ); ?>

        </form>
    </div><!-- /.mc-auth-card -->
    <?php endif; ?>

</div><!-- /.mc-auth-wrapper -->

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
