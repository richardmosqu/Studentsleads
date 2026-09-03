<?php
/**
 * University Leads — Definición de las preguntas del formulario.
 * Un solo lugar para etiquetas y opciones: las usan el formulario,
 * los correos y el dashboard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preguntas de selección del cuestionario.
 * Cada campo: etiqueta + opciones (valor => texto).
 */
function ul_get_fields() {
	$fields = array(
		'nivel'       => array(
			'label'   => __( '¿Cuál es tu nivel de estudios actual?', 'university-leads' ),
			'choices' => array(
				'secundaria' => __( 'Estudiante de secundaria', 'university-leads' ),
				'pregrado'   => __( 'Estudiante universitario (pregrado)', 'university-leads' ),
				'posgrado'   => __( 'Interesado en posgrado / maestría', 'university-leads' ),
				'egresado'   => __( 'Egresado / profesional', 'university-leads' ),
			),
		),
		'destino'     => array(
			'label'    => __( '¿En qué países te gustaría estudiar?', 'university-leads' ),
			'multiple' => true,
			'choices'  => array(
				'usa'        => __( 'Estados Unidos', 'university-leads' ),
				'canada'     => __( 'Canadá', 'university-leads' ),
				'espana'     => __( 'España', 'university-leads' ),
				'europa'     => __( 'Otro país de Europa', 'university-leads' ),
				'latam'      => __( 'Latinoamérica', 'university-leads' ),
				'cualquiera' => __( 'Aún no lo decido', 'university-leads' ),
			),
		),
		'presupuesto' => array(
			'label'   => __( '¿Con qué presupuesto anual cuentas (USD)?', 'university-leads' ),
			'choices' => array(
				'alto'  => __( 'Más de $20,000 al año', 'university-leads' ),
				'medio' => __( 'Entre $8,000 y $20,000 al año', 'university-leads' ),
				'bajo'  => __( 'Menos de $8,000 al año', 'university-leads' ),
			),
		),
		'ingles'      => array(
			'label'   => __( '¿Cuál es tu nivel de inglés?', 'university-leads' ),
			'choices' => array(
				'avanzado'   => __( 'Avanzado / fluido', 'university-leads' ),
				'intermedio' => __( 'Intermedio', 'university-leads' ),
				'basico'     => __( 'Básico', 'university-leads' ),
			),
		),
		'inicio'      => array(
			'label'   => __( '¿Cuándo te gustaría empezar?', 'university-leads' ),
			'choices' => array(
				'0-6'  => __( 'En los próximos 6 meses', 'university-leads' ),
				'6-12' => __( 'En 6 a 12 meses', 'university-leads' ),
				'12+'  => __( 'En más de un año', 'university-leads' ),
			),
		),
	);

	return apply_filters( 'ul_fields', $fields );
}

/**
 * Texto legible de una respuesta guardada (o el valor crudo si ya no existe la opción).
 */
function ul_choice_label( $field, $value ) {
	$fields = ul_get_fields();
	if ( isset( $fields[ $field ]['choices'][ $value ] ) ) {
		return $fields[ $field ]['choices'][ $value ];
	}
	return $value;
}

/**
 * Texto legible de una respuesta simple o múltiple (las múltiples se unen con comas).
 */
function ul_answer_text( $field, $value ) {
	if ( is_array( $value ) ) {
		$labels = array();
		foreach ( $value as $v ) {
			$labels[] = ul_choice_label( $field, $v );
		}
		return implode( ', ', $labels );
	}
	return ul_choice_label( $field, $value );
}
