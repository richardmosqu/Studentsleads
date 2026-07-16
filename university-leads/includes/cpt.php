<?php
/**
 * University Leads — Tipo de contenido interno para los leads.
 * No tiene UI propia: la gestión se hace desde el dashboard kanban.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ul_register_cpt() {
	register_post_type(
		UL_CPT,
		array(
			'labels'          => array(
				'name'          => __( 'Leads universitarios', 'university-leads' ),
				'singular_name' => __( 'Lead universitario', 'university-leads' ),
			),
			'public'          => false,
			'show_ui'         => false,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'ul_register_cpt' );

/**
 * Estados del pipeline (columnas del kanban), en orden.
 */
function ul_get_statuses() {
	return array(
		'nuevo'      => __( 'Nuevos', 'university-leads' ),
		'potencial'  => __( 'Potenciales', 'university-leads' ),
		'contactado' => __( 'Contactados', 'university-leads' ),
		'cerrado'    => __( 'Cerrados', 'university-leads' ),
	);
}
