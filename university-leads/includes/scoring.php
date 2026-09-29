<?php
/**
 * University Leads — Algoritmo de recomendación de paquete.
 *
 * Cada respuesta suma puntos a uno o más paquetes; el paquete con
 * mayor puntaje total es el recomendado. La matriz de puntos se puede
 * ajustar con el filtro `ul_scoring_matrix`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paquetes disponibles (slug => nombre y descripción por defecto).
 * Los nombres/descripciones se personalizan en Ajustes; los slugs son
 * fijos porque la matriz de puntos apunta a ellos.
 */
function ul_default_packages() {
	return array(
		'premium'  => array(
			'name' => __( 'Paquete Premium Internacional', 'university-leads' ),
			'desc' => __( 'Universidades top en EE.UU. y Canadá, con acompañamiento completo de admisión, visa y llegada.', 'university-leads' ),
		),
		'estandar' => array(
			'name' => __( 'Paquete Universitario Estándar', 'university-leads' ),
			'desc' => __( 'Programas universitarios en Europa y Latinoamérica con excelente relación calidad-precio.', 'university-leads' ),
		),
		'idiomas'  => array(
			'name' => __( 'Paquete Idiomas + Universidad', 'university-leads' ),
			'desc' => __( 'Curso intensivo de idioma en el extranjero como puerta de entrada a la universidad.', 'university-leads' ),
		),
	);
}

/**
 * Paquetes con los nombres/descripciones personalizados en Ajustes.
 */
function ul_get_packages() {
	$packages = ul_default_packages();
	$settings = ul_get_settings();

	foreach ( $packages as $slug => $pkg ) {
		if ( ! empty( $settings['packages'][ $slug ]['name'] ) ) {
			$packages[ $slug ]['name'] = $settings['packages'][ $slug ]['name'];
		}
		if ( ! empty( $settings['packages'][ $slug ]['desc'] ) ) {
			$packages[ $slug ]['desc'] = $settings['packages'][ $slug ]['desc'];
		}
	}

	return $packages;
}

/**
 * Matriz de puntos: pregunta => respuesta => (paquete => puntos).
 *
 * El nivel de inglés y el destino son las señales principales;
 * el resto afina la recomendación.
 */
function ul_scoring_matrix() {
	$matrix = array(
		'ingles'      => array(
			'avanzado'   => array( 'premium' => 2, 'estandar' => 1 ),
			'intermedio' => array( 'estandar' => 2 ),
			'basico'     => array( 'idiomas' => 3 ),
		),
		'destino'     => array(
			'usa'        => array( 'premium' => 2 ),
			'canada'     => array( 'premium' => 2 ),
			'espana'     => array( 'estandar' => 2 ),
			'europa'     => array( 'estandar' => 2 ),
			'latam'      => array( 'estandar' => 2 ),
			'cualquiera' => array( 'idiomas' => 1, 'estandar' => 1 ),
		),
		'inicio'      => array(
			'0-6'  => array( 'premium' => 1, 'estandar' => 1 ),
			'6-12' => array( 'estandar' => 1 ),
			'12+'  => array( 'idiomas' => 1 ),
		),
		'nivel'       => array(
			'secundaria' => array( 'idiomas' => 1 ),
			'pregrado'   => array( 'estandar' => 1 ),
			'posgrado'   => array( 'premium' => 1 ),
			'egresado'   => array( 'premium' => 1, 'estandar' => 1 ),
		),
	);

	return apply_filters( 'ul_scoring_matrix', $matrix );
}

/**
 * Calcula el paquete recomendado a partir de las respuestas.
 *
 * @param array $answers campo => valor elegido.
 * @return array { paquete: slug ganador, scores: puntaje por paquete }
 */
function ul_score_lead( $answers ) {
	$matrix = ul_scoring_matrix();
	$scores = array_fill_keys( array_keys( ul_default_packages() ), 0 );

	foreach ( $answers as $field => $value ) {
		// Las respuestas múltiples (ej. varios países) suman los puntos de cada opción elegida.
		foreach ( (array) $value as $v ) {
			if ( empty( $matrix[ $field ][ $v ] ) ) {
				continue;
			}
			foreach ( $matrix[ $field ][ $v ] as $package => $points ) {
				if ( isset( $scores[ $package ] ) ) {
					$scores[ $package ] += $points;
				}
			}
		}
	}

	arsort( $scores );
	$winner = (string) array_key_first( $scores );

	return array(
		'paquete' => $winner,
		'scores'  => $scores,
	);
}
