<?php
/**
 * University Leads — Portal frontal de leads [university_leads_dashboard].
 *
 * Página pública protegida por contraseña (definida en Ajustes) para que
 * el equipo de Campus vea los leads, los contacte por WhatsApp/correo,
 * actualice su estado y exporte todo a CSV. No requiere usuario de WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UL_PORTAL_COOKIE', 'ul_portal_token' );
define( 'UL_PORTAL_TTL', 12 * HOUR_IN_SECONDS );

/**
 * Clave del token de sesión. Incluye el hash de la contraseña:
 * al cambiar la contraseña se invalidan todas las sesiones abiertas.
 */
function ul_portal_key() {
	$s = ul_get_settings();
	return wp_salt( 'auth' ) . '|ul-portal|' . $s['portal_password_hash'];
}

/**
 * ¿Tiene el visitante una sesión válida del portal?
 */
function ul_portal_is_authed() {
	$s = ul_get_settings();
	if ( empty( $s['portal_password_hash'] ) || empty( $_COOKIE[ UL_PORTAL_COOKIE ] ) ) {
		return false;
	}
	$parts = explode( '|', sanitize_text_field( wp_unslash( $_COOKIE[ UL_PORTAL_COOKIE ] ) ) );
	if ( 2 !== count( $parts ) ) {
		return false;
	}
	list( $expiry, $sig ) = $parts;
	if ( ! ctype_digit( $expiry ) || (int) $expiry < time() ) {
		return false;
	}
	return hash_equals( hash_hmac( 'sha256', $expiry, ul_portal_key() ), $sig );
}

/**
 * Login: valida la contraseña y deja una cookie firmada de 12 horas.
 * Limita a 5 intentos fallidos por IP cada 10 minutos.
 */
function ul_portal_login() {
	if ( ! isset( $_POST['ul_portal_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ul_portal_nonce'] ) ), 'ul_portal_login' ) ) {
		wp_die( esc_html__( 'La sesión expiró. Vuelve atrás e inténtalo de nuevo.', 'university-leads' ) );
	}

	$redirect = isset( $_POST['ul_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['ul_redirect'] ) ) : home_url( '/' );
	$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );
	$redirect = remove_query_arg( 'ul_portal_error', $redirect );

	$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$throttle = 'ul_portal_fail_' . md5( $ip );
	$fails    = (int) get_transient( $throttle );

	if ( $fails >= 5 ) {
		wp_safe_redirect( add_query_arg( 'ul_portal_error', 'locked', $redirect ) );
		exit;
	}

	$s  = ul_get_settings();
	$pw = isset( $_POST['ul_portal_pw'] ) ? (string) wp_unslash( $_POST['ul_portal_pw'] ) : '';

	if ( empty( $s['portal_password_hash'] ) || ! wp_check_password( $pw, $s['portal_password_hash'] ) ) {
		set_transient( $throttle, $fails + 1, 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'ul_portal_error', 'bad', $redirect ) );
		exit;
	}

	delete_transient( $throttle );

	$expiry = time() + UL_PORTAL_TTL;
	$token  = $expiry . '|' . hash_hmac( 'sha256', (string) $expiry, ul_portal_key() );
	setcookie( UL_PORTAL_COOKIE, $token, $expiry, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_nopriv_ul_portal_login', 'ul_portal_login' );
add_action( 'admin_post_ul_portal_login', 'ul_portal_login' );

/**
 * Cierre de sesión del portal.
 */
function ul_portal_logout() {
	setcookie( UL_PORTAL_COOKIE, '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	$redirect = wp_validate_redirect( wp_get_referer(), home_url( '/' ) );
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_nopriv_ul_portal_logout', 'ul_portal_logout' );
add_action( 'admin_post_ul_portal_logout', 'ul_portal_logout' );

/**
 * Cambio de estado de un lead desde el portal.
 */
function ul_portal_update_status() {
	if ( ! ul_portal_is_authed() ) {
		wp_die( esc_html__( 'Tu sesión del portal expiró. Ingresa de nuevo.', 'university-leads' ) );
	}
	if ( ! isset( $_POST['ul_portal_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ul_portal_nonce'] ) ), 'ul_portal_status' ) ) {
		wp_die( esc_html__( 'La sesión expiró. Vuelve atrás e inténtalo de nuevo.', 'university-leads' ) );
	}

	$lead_id  = isset( $_POST['ul_lead'] ) ? absint( $_POST['ul_lead'] ) : 0;
	$estado   = isset( $_POST['ul_estado'] ) ? sanitize_key( wp_unslash( $_POST['ul_estado'] ) ) : '';
	$statuses = ul_get_statuses();

	if ( $lead_id && UL_CPT === get_post_type( $lead_id ) && isset( $statuses[ $estado ] ) ) {
		update_post_meta( $lead_id, '_ul_estado', $estado );
	}

	$redirect = isset( $_POST['ul_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['ul_redirect'] ) ) : home_url( '/' );
	wp_safe_redirect( wp_validate_redirect( $redirect, home_url( '/' ) ) );
	exit;
}
add_action( 'admin_post_nopriv_ul_portal_status', 'ul_portal_update_status' );
add_action( 'admin_post_ul_portal_status', 'ul_portal_update_status' );

/**
 * Exportación CSV de todos los leads (compatible con Excel).
 */
function ul_portal_export() {
	if ( ! ul_portal_is_authed() && ! current_user_can( ul_capability() ) ) {
		wp_die( esc_html__( 'Tu sesión del portal expiró. Ingresa de nuevo.', 'university-leads' ) );
	}
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'ul_portal_export' ) ) {
		wp_die( esc_html__( 'El enlace de exportación expiró. Recarga el portal e inténtalo de nuevo.', 'university-leads' ) );
	}

	$fields   = ul_get_fields();
	$packages = ul_get_packages();
	$statuses = ul_get_statuses();

	$leads = get_posts(
		array(
			'post_type'      => UL_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="leads-' . gmdate( 'Y-m-d' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' );
	// BOM UTF-8 para que Excel muestre bien los acentos.
	fwrite( $out, "\xEF\xBB\xBF" );

	$header = array(
		__( 'Fecha', 'university-leads' ),
		__( 'Nombre', 'university-leads' ),
		__( 'Correo', 'university-leads' ),
		__( 'Teléfono', 'university-leads' ),
		__( 'Institución', 'university-leads' ),
	);
	foreach ( $fields as $field ) {
		$header[] = $field['label'];
	}
	$header[] = __( 'Paquete recomendado', 'university-leads' );
	$header[] = __( 'Estado', 'university-leads' );
	$header[] = __( 'Mensaje', 'university-leads' );
	fputcsv( $out, $header );

	foreach ( $leads as $lead ) {
		$answers = get_post_meta( $lead->ID, '_ul_answers', true );
		$paquete = get_post_meta( $lead->ID, '_ul_paquete', true );
		$estado  = get_post_meta( $lead->ID, '_ul_estado', true );

		$row = array(
			get_the_date( 'Y-m-d H:i', $lead ),
			$lead->post_title,
			get_post_meta( $lead->ID, '_ul_email', true ),
			get_post_meta( $lead->ID, '_ul_telefono', true ),
			get_post_meta( $lead->ID, '_ul_institucion', true ),
		);
		foreach ( $fields as $key => $field ) {
			$row[] = is_array( $answers ) && isset( $answers[ $key ] ) ? ul_answer_text( $key, $answers[ $key ] ) : '';
		}
		$row[] = isset( $packages[ $paquete ] ) ? $packages[ $paquete ]['name'] : $paquete;
		$row[] = isset( $statuses[ $estado ] ) ? $statuses[ $estado ] : $statuses['nuevo'];
		$row[] = get_post_meta( $lead->ID, '_ul_mensaje', true );
		fputcsv( $out, $row );
	}

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_nopriv_ul_portal_export', 'ul_portal_export' );
add_action( 'admin_post_ul_portal_export', 'ul_portal_export' );

/**
 * Shortcode [university_leads_dashboard]: login o tablero según la sesión.
 */
function ul_portal_shortcode() {
	$s = ul_get_settings();

	wp_enqueue_style( 'ul-portal', UL_URL . 'assets/portal.css', array(), UL_VERSION );
	wp_add_inline_style( 'ul-portal', '.ul-portal{--ul-accent:' . esc_attr( $s['accent_color'] ) . ';}' );

	if ( empty( $s['portal_password_hash'] ) ) {
		if ( current_user_can( ul_capability() ) ) {
			return '<div class="ul-portal"><p class="ul-portal__setup">' .
				esc_html__( 'Configura la contraseña del portal en University Leads → Ajustes para activar esta página.', 'university-leads' ) . '</p></div>';
		}
		return '';
	}

	if ( ! ul_portal_is_authed() ) {
		return ul_portal_render_login();
	}

	return ul_portal_render_board();
}
add_shortcode( 'university_leads_dashboard', 'ul_portal_shortcode' );

/**
 * Pantalla de ingreso con contraseña.
 */
function ul_portal_render_login() {
	$error = isset( $_GET['ul_portal_error'] ) ? sanitize_key( wp_unslash( $_GET['ul_portal_error'] ) ) : '';

	ob_start();
	?>
	<div class="ul-portal">
		<div class="ul-portal__login">
			<span class="ul-portal__login-emoji">🔐</span>
			<h3 class="ul-portal__login-title"><?php esc_html_e( 'Portal de leads', 'university-leads' ); ?></h3>
			<p class="ul-portal__login-sub"><?php esc_html_e( 'Ingresa la contraseña del equipo para ver los leads.', 'university-leads' ); ?></p>

			<?php if ( 'bad' === $error ) : ?>
				<p class="ul-portal__error"><?php esc_html_e( 'Contraseña incorrecta. Inténtalo de nuevo.', 'university-leads' ); ?></p>
			<?php elseif ( 'locked' === $error ) : ?>
				<p class="ul-portal__error"><?php esc_html_e( 'Demasiados intentos. Espera 10 minutos e inténtalo de nuevo.', 'university-leads' ); ?></p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ul_portal_login" />
				<input type="hidden" name="ul_redirect" value="<?php echo esc_url( ul_current_url() ); ?>" />
				<?php wp_nonce_field( 'ul_portal_login', 'ul_portal_nonce' ); ?>
				<input class="ul-portal__pw" type="password" name="ul_portal_pw" required placeholder="<?php esc_attr_e( 'Contraseña', 'university-leads' ); ?>" autocomplete="current-password" />
				<button type="submit" class="ul-portal__btn"><?php esc_html_e( 'Entrar', 'university-leads' ); ?></button>
			</form>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Tablero de leads del portal.
 */
function ul_portal_render_board() {
	$statuses = ul_get_statuses();
	$packages = ul_get_packages();
	$fields   = ul_get_fields();
	$current  = ul_current_url();

	$filter = isset( $_GET['ul_estado'] ) ? sanitize_key( wp_unslash( $_GET['ul_estado'] ) ) : '';
	if ( ! isset( $statuses[ $filter ] ) ) {
		$filter = '';
	}

	$leads = get_posts(
		array(
			'post_type'      => UL_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	// Conteo por estado para las pestañas (estados desconocidos cuentan como "nuevo").
	$counts = array_fill_keys( array_keys( $statuses ), 0 );
	foreach ( $leads as $lead ) {
		$e = get_post_meta( $lead->ID, '_ul_estado', true );
		$e = isset( $counts[ $e ] ) ? $e : 'nuevo';
		$counts[ $e ]++;
	}

	$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=ul_portal_export' ), 'ul_portal_export' );
	$logout_url = admin_url( 'admin-post.php?action=ul_portal_logout' );

	ob_start();
	?>
	<div class="ul-portal">
		<div class="ul-portal__bar">
			<div>
				<h3 class="ul-portal__title"><?php esc_html_e( 'Leads de estudiantes', 'university-leads' ); ?></h3>
				<p class="ul-portal__count">
					<?php
					/* translators: %d: cantidad total de leads. */
					echo esc_html( sprintf( _n( '%d lead registrado', '%d leads registrados', count( $leads ), 'university-leads' ), count( $leads ) ) );
					?>
				</p>
			</div>
			<div class="ul-portal__actions">
				<a class="ul-portal__btn ul-portal__btn--export" href="<?php echo esc_url( $export_url ); ?>">⬇️ <?php esc_html_e( 'Exportar (Excel/CSV)', 'university-leads' ); ?></a>
				<a class="ul-portal__logout" href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Cerrar sesión', 'university-leads' ); ?></a>
			</div>
		</div>

		<div class="ul-portal__tabs">
			<a class="ul-portal__tab <?php echo '' === $filter ? 'is-active' : ''; ?>" href="<?php echo esc_url( remove_query_arg( 'ul_estado', $current ) ); ?>">
				<?php esc_html_e( 'Todos', 'university-leads' ); ?> <span><?php echo esc_html( count( $leads ) ); ?></span>
			</a>
			<?php foreach ( $statuses as $sk => $sl ) : ?>
				<a class="ul-portal__tab <?php echo $filter === $sk ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'ul_estado', $sk, $current ) ); ?>">
					<?php echo esc_html( $sl ); ?> <span><?php echo esc_html( $counts[ $sk ] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="ul-portal__list">
			<?php $shown = 0; ?>
			<?php foreach ( $leads as $lead ) : ?>
				<?php
				$estado = get_post_meta( $lead->ID, '_ul_estado', true );
				$estado = isset( $statuses[ $estado ] ) ? $estado : 'nuevo';
				if ( '' !== $filter && $filter !== $estado ) {
					continue;
				}
				$shown++;
				ul_portal_render_lead( $lead, $estado, $statuses, $packages, $fields, $current );
				?>
			<?php endforeach; ?>

			<?php if ( 0 === $shown ) : ?>
				<p class="ul-portal__empty"><?php esc_html_e( 'No hay leads en esta vista todavía.', 'university-leads' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Tarjeta de un lead en el portal.
 */
function ul_portal_render_lead( $lead, $estado, $statuses, $packages, $fields, $current ) {
	$email    = get_post_meta( $lead->ID, '_ul_email', true );
	$telefono = get_post_meta( $lead->ID, '_ul_telefono', true );
	$inst     = get_post_meta( $lead->ID, '_ul_institucion', true );
	$mensaje  = get_post_meta( $lead->ID, '_ul_mensaje', true );
	$answers  = get_post_meta( $lead->ID, '_ul_answers', true );
	$paquete  = get_post_meta( $lead->ID, '_ul_paquete', true );
	$pkg_name = isset( $packages[ $paquete ] ) ? $packages[ $paquete ]['name'] : $paquete;
	$wa_url   = $telefono ? 'https://wa.me/' . rawurlencode( preg_replace( '/[^0-9]/', '', $telefono ) ) : '';
	?>
	<div class="ul-plead">
		<div class="ul-plead__head">
			<div>
				<strong class="ul-plead__name"><?php echo esc_html( $lead->post_title ); ?></strong>
				<span class="ul-plead__date"><?php echo esc_html( get_the_date( 'd/m/Y H:i', $lead ) ); ?></span>
			</div>
			<span class="ul-plead__estado ul-plead__estado--<?php echo esc_attr( $estado ); ?>"><?php echo esc_html( $statuses[ $estado ] ); ?></span>
		</div>

		<?php if ( $pkg_name ) : ?>
			<span class="ul-plead__pkg">🎓 <?php echo esc_html( $pkg_name ); ?></span>
		<?php endif; ?>

		<div class="ul-plead__contact">
			<?php if ( $wa_url ) : ?>
				<a class="ul-plead__cta ul-plead__cta--wa" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener">💬 WhatsApp</a>
			<?php endif; ?>
			<?php if ( $email ) : ?>
				<a class="ul-plead__cta ul-plead__cta--mail" href="mailto:<?php echo esc_attr( $email ); ?>">✉️ <?php esc_html_e( 'Correo', 'university-leads' ); ?></a>
			<?php endif; ?>
			<?php if ( $telefono ) : ?>
				<a class="ul-plead__cta" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $telefono ) ); ?>">📞 <?php echo esc_html( $telefono ); ?></a>
			<?php endif; ?>
		</div>

		<details class="ul-plead__details">
			<summary><?php esc_html_e( 'Ver respuestas completas', 'university-leads' ); ?></summary>
			<ul>
				<?php if ( $email ) : ?>
					<li><strong><?php esc_html_e( 'Correo:', 'university-leads' ); ?></strong> <?php echo esc_html( $email ); ?></li>
				<?php endif; ?>
				<?php if ( $inst ) : ?>
					<li><strong><?php esc_html_e( 'Institución:', 'university-leads' ); ?></strong> <?php echo esc_html( $inst ); ?></li>
				<?php endif; ?>
				<?php if ( is_array( $answers ) ) : ?>
					<?php foreach ( $answers as $key => $value ) : ?>
						<li>
							<strong><?php echo esc_html( isset( $fields[ $key ]['label'] ) ? $fields[ $key ]['label'] : $key ); ?></strong><br />
							<?php echo esc_html( ul_answer_text( $key, $value ) ); ?>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
				<?php if ( $mensaje ) : ?>
					<li><strong><?php esc_html_e( 'Mensaje:', 'university-leads' ); ?></strong><br /><?php echo esc_html( $mensaje ); ?></li>
				<?php endif; ?>
			</ul>
		</details>

		<form class="ul-plead__status" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ul_portal_status" />
			<input type="hidden" name="ul_lead" value="<?php echo esc_attr( $lead->ID ); ?>" />
			<input type="hidden" name="ul_redirect" value="<?php echo esc_url( $current ); ?>" />
			<?php wp_nonce_field( 'ul_portal_status', 'ul_portal_nonce' ); ?>
			<label for="ul-pestado-<?php echo esc_attr( $lead->ID ); ?>"><?php esc_html_e( 'Estado:', 'university-leads' ); ?></label>
			<select id="ul-pestado-<?php echo esc_attr( $lead->ID ); ?>" name="ul_estado">
				<?php foreach ( $statuses as $sk => $sl ) : ?>
					<option value="<?php echo esc_attr( $sk ); ?>" <?php selected( $estado, $sk ); ?>><?php echo esc_html( $sl ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit"><?php esc_html_e( 'Guardar', 'university-leads' ); ?></button>
		</form>
	</div>
	<?php
}
