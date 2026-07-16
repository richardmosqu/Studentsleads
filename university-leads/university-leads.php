<?php
/**
 * Plugin Name:       University Leads
 * Plugin URI:        https://campuslifepa.com
 * Description:       Captación de estudiantes interesados en paquetes para estudiar en el extranjero: formulario con recomendación automática de paquete, dashboard kanban de seguimiento y notificaciones por correo. Para Campus Life / TheUForYou.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            TheUForYou
 * Author URI:        https://campuslifepa.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       university-leads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UL_VERSION', '1.3.0' );
define( 'UL_CPT', 'ul_lead' );
define( 'UL_DIR', plugin_dir_path( __FILE__ ) );
define( 'UL_URL', plugin_dir_url( __FILE__ ) );

require_once UL_DIR . 'includes/settings.php';
require_once UL_DIR . 'includes/fields.php';
require_once UL_DIR . 'includes/scoring.php';
require_once UL_DIR . 'includes/cpt.php';
require_once UL_DIR . 'includes/form.php';
require_once UL_DIR . 'includes/emails.php';
require_once UL_DIR . 'includes/dashboard.php';
require_once UL_DIR . 'includes/portal.php';

/**
 * Capacidad requerida para ver el dashboard y los ajustes.
 * Cambiable con el filtro `ul_dashboard_capability`.
 */
function ul_capability() {
	return apply_filters( 'ul_dashboard_capability', 'manage_options' );
}
