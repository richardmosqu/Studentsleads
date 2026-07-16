<?php
/**
 * University Leads — Dashboard kanban (tipo Asana) en el panel de administración.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ul_dashboard_menu() {
	add_menu_page(
		__( 'University Leads', 'university-leads' ),
		__( 'University Leads', 'university-leads' ),
		ul_capability(),
		'university-leads',
		'ul_render_dashboard',
		'dashicons-welcome-learn-more',
		26
	);
	add_submenu_page(
		'university-leads',
		__( 'Dashboard', 'university-leads' ),
		__( 'Dashboard', 'university-leads' ),
		ul_capability(),
		'university-leads',
		'ul_render_dashboard'
	);
}
add_action( 'admin_menu', 'ul_dashboard_menu' );

function ul_dashboard_assets( $hook ) {
	if ( 'toplevel_page_university-leads' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'ul-dashboard', UL_URL . 'assets/dashboard.css', array(), UL_VERSION );
	wp_enqueue_script( 'ul-dashboard', UL_URL . 'assets/dashboard.js', array(), UL_VERSION, true );
	wp_localize_script(
		'ul-dashboard',
		'ULDash',
		array(
			'nonce'    => wp_create_nonce( 'ul_dashboard' ),
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'errorMsg' => __( 'No se pudo guardar el cambio. Recarga la página e inténtalo de nuevo.', 'university-leads' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'ul_dashboard_assets' );

/**
 * Página principal: tablero con una columna por estado.
 */
function ul_render_dashboard() {
	if ( ! current_user_can( ul_capability() ) ) {
		wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'university-leads' ) );
	}

	$statuses = ul_get_statuses();
	$packages = ul_get_packages();
	$fields   = ul_get_fields();

	$leads = get_posts(
		array(
			'post_type'      => UL_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	// Agrupar leads por estado (los estados desconocidos caen en "nuevo").
	$grouped = array_fill_keys( array_keys( $statuses ), array() );
	foreach ( $leads as $lead ) {
		$estado = get_post_meta( $lead->ID, '_ul_estado', true );
		if ( ! isset( $grouped[ $estado ] ) ) {
			$estado = 'nuevo';
		}
		$grouped[ $estado ][] = $lead;
	}
	?>
	<div class="wrap ul-dash">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'University Leads', 'university-leads' ); ?></h1>
		<p class="ul-dash__hint"><?php esc_html_e( 'Arrastra las tarjetas entre columnas para actualizar el estado de cada estudiante.', 'university-leads' ); ?></p>

		<div class="ul-board">
			<?php foreach ( $statuses as $status_key => $status_label ) : ?>
				<div class="ul-column">
					<h2 class="ul-column__title">
						<?php echo esc_html( $status_label ); ?>
						<span class="ul-column__count"><?php echo esc_html( count( $grouped[ $status_key ] ) ); ?></span>
					</h2>
					<div class="ul-column__body" data-estado="<?php echo esc_attr( $status_key ); ?>">
						<?php foreach ( $grouped[ $status_key ] as $lead ) : ?>
							<?php ul_render_card( $lead, $packages, $fields, $statuses ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Tarjeta individual del tablero.
 */
function ul_render_card( $lead, $packages, $fields, $statuses ) {
	$email    = get_post_meta( $lead->ID, '_ul_email', true );
	$telefono = get_post_meta( $lead->ID, '_ul_telefono', true );
	$inst     = get_post_meta( $lead->ID, '_ul_institucion', true );
	$mensaje  = get_post_meta( $lead->ID, '_ul_mensaje', true );
	$answers  = get_post_meta( $lead->ID, '_ul_answers', true );
	$paquete  = get_post_meta( $lead->ID, '_ul_paquete', true );
	$estado   = get_post_meta( $lead->ID, '_ul_estado', true );
	$pkg_name = isset( $packages[ $paquete ] ) ? $packages[ $paquete ]['name'] : $paquete;

	$delete_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=ul_delete_lead&lead=' . $lead->ID ),
		'ul_delete_lead_' . $lead->ID
	);
	?>
	<div class="ul-card" draggable="true" data-lead="<?php echo esc_attr( $lead->ID ); ?>">
		<div class="ul-card__head">
			<strong class="ul-card__name"><?php echo esc_html( $lead->post_title ); ?></strong>
			<span class="ul-card__date"><?php echo esc_html( get_the_date( 'd/m/Y', $lead ) ); ?></span>
		</div>
		<?php if ( $pkg_name ) : ?>
			<span class="ul-card__pkg ul-card__pkg--<?php echo esc_attr( $paquete ); ?>"><?php echo esc_html( $pkg_name ); ?></span>
		<?php endif; ?>
		<div class="ul-card__contact">
			<?php if ( $email ) : ?>
				<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><br />
			<?php endif; ?>
			<?php if ( $telefono ) : ?>
				<span><?php echo esc_html( $telefono ); ?></span>
			<?php endif; ?>
		</div>

		<details class="ul-card__details">
			<summary><?php esc_html_e( 'Ver respuestas', 'university-leads' ); ?></summary>
			<ul>
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

		<div class="ul-card__actions">
			<label class="screen-reader-text" for="ul-estado-<?php echo esc_attr( $lead->ID ); ?>"><?php esc_html_e( 'Estado', 'university-leads' ); ?></label>
			<select id="ul-estado-<?php echo esc_attr( $lead->ID ); ?>" class="ul-card__status">
				<?php foreach ( $statuses as $sk => $sl ) : ?>
					<option value="<?php echo esc_attr( $sk ); ?>" <?php selected( $estado ? $estado : 'nuevo', $sk ); ?>><?php echo esc_html( $sl ); ?></option>
				<?php endforeach; ?>
			</select>
			<a class="ul-card__delete" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( '¿Eliminar este lead definitivamente?', 'university-leads' ) ); ?>');">
				<?php esc_html_e( 'Eliminar', 'university-leads' ); ?>
			</a>
		</div>
	</div>
	<?php
}

/**
 * AJAX: actualizar el estado de un lead (drag & drop o selector).
 */
function ul_ajax_update_status() {
	check_ajax_referer( 'ul_dashboard' );

	if ( ! current_user_can( ul_capability() ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	$lead_id = isset( $_POST['lead'] ) ? absint( $_POST['lead'] ) : 0;
	$estado  = isset( $_POST['estado'] ) ? sanitize_key( wp_unslash( $_POST['estado'] ) ) : '';

	$statuses = ul_get_statuses();
	if ( ! $lead_id || UL_CPT !== get_post_type( $lead_id ) || ! isset( $statuses[ $estado ] ) ) {
		wp_send_json_error( array( 'message' => 'invalid' ), 400 );
	}

	update_post_meta( $lead_id, '_ul_estado', $estado );
	wp_send_json_success( array( 'estado' => $estado ) );
}
add_action( 'wp_ajax_ul_update_status', 'ul_ajax_update_status' );

/**
 * Eliminar un lead desde el tablero.
 */
function ul_handle_delete_lead() {
	$lead_id = isset( $_GET['lead'] ) ? absint( $_GET['lead'] ) : 0;

	if ( ! current_user_can( ul_capability() ) ) {
		wp_die( esc_html__( 'No tienes permisos para hacer esto.', 'university-leads' ) );
	}
	check_admin_referer( 'ul_delete_lead_' . $lead_id );

	if ( $lead_id && UL_CPT === get_post_type( $lead_id ) ) {
		wp_delete_post( $lead_id, true );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=university-leads' ) );
	exit;
}
add_action( 'admin_post_ul_delete_lead', 'ul_handle_delete_lead' );
