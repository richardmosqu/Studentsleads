<?php
/**
 * Limpieza al desinstalar University Leads: elimina los leads y los ajustes.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$ul_leads = get_posts(
	array(
		'post_type'   => 'ul_lead',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);

foreach ( $ul_leads as $ul_lead_id ) {
	wp_delete_post( $ul_lead_id, true );
}

delete_option( 'ul_settings' );
