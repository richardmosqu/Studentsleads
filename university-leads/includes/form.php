<?php
/**
 * University Leads — Formulario público [university_leads_form].
 *
 * Wizard conversacional: una pregunta por pantalla, barra de progreso,
 * opciones tipo tarjeta con auto-avance y textos personalizados con el
 * nombre del estudiante. Sin JavaScript, el formulario degrada a un
 * formulario clásico de una sola página y sigue funcionando.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emoji decorativo por pregunta del cuestionario.
 */
function ul_field_emoji( $key ) {
	$emojis = array(
		'nivel'       => '🎓',
		'destino'     => '🌍',
		'presupuesto' => '💰',
		'ingles'      => '🗣️',
		'inicio'      => '📅',
		'beca'        => '🏅',
	);
	return isset( $emojis[ $key ] ) ? $emojis[ $key ] : '✨';
}

/**
 * Shortcode [university_leads_form].
 */
function ul_form_shortcode() {
	$s        = ul_get_settings();
	$fields   = ul_get_fields();
	$packages = ul_get_packages();
	$logo_url = $s['logo_id'] ? wp_get_attachment_image_url( $s['logo_id'], 'medium' ) : '';

	wp_enqueue_style( 'university-leads', UL_URL . 'assets/form.css', array(), UL_VERSION );
	wp_enqueue_script( 'university-leads', UL_URL . 'assets/form.js', array(), UL_VERSION, true );
	wp_add_inline_style(
		'university-leads',
		'.ul-form-wrap{--ul-accent:' . esc_attr( $s['accent_color'] ) . ';}'
	);

	$status  = isset( $_GET['ul_status'] ) ? sanitize_key( wp_unslash( $_GET['ul_status'] ) ) : '';
	$pkg     = isset( $_GET['ul_pkg'] ) ? sanitize_key( wp_unslash( $_GET['ul_pkg'] ) ) : '';
	$rec_pkg = isset( $packages[ $pkg ] ) ? $packages[ $pkg ] : null;

	ob_start();
	?>
	<div class="ul-form-wrap" id="ul-form">
		<?php if ( $logo_url ) : ?>
			<img class="ul-form-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="" />
		<?php endif; ?>

		<?php if ( '' !== $s['form_title'] ) : ?>
			<h3 class="ul-form-title"><?php echo esc_html( $s['form_title'] ); ?></h3>
		<?php endif; ?>
		<?php if ( '' !== $s['form_subtitle'] && 'ok' !== $status ) : ?>
			<p class="ul-form-subtitle"><?php echo esc_html( $s['form_subtitle'] ); ?></p>
		<?php endif; ?>

		<?php if ( 'ok' === $status ) : ?>
			<div class="ul-notice ul-notice--ok">
				<span class="ul-notice__emoji">🎉</span>
				<p class="ul-notice__msg"><?php echo esc_html( $s['success_message'] ); ?></p>
				<?php if ( $rec_pkg ) : ?>
					<div class="ul-notice__pkg">
						<span class="ul-notice__pkg-kicker"><?php esc_html_e( 'Tu programa ideal según tus respuestas', 'university-leads' ); ?></span>
						<strong class="ul-notice__pkg-name">🎓 <?php echo esc_html( $rec_pkg['name'] ); ?></strong>
						<span class="ul-notice__pkg-desc"><?php echo esc_html( $rec_pkg['desc'] ); ?></span>
					</div>
					<p class="ul-notice__next"><?php esc_html_e( 'Revisa tu correo: te enviamos la confirmación y muy pronto un asesor te escribirá. 📩', 'university-leads' ); ?></p>
				<?php endif; ?>
			</div>
		<?php elseif ( 'error' === $status ) : ?>
			<div class="ul-notice ul-notice--error">
				<p><?php esc_html_e( 'Algo salió mal: revisa que el nombre, el correo y todas las preguntas estén completos.', 'university-leads' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( 'ok' !== $status ) : ?>
			<form class="ul-form" data-ul-wizard method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ul_submit" />
				<input type="hidden" name="ul_redirect" value="<?php echo esc_url( ul_current_url() ); ?>" />
				<?php wp_nonce_field( 'ul_submit', 'ul_nonce' ); ?>

				<?php /* Honeypot: invisible para humanos; los bots lo rellenan. */ ?>
				<p class="ul-hp" aria-hidden="true">
					<label for="ul-website"><?php esc_html_e( 'No llenar este campo', 'university-leads' ); ?></label>
					<input type="text" id="ul-website" name="ul_website" tabindex="-1" autocomplete="off" />
				</p>

				<div class="ul-progress" aria-hidden="true">
					<div class="ul-progress__track"><div class="ul-progress__bar"></div></div>
					<span class="ul-progress__label"></span>
				</div>

				<section class="ul-step">
					<h4 class="ul-step__q">👋 <?php esc_html_e( '¡Hola! Empecemos por lo básico: ¿cómo te llamas?', 'university-leads' ); ?></h4>
					<input type="text" id="ul-nombre" name="ul_nombre" data-ul-nombre-input required maxlength="120" placeholder="<?php esc_attr_e( 'Escribe tu nombre completo…', 'university-leads' ); ?>" autocomplete="name" />
					<p class="ul-step__hint"><?php esc_html_e( 'Presiona Enter ↵ o el botón para continuar', 'university-leads' ); ?></p>
				</section>

				<section class="ul-step">
					<h4 class="ul-step__q">😊 <?php esc_html_e( '¡Un gusto', 'university-leads' ); ?><span data-ul-nombre></span>! <?php esc_html_e( '¿A qué correo te escribimos?', 'university-leads' ); ?></h4>
					<input type="email" id="ul-email" name="ul_email" required maxlength="200" placeholder="tucorreo@ejemplo.com" autocomplete="email" />
				</section>

				<section class="ul-step">
					<h4 class="ul-step__q">📱 <?php esc_html_e( '¿Dónde más podemos contactarte?', 'university-leads' ); ?> <span class="ul-step__optional"><?php esc_html_e( '(opcional)', 'university-leads' ); ?></span></h4>
					<label class="ul-step__sublabel" for="ul-telefono"><?php esc_html_e( 'Teléfono / WhatsApp', 'university-leads' ); ?></label>
					<input type="tel" id="ul-telefono" name="ul_telefono" maxlength="40" placeholder="+507 6000-0000" autocomplete="tel" />
					<label class="ul-step__sublabel" for="ul-institucion"><?php esc_html_e( 'Colegio o universidad actual', 'university-leads' ); ?></label>
					<input type="text" id="ul-institucion" name="ul_institucion" maxlength="160" placeholder="<?php esc_attr_e( 'Ej. Universidad de Panamá', 'university-leads' ); ?>" />
				</section>

				<?php foreach ( $fields as $key => $field ) : ?>
					<?php $multiple = ! empty( $field['multiple'] ); ?>
					<section class="ul-step" <?php echo $multiple ? 'data-min="1"' : 'data-auto'; ?>>
						<h4 class="ul-step__q"><?php echo esc_html( ul_field_emoji( $key ) ); ?> <?php echo esc_html( $field['label'] ); ?></h4>
						<?php if ( $multiple ) : ?>
							<p class="ul-step__hint ul-step__hint--top"><?php esc_html_e( 'Puedes elegir varias opciones. ✅', 'university-leads' ); ?></p>
						<?php endif; ?>
						<div class="ul-options" role="<?php echo $multiple ? 'group' : 'radiogroup'; ?>" aria-label="<?php echo esc_attr( $field['label'] ); ?>">
							<?php $first = true; ?>
							<?php foreach ( $field['choices'] as $value => $label ) : ?>
								<label class="ul-option">
									<?php if ( $multiple ) : ?>
										<input type="checkbox" name="ul_<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $value ); ?>" />
									<?php else : ?>
										<input type="radio" name="ul_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo $first ? 'required' : ''; ?> />
									<?php endif; ?>
									<span class="ul-option__text"><?php echo esc_html( $label ); ?></span>
									<span class="ul-option__check" aria-hidden="true">✓</span>
								</label>
								<?php $first = false; ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>

				<section class="ul-step">
					<h4 class="ul-step__q">✍️ <?php esc_html_e( 'Ya casi terminamos', 'university-leads' ); ?><span data-ul-nombre></span>. <?php esc_html_e( '¿Algo más que debamos saber?', 'university-leads' ); ?> <span class="ul-step__optional"><?php esc_html_e( '(opcional)', 'university-leads' ); ?></span></h4>
					<textarea id="ul-mensaje" name="ul_mensaje" rows="3" maxlength="2000" placeholder="<?php esc_attr_e( 'Cuéntanos sobre tus metas, carrera de interés, dudas…', 'university-leads' ); ?>"></textarea>
					<p class="ul-step__hint"><?php esc_html_e( 'Al enviar, verás al instante el paquete ideal para ti. 🎯', 'university-leads' ); ?></p>
				</section>

				<div class="ul-nav">
					<button type="button" class="ul-nav__prev" data-ul-prev>← <?php esc_html_e( 'Atrás', 'university-leads' ); ?></button>
					<button type="button" class="ul-nav__next" data-ul-next><?php esc_html_e( 'Continuar', 'university-leads' ); ?> →</button>
					<button type="submit" class="ul-submit"><?php echo esc_html( $s['button_text'] ); ?></button>
				</div>
			</form>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'university_leads_form', 'ul_form_shortcode' );

/**
 * URL de la página actual, para volver a ella tras el envío.
 */
function ul_current_url() {
	$permalink = get_permalink();
	if ( $permalink ) {
		return $permalink;
	}
	return home_url( add_query_arg( array(), isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' ) );
}

/**
 * Procesa el envío del formulario (visitantes y usuarios con sesión).
 */
function ul_handle_submit() {
	if ( ! isset( $_POST['ul_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ul_nonce'] ) ), 'ul_submit' ) ) {
		wp_die( esc_html__( 'La sesión del formulario expiró. Vuelve atrás e inténtalo de nuevo.', 'university-leads' ) );
	}

	$redirect = isset( $_POST['ul_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['ul_redirect'] ) ) : home_url( '/' );
	// Solo se permite redirigir dentro del propio sitio.
	$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );
	// Se limpian estados de envíos anteriores en la URL de retorno.
	$redirect = remove_query_arg( array( 'ul_status', 'ul_pkg' ), $redirect );

	// Honeypot con contenido => bot. Se simula éxito sin guardar nada.
	if ( ! empty( $_POST['ul_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'ul_status', 'ok', $redirect ) . '#ul-form' );
		exit;
	}

	$nombre      = isset( $_POST['ul_nombre'] ) ? sanitize_text_field( wp_unslash( $_POST['ul_nombre'] ) ) : '';
	$email       = isset( $_POST['ul_email'] ) ? sanitize_email( wp_unslash( $_POST['ul_email'] ) ) : '';
	$telefono    = isset( $_POST['ul_telefono'] ) ? sanitize_text_field( wp_unslash( $_POST['ul_telefono'] ) ) : '';
	$institucion = isset( $_POST['ul_institucion'] ) ? sanitize_text_field( wp_unslash( $_POST['ul_institucion'] ) ) : '';
	$mensaje     = isset( $_POST['ul_mensaje'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ul_mensaje'] ) ) : '';

	// Respuestas del cuestionario: solo se aceptan valores definidos en ul_get_fields().
	$answers = array();
	foreach ( ul_get_fields() as $key => $field ) {
		if ( ! empty( $field['multiple'] ) ) {
			$raw    = isset( $_POST[ 'ul_' . $key ] ) ? (array) wp_unslash( $_POST[ 'ul_' . $key ] ) : array();
			$values = array();
			foreach ( $raw as $v ) {
				$v = sanitize_key( $v );
				if ( isset( $field['choices'][ $v ] ) ) {
					$values[] = $v;
				}
			}
			$values = array_values( array_unique( $values ) );
			if ( empty( $values ) ) {
				wp_safe_redirect( add_query_arg( 'ul_status', 'error', $redirect ) . '#ul-form' );
				exit;
			}
			$answers[ $key ] = $values;
			continue;
		}

		$raw = isset( $_POST[ 'ul_' . $key ] ) ? sanitize_key( wp_unslash( $_POST[ 'ul_' . $key ] ) ) : '';
		if ( ! isset( $field['choices'][ $raw ] ) ) {
			wp_safe_redirect( add_query_arg( 'ul_status', 'error', $redirect ) . '#ul-form' );
			exit;
		}
		$answers[ $key ] = $raw;
	}

	if ( '' === $nombre || '' === $email ) {
		wp_safe_redirect( add_query_arg( 'ul_status', 'error', $redirect ) . '#ul-form' );
		exit;
	}

	$result = ul_score_lead( $answers );

	$lead_id = wp_insert_post(
		array(
			'post_type'   => UL_CPT,
			'post_status' => 'publish',
			'post_title'  => $nombre,
		),
		true
	);

	if ( is_wp_error( $lead_id ) ) {
		wp_safe_redirect( add_query_arg( 'ul_status', 'error', $redirect ) . '#ul-form' );
		exit;
	}

	update_post_meta( $lead_id, '_ul_email', $email );
	update_post_meta( $lead_id, '_ul_telefono', $telefono );
	update_post_meta( $lead_id, '_ul_institucion', $institucion );
	update_post_meta( $lead_id, '_ul_mensaje', $mensaje );
	update_post_meta( $lead_id, '_ul_answers', $answers );
	update_post_meta( $lead_id, '_ul_paquete', $result['paquete'] );
	update_post_meta( $lead_id, '_ul_scores', $result['scores'] );
	update_post_meta( $lead_id, '_ul_estado', 'nuevo' );

	ul_notify_team( $lead_id );
	ul_notify_student( $lead_id );

	wp_safe_redirect(
		add_query_arg(
			array(
				'ul_status' => 'ok',
				'ul_pkg'    => $result['paquete'],
			),
			$redirect
		) . '#ul-form'
	);
	exit;
}
add_action( 'admin_post_nopriv_ul_submit', 'ul_handle_submit' );
add_action( 'admin_post_ul_submit', 'ul_handle_submit' );
