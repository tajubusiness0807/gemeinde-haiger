<?php
/**
 * Plugin Name:       FeG Haiger – Funktionen
 * Plugin URI:        https://haiger.feg.de/
 * Description:       Terminvorschau aus den Google-Kalendern der Gemeinde ([feg_termine]), Kontaktformular ([feg_kontaktformular]), Weiterleitungen der alten Joomla-Adressen und Einrichtung nach dem Inhalts-Import.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Freie evangelische Gemeinde Haiger
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       feg-haiger-funktionen
 *
 * @package feg-haiger-funktionen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FEG_HAIGER_VERSION', '1.0.0' );
define( 'FEG_HAIGER_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/class-feg-ical.php';
require_once __DIR__ . '/includes/termine.php';
require_once __DIR__ . '/includes/kontakt.php';
require_once __DIR__ . '/includes/einrichtung.php';

register_activation_hook( __FILE__, 'feg_einrichtung_ausfuehren' );
