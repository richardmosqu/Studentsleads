<?php
/**
 * University Leads — Ajustes (logo, textos del formulario, correos y paquetes).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Valores por defecto de todos los ajustes.
 */
function ul_default_settings() {
	return array(
		'logo_id'         => 0,
		'form_title'      => __( '¿Listo para estudiar en el extranjero?', 'university-leads' ),
		'form_subtitle'   => __( 'Cuéntanos sobre ti y te recomendaremos el paquete ideal para tu futuro.', 'university-leads' ),
		'button_text'     => __( 'Descubrir mi paquete', 'university-leads' ),
		'success_message' => __( '¡Gracias! Hemos recibido tu solicitud. Nuestro equipo te contactará muy pronto.', 'university-leads' ),
		'accent_color'    => '#2563eb',
		'notify_email'    => '',
		'student_subject' => __( '¡Recibimos tu solicitud, {nombre}!', 'university-leads' ),
		'student_body'    => __( "Hola {nombre},\n\nGracias por tu interés en estudiar en el extranjero con TheUForYou / Campus Life.\n\nSegún tus respuestas, el programa que mejor encaja contigo es: {paquete}.\n\nUn asesor te contactará muy pronto con todos los detalles.\n\n— Equipo TheUForYou · campuslifepa.com", 'university-leads' ),
		'packages'        => array(),
		'portal_password_hash' => '',
	);
}

/**
 * Ajustes guardados combinados con los valores por defecto.
 */
function ul_get_settings() {
	$saved = get_option( 'ul_settings', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, ul_default_settings() );
}

function ul_register_settings() {
	register_setting(
		'ul_settings_group',
		'ul_settings',
		array( 'sanitize_callback' => 'ul_sanitize_settings' )
	);
}
add_action( 'admin_init', 'ul_register_settings' );

/**
 * Sanitiza todos los campos de ajustes antes de guardarlos.
 */
function ul_sanitize_settings( $input ) {
	$defaults = ul_default_settings();
	$clean    = array();

	$input = is_array( $input ) ? $input : array();

	$clean['logo_id']         = isset( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0;
	$clean['form_title']      = isset( $input['form_title'] ) ? sanitize_text_field( $input['form_title'] ) : $defaults['form_title'];
	$clean['form_subtitle']   = isset( $input['form_subtitle'] ) ? sanitize_text_field( $input['form_subtitle'] ) : $defaults['form_subtitle'];
	$clean['button_text']     = isset( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : $defaults['button_text'];
	$clean['success_message'] = isset( $input['success_message'] ) ? sanitize_textarea_field( $input['success_message'] ) : $defaults['success_message'];

	$color                 = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : '';
	$clean['accent_color'] = $color ? $color : $defaults['accent_color'];

	$clean['notify_email']    = isset( $input['notify_email'] ) ? sanitize_email( $input['notify_email'] ) : '';
	$clean['student_subject'] = isset( $input['student_subject'] ) ? sanitize_text_field( $input['student_subject'] ) : $defaults['student_subject'];
	$clean['student_body']    = isset( $input['student_body'] ) ? sanitize_textarea_field( $input['student_body'] ) : $defaults['student_body'];

	// La contraseña del portal se guarda como hash; el campo vacío no la cambia.
	$prev                          = ul_get_settings();
	$clean['portal_password_hash'] = $prev['portal_password_hash'];
	if ( ! empty( $input['portal_password_new'] ) ) {
		$clean['portal_password_hash'] = wp_hash_password( (string) $input['portal_password_new'] );
	}
	if ( ! empty( $input['portal_password_clear'] ) ) {
		$clean['portal_password_hash'] = '';
	}

	$clean['packages'] = array();
	if ( isset( $input['packages'] ) && is_array( $input['packages'] ) ) {
		foreach ( ul_default_packages() as $slug => $pkg ) {
			if ( isset( $input['packages'][ $slug ] ) && is_array( $input['packages'][ $slug ] ) ) {
				$clean['packages'][ $slug ] = array(
					'name' => sanitize_text_field( $input['packages'][ $slug ]['name'] ?? '' ),
					'desc' => sanitize_textarea_field( $input['packages'][ $slug ]['desc'] ?? '' ),
				);
			}
		}
	}

	return $clean;
}

/**
 * Página de ajustes (submenú del dashboard).
 */
function ul_settings_menu() {
	add_submenu_page(
		'university-leads',
		__( 'Ajustes — University Leads', 'university-leads' ),
		__( 'Ajustes', 'university-leads' ),
		ul_capability(),
		'university-leads-settings',
		'ul_render_settings_page'
	);
}
add_action( 'admin_menu', 'ul_settings_menu', 20 );

function ul_settings_assets( $hook ) {
	if ( false === strpos( $hook, 'university-leads-settings' ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'ul-settings', UL_URL . 'assets/settings.js', array( 'jquery' ), UL_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'ul_settings_assets' );

function ul_render_settings_page() {
	if ( ! current_user_can( ul_capability() ) ) {
		wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'university-leads' ) );
	}

	$s        = ul_get_settings();
	$logo_url = $s['logo_id'] ? wp_get_attachment_image_url( $s['logo_id'], 'medium' ) : '';
	$packages = ul_get_packages();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'University Leads — Ajustes', 'university-leads' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'ul_settings_group' ); ?>

			<h2><?php esc_html_e( 'Formulario', 'university-leads' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Logo', 'university-leads' ); ?></th>
					<td>
						<input type="hidden" id="ul-logo-id" name="ul_settings[logo_id]" value="<?php echo esc_attr( $s['logo_id'] ); ?>" />
						<img id="ul-logo-preview" src="<?php echo esc_url( $logo_url ); ?>" style="max-height:80px;max-width:240px;display:<?php echo $logo_url ? 'block' : 'none'; ?>;margin-bottom:8px;" alt="" />
						<button type="button" class="button ul-media-pick"><?php esc_html_e( 'Elegir logo', 'university-leads' ); ?></button>
						<button type="button" class="button ul-media-remove" style="display:<?php echo $logo_url ? 'inline-block' : 'none'; ?>;"><?php esc_html_e( 'Quitar', 'university-leads' ); ?></button>
						<p class="description"><?php esc_html_e( 'Se muestra en la parte superior del formulario.', 'university-leads' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ul-form-title"><?php esc_html_e( 'Título', 'university-leads' ); ?></label></th>
					<td><input type="text" id="ul-form-title" class="regular-text" name="ul_settings[form_title]" value="<?php echo esc_attr( $s['form_title'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ul-form-subtitle"><?php esc_html_e( 'Subtítulo', 'university-leads' ); ?></label></th>
					<td><input type="text" id="ul-form-subtitle" class="large-text" name="ul_settings[form_subtitle]" value="<?php echo esc_attr( $s['form_subtitle'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ul-button-text"><?php esc_html_e( 'Texto del botón', 'university-leads' ); ?></label></th>
					<td><input type="text" id="ul-button-text" class="regular-text" name="ul_settings[button_text]" value="<?php echo esc_attr( $s['button_text'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ul-success-message"><?php esc_html_e( 'Mensaje de éxito', 'university-leads' ); ?></label></th>
					<td><textarea id="ul-success-message" class="large-text" rows="2" name="ul_settings[success_message]"><?php echo esc_textarea( $s['success_message'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="ul-accent-color"><?php esc_html_e( 'Color de acento', 'university-leads' ); ?></label></th>
					<td>
						<input type="color" id="ul-accent-color" name="ul_settings[accent_color]" value="<?php echo esc_attr( $s['accent_color'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Color del botón y detalles del formulario.', 'university-leads' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Notificaciones', 'university-leads' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ul-notify-email"><?php esc_html_e( 'Correo del equipo', 'university-leads' ); ?></label></th>
					<td>
						<input type="email" id="ul-notify-email" class="regular-text" name="ul_settings[notify_email]" value="<?php echo esc_attr( $s['notify_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'A dónde llega el aviso de cada nueva solicitud. Vacío = correo del administrador del sitio.', 'university-leads' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ul-student-subject"><?php esc_html_e( 'Asunto para el estudiante', 'university-leads' ); ?></label></th>
					<td><input type="text" id="ul-student-subject" class="large-text" name="ul_settings[student_subject]" value="<?php echo esc_attr( $s['student_subject'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ul-student-body"><?php esc_html_e( 'Correo para el estudiante', 'university-leads' ); ?></label></th>
					<td>
						<textarea id="ul-student-body" class="large-text" rows="8" name="ul_settings[student_body]"><?php echo esc_textarea( $s['student_body'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Comodines disponibles: {nombre} y {paquete}.', 'university-leads' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Portal de leads (equipo Campus)', 'university-leads' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Página del sitio protegida con contraseña donde el equipo ve los leads, los contacta y exporta a Excel. Crea una página con el shortcode [university_leads_dashboard] y define aquí la contraseña.', 'university-leads' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ul-portal-pw"><?php esc_html_e( 'Contraseña del portal', 'university-leads' ); ?></label></th>
					<td>
						<input type="password" id="ul-portal-pw" class="regular-text" name="ul_settings[portal_password_new]" value="" autocomplete="new-password" placeholder="<?php echo $s['portal_password_hash'] ? esc_attr__( '•••••••• (configurada — escribe para cambiarla)', 'university-leads' ) : esc_attr__( 'Define una contraseña', 'university-leads' ); ?>" />
						<p class="description">
							<?php
							if ( $s['portal_password_hash'] ) {
								esc_html_e( 'Hay una contraseña configurada. Deja el campo vacío para mantenerla, o escribe una nueva (esto cierra las sesiones abiertas).', 'university-leads' );
							} else {
								esc_html_e( 'El portal está inactivo hasta que definas una contraseña.', 'university-leads' );
							}
							?>
						</p>
						<?php if ( $s['portal_password_hash'] ) : ?>
							<label><input type="checkbox" name="ul_settings[portal_password_clear]" value="1" /> <?php esc_html_e( 'Desactivar el portal (borrar la contraseña)', 'university-leads' ); ?></label>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Paquetes', 'university-leads' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Edita el nombre y la descripción de cada paquete. El algoritmo asigna el paquete según destino, nivel de inglés y fechas.', 'university-leads' ); ?></p>
			<table class="form-table" role="presentation">
				<?php foreach ( $packages as $slug => $pkg ) : ?>
					<tr>
						<th scope="row"><code><?php echo esc_html( $slug ); ?></code></th>
						<td>
							<input type="text" class="regular-text" name="ul_settings[packages][<?php echo esc_attr( $slug ); ?>][name]" value="<?php echo esc_attr( $pkg['name'] ); ?>" />
							<br />
							<textarea class="large-text" rows="2" name="ul_settings[packages][<?php echo esc_attr( $slug ); ?>][desc]"><?php echo esc_textarea( $pkg['desc'] ); ?></textarea>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<?php submit_button( __( 'Guardar ajustes', 'university-leads' ) ); ?>
		</form>
	</div>
	<?php
}
