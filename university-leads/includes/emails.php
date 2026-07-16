<?php
/**
 * University Leads — Notificaciones por correo en HTML (equipo y estudiante).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plantilla base de los correos: encabezado con logo/color de marca,
 * cuerpo y pie. Estilos inline para compatibilidad con clientes de correo.
 */
function ul_email_template( $preheader, $content_html ) {
	$s        = ul_get_settings();
	$accent   = $s['accent_color'];
	$logo_url = $s['logo_id'] ? wp_get_attachment_image_url( $s['logo_id'], 'medium' ) : '';
	$site     = get_bloginfo( 'name' );

	$header_inner = $logo_url
		? '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $site ) . '" style="max-height:56px;max-width:220px;" />'
		: '<span style="color:#ffffff;font-size:20px;font-weight:700;">' . esc_html( $site ) . '</span>';

	return '<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8" /><meta name="viewport" content="width=device-width" /></head>
<body style="margin:0;padding:0;background:#f1f5f9;">
	<span style="display:none;max-height:0;overflow:hidden;">' . esc_html( $preheader ) . '</span>
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
		<tr><td align="center">
			<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
				<tr>
					<td align="center" style="background:' . esc_attr( $accent ) . ';padding:22px;">' . $header_inner . '</td>
				</tr>
				<tr>
					<td style="padding:32px 28px;color:#0f172a;font-size:15px;line-height:1.6;">' . $content_html . '</td>
				</tr>
				<tr>
					<td align="center" style="background:#f8fafc;padding:18px;color:#94a3b8;font-size:12px;">
						' . esc_html( $site ) . ' · <a href="' . esc_url( home_url( '/' ) ) . '" style="color:#64748b;">' . esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ) . '</a>
						&nbsp;·&nbsp; <a href="https://theuforyou.com" style="color:#64748b;">theuforyou.com</a>
					</td>
				</tr>
			</table>
		</td></tr>
	</table>
</body>
</html>';
}

/**
 * Tarjeta destacada del paquete recomendado (compartida por ambos correos).
 */
function ul_email_package_card( $pkg_name, $pkg_desc, $accent ) {
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;">
		<tr><td style="border:2px solid ' . esc_attr( $accent ) . ';border-radius:12px;padding:16px 20px;background:#f8faff;">
			<span style="display:block;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;color:' . esc_attr( $accent ) . ';margin-bottom:6px;">Paquete recomendado</span>
			<span style="display:block;font-size:18px;font-weight:700;color:#0f172a;margin-bottom:6px;">🎓 ' . esc_html( $pkg_name ) . '</span>
			<span style="display:block;font-size:14px;color:#475569;">' . esc_html( $pkg_desc ) . '</span>
		</td></tr>
	</table>';
}

/**
 * Botón centrado para los correos.
 */
function ul_email_button( $url, $text, $accent ) {
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0 6px;">
		<tr><td align="center">
			<a href="' . esc_url( $url ) . '" style="display:inline-block;background:' . esc_attr( $accent ) . ';color:#ffffff;font-weight:700;font-size:15px;text-decoration:none;padding:13px 34px;border-radius:10px;">' . esc_html( $text ) . '</a>
		</td></tr>
	</table>';
}

/**
 * Fila de la tabla de datos del correo interno.
 */
function ul_email_row( $label, $value_html ) {
	return '<tr>
		<td style="padding:9px 12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:13px;font-weight:700;color:#475569;white-space:nowrap;vertical-align:top;">' . esc_html( $label ) . '</td>
		<td style="padding:9px 12px;border-bottom:1px solid #e2e8f0;font-size:14px;color:#0f172a;">' . $value_html . '</td>
	</tr>';
}

/**
 * Aviso interno al equipo con todos los datos del lead.
 */
function ul_notify_team( $lead_id ) {
	$s  = ul_get_settings();
	$to = $s['notify_email'] ? $s['notify_email'] : get_option( 'admin_email' );
	$to = apply_filters( 'ul_team_recipient', $to, $lead_id );
	if ( empty( $to ) ) {
		return;
	}

	$accent   = $s['accent_color'];
	$nombre   = get_the_title( $lead_id );
	$email    = get_post_meta( $lead_id, '_ul_email', true );
	$telefono = get_post_meta( $lead_id, '_ul_telefono', true );
	$inst     = get_post_meta( $lead_id, '_ul_institucion', true );
	$mensaje  = get_post_meta( $lead_id, '_ul_mensaje', true );
	$answers  = get_post_meta( $lead_id, '_ul_answers', true );
	$paquete  = get_post_meta( $lead_id, '_ul_paquete', true );
	$packages = ul_get_packages();
	$pkg      = isset( $packages[ $paquete ] ) ? $packages[ $paquete ] : array( 'name' => $paquete, 'desc' => '' );

	/* translators: %s: nombre del estudiante. */
	$subject = sprintf( __( '🎯 Nuevo lead: %s', 'university-leads' ), $nombre );

	$contact_rows  = ul_email_row( __( 'Nombre', 'university-leads' ), esc_html( $nombre ) );
	$contact_rows .= ul_email_row(
		__( 'Correo', 'university-leads' ),
		$email ? '<a href="mailto:' . esc_attr( $email ) . '" style="color:' . esc_attr( $accent ) . ';">' . esc_html( $email ) . '</a>' : '—'
	);
	$tel_link      = $telefono ? 'https://wa.me/' . rawurlencode( preg_replace( '/[^0-9]/', '', $telefono ) ) : '';
	$contact_rows .= ul_email_row(
		__( 'Teléfono', 'university-leads' ),
		$telefono ? esc_html( $telefono ) . ' &nbsp;·&nbsp; <a href="' . esc_url( $tel_link ) . '" style="color:#16a34a;font-weight:700;">WhatsApp ↗</a>' : '—'
	);
	$contact_rows .= ul_email_row( __( 'Institución', 'university-leads' ), $inst ? esc_html( $inst ) : '—' );

	$answer_rows = '';
	if ( is_array( $answers ) ) {
		$fields = ul_get_fields();
		foreach ( $answers as $key => $value ) {
			$label        = isset( $fields[ $key ]['label'] ) ? $fields[ $key ]['label'] : $key;
			$answer_rows .= ul_email_row( $label, esc_html( ul_answer_text( $key, $value ) ) );
		}
	}

	$message_block = '';
	if ( $mensaje ) {
		$message_block = '<p style="margin:18px 0 6px;font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.04em;">' . esc_html__( 'Mensaje del estudiante', 'university-leads' ) . '</p>
			<div style="border-left:4px solid ' . esc_attr( $accent ) . ';background:#f8fafc;padding:12px 16px;border-radius:0 10px 10px 0;color:#334155;font-style:italic;">' . nl2br( esc_html( $mensaje ) ) . '</div>';
	}

	$content = '<h2 style="margin:0 0 4px;font-size:21px;color:#0f172a;">' . esc_html__( '¡Nuevo lead recibido! 🎉', 'university-leads' ) . '</h2>
		<p style="margin:0 0 18px;color:#64748b;">' . esc_html__( 'Un estudiante acaba de completar el formulario en el sitio web.', 'university-leads' ) . '</p>
		' . ul_email_package_card( $pkg['name'], $pkg['desc'], $accent ) . '
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:10px;border-collapse:separate;overflow:hidden;">
			' . $contact_rows . $answer_rows . '
		</table>
		' . $message_block . '
		' . ul_email_button( admin_url( 'admin.php?page=university-leads' ), __( 'Abrir el dashboard de leads', 'university-leads' ), $accent );

	$headers   = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( $email ) {
		$headers[] = 'Reply-To: ' . $nombre . ' <' . $email . '>';
	}

	wp_mail( $to, $subject, ul_email_template( $nombre . ' — ' . $pkg['name'], $content ), $headers );
}

/**
 * Confirmación al estudiante, con los comodines {nombre} y {paquete}.
 */
function ul_notify_student( $lead_id ) {
	$email = get_post_meta( $lead_id, '_ul_email', true );
	if ( ! is_email( $email ) ) {
		return;
	}

	$s        = ul_get_settings();
	$accent   = $s['accent_color'];
	$nombre   = get_the_title( $lead_id );
	$paquete  = get_post_meta( $lead_id, '_ul_paquete', true );
	$packages = ul_get_packages();
	$pkg      = isset( $packages[ $paquete ] ) ? $packages[ $paquete ] : array( 'name' => $paquete, 'desc' => '' );

	$replacements = array(
		'{nombre}'  => $nombre,
		'{paquete}' => $pkg['name'],
	);

	$subject = strtr( $s['student_subject'], $replacements );
	$body    = strtr( $s['student_body'], $replacements );

	// El cuerpo configurado en Ajustes se convierte en párrafos HTML.
	$paragraphs = '';
	foreach ( preg_split( '/\n{2,}/', $body ) as $p ) {
		$p = trim( $p );
		if ( '' !== $p ) {
			$paragraphs .= '<p style="margin:0 0 14px;color:#334155;">' . nl2br( esc_html( $p ) ) . '</p>';
		}
	}

	$steps = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 8px;">
		<tr><td style="padding:7px 0;color:#334155;font-size:14px;">1️⃣ &nbsp;' . esc_html__( 'Un asesor revisa tu perfil y tu paquete recomendado.', 'university-leads' ) . '</td></tr>
		<tr><td style="padding:7px 0;color:#334155;font-size:14px;">2️⃣ &nbsp;' . esc_html__( 'Te contactamos por correo o WhatsApp para una asesoría 1 a 1 gratis.', 'university-leads' ) . '</td></tr>
		<tr><td style="padding:7px 0;color:#334155;font-size:14px;">3️⃣ &nbsp;' . esc_html__( 'Armamos juntos tu plan para estudiar en el extranjero. ✈️', 'university-leads' ) . '</td></tr>
	</table>';

	$content = '<h2 style="margin:0 0 16px;font-size:21px;color:#0f172a;">' . esc_html( sprintf( /* translators: %s: nombre del estudiante. */ __( '¡Hola, %s! 👋', 'university-leads' ), $nombre ) ) . '</h2>
		' . $paragraphs . '
		' . ul_email_package_card( $pkg['name'], $pkg['desc'], $accent ) . '
		<p style="margin:18px 0 6px;font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.04em;">' . esc_html__( '¿Qué sigue ahora?', 'university-leads' ) . '</p>
		' . $steps;

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	wp_mail( $email, $subject, ul_email_template( $subject, $content ), $headers );
}
