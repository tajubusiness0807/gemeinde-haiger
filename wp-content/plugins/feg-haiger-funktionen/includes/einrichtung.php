<?php
/**
 * Einrichtung nach dem Import, Weiterleitungen alter Joomla-Adressen, Einstellungsseite.
 *
 * @package feg-haiger-funktionen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Website-Einstellungen passend zu den importierten Inhalten setzen.
 *
 * - Seite „home“ als statische Startseite (die Unterseiten behalten so ihre alten
 *   Adressen wie /home/kontakt/),
 * - Datenschutzerklärung verknüpfen,
 * - sprechende Permalinks (/%postname%/), falls noch „einfach“ eingestellt.
 */
function feg_einrichtung_ausfuehren() {
	$start = get_page_by_path( 'home' );
	if ( $start && 'page' !== get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $start->ID );
	}

	$datenschutz = get_page_by_path( 'home/datenschutzerklaerung' );
	if ( $datenschutz ) {
		$aktuell = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( ! $aktuell || 'publish' !== get_post_status( $aktuell ) ) {
			update_option( 'wp_page_for_privacy_policy', $datenschutz->ID );
		}
	}

	// „Einfach“ oder die Datums-Voreinstellung einer frischen Installation durch /%postname%/ ersetzen,
	// damit Links wie /ladies-time-mit-elena-schulte/ aus den importierten Inhalten passen.
	if ( in_array( (string) get_option( 'permalink_structure' ), array( '', '/%year%/%monthnum%/%day%/%postname%/' ), true ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	if ( ! get_option( 'feg_kontakt_empfaenger' ) ) {
		update_option( 'feg_kontakt_empfaenger', 'kontakt@haiger.feg.de' );
	}

	flush_rewrite_rules( false );
}
add_action( 'import_end', 'feg_einrichtung_ausfuehren' );

/**
 * Alte Joomla-Adressen auf die neuen WordPress-Adressen umleiten (nur bei 404).
 */
function feg_alte_adressen_umleiten() {
	if ( ! is_404() ) {
		return;
	}
	$pfad = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH );
	$pfad = trim( (string) $pfad, '/' );

	// Joomla-Beiträge: /aktuelles/141-ladies-time-mit-elena-schulte → Beitrag „ladies-time-mit-elena-schulte“.
	if ( preg_match( '#(?:^|/)aktuelles/(\d+)-([a-z0-9-]+)$#', $pfad, $m ) ) {
		$beitrag = get_page_by_path( $m[2], OBJECT, 'post' );
		if ( $beitrag && 'publish' === $beitrag->post_status ) {
			wp_safe_redirect( get_permalink( $beitrag ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'feg_alte_adressen_umleiten', 5 );

/**
 * E-Mail-Adressen in Inhalten vor Spam-Robotern verschleiern (wie bisher in Joomla).
 *
 * @param string $html Block-HTML.
 * @return string
 */
function feg_emails_verschleiern( $html ) {
	if ( false === strpos( $html, 'mailto:' ) ) {
		return $html;
	}
	return preg_replace_callback(
		'/mailto:([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/',
		static function ( $m ) {
			return 'mailto:' . antispambot( $m[1] );
		},
		$html
	);
}
add_filter( 'render_block', 'feg_emails_verschleiern' );

/**
 * Einstellungsseite „Einstellungen → FeG Haiger“.
 */
function feg_einstellungen_registrieren() {
	register_setting(
		'feg_haiger',
		'feg_termine_kalender',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => feg_termine_standard_kalender(),
		)
	);
	register_setting(
		'feg_haiger',
		'feg_termine_tage',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 90,
		)
	);
	register_setting(
		'feg_haiger',
		'feg_kontakt_empfaenger',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => 'kontakt@haiger.feg.de',
		)
	);
}
add_action( 'admin_init', 'feg_einstellungen_registrieren' );

/**
 * Zwischenspeicher der Kalender leeren, wenn die Kalenderliste geändert wird.
 */
function feg_termine_cache_leeren() {
	foreach ( feg_termine_kalender() as $kalender ) {
		delete_transient( 'feg_ics_' . md5( $kalender['url'] ) );
	}
}
add_action( 'update_option_feg_termine_kalender', 'feg_termine_cache_leeren' );

/**
 * Menüeintrag.
 */
function feg_einstellungen_menue() {
	add_options_page(
		__( 'FeG Haiger', 'feg-haiger-funktionen' ),
		__( 'FeG Haiger', 'feg-haiger-funktionen' ),
		'manage_options',
		'feg-haiger',
		'feg_einstellungen_seite'
	);
}
add_action( 'admin_menu', 'feg_einstellungen_menue' );

/**
 * Inhalt der Einstellungsseite.
 */
function feg_einstellungen_seite() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['feg_einrichtung'] ) && check_admin_referer( 'feg_einrichtung' ) ) {
		feg_einrichtung_ausfuehren();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Startseite, Datenschutzseite und Permalinks wurden eingerichtet.', 'feg-haiger-funktionen' ) . '</p></div>';
	}
	if ( isset( $_POST['feg_cache'] ) && check_admin_referer( 'feg_cache' ) ) {
		feg_termine_cache_leeren();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Die Kalender werden beim nächsten Seitenaufruf neu geladen.', 'feg-haiger-funktionen' ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'FeG Haiger – Einstellungen', 'feg-haiger-funktionen' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'feg_haiger' ); ?>
			<h2><?php esc_html_e( 'Terminvorschau', 'feg-haiger-funktionen' ); ?></h2>
			<p><?php echo wp_kses_post( __( 'Die Termine werden aus den öffentlichen Google-Kalendern geladen und eine Stunde zwischengespeichert. Einbinden mit dem Shortcode <code>[feg_termine]</code> (Optionen: <code>anzahl="12"</code>, <code>tage="90"</code>, <code>ansicht="liste"</code>, <code>kalender="Gottesdienste, Senioren"</code>).', 'feg-haiger-funktionen' ) ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="feg_termine_kalender"><?php esc_html_e( 'Kalender', 'feg-haiger-funktionen' ); ?></label></th>
					<td>
						<textarea name="feg_termine_kalender" id="feg_termine_kalender" rows="7" class="large-text code"><?php echo esc_textarea( get_option( 'feg_termine_kalender', feg_termine_standard_kalender() ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Eine Zeile je Kalender: Name | Farbe | ICS-Adresse. Die ICS-Adresse findest du in Google Kalender unter „Einstellungen und Freigabe → Öffentliche Adresse im iCal-Format“.', 'feg-haiger-funktionen' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="feg_termine_tage"><?php esc_html_e( 'Zeitraum (Tage)', 'feg-haiger-funktionen' ); ?></label></th>
					<td><input type="number" min="1" max="730" name="feg_termine_tage" id="feg_termine_tage" value="<?php echo esc_attr( (string) get_option( 'feg_termine_tage', 90 ) ); ?>" class="small-text"></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Kontaktformular', 'feg-haiger-funktionen' ); ?></h2>
			<p><?php echo wp_kses_post( __( 'Einbinden mit dem Shortcode <code>[feg_kontaktformular]</code>.', 'feg-haiger-funktionen' ) ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="feg_kontakt_empfaenger"><?php esc_html_e( 'Empfänger', 'feg-haiger-funktionen' ); ?></label></th>
					<td><input type="email" name="feg_kontakt_empfaenger" id="feg_kontakt_empfaenger" value="<?php echo esc_attr( get_option( 'feg_kontakt_empfaenger', 'kontakt@haiger.feg.de' ) ); ?>" class="regular-text"></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<hr>
		<h2><?php esc_html_e( 'Werkzeuge', 'feg-haiger-funktionen' ); ?></h2>
		<form method="post" style="display:inline-block;margin-right:1em">
			<?php wp_nonce_field( 'feg_einrichtung' ); ?>
			<p><?php esc_html_e( 'Nach dem Import: Seite „Startseite“ als Startseite festlegen, Datenschutzerklärung verknüpfen und sprechende Permalinks aktivieren.', 'feg-haiger-funktionen' ); ?></p>
			<?php submit_button( __( 'Website einrichten', 'feg-haiger-funktionen' ), 'secondary', 'feg_einrichtung', false ); ?>
		</form>
		<form method="post" style="display:inline-block">
			<?php wp_nonce_field( 'feg_cache' ); ?>
			<p><?php esc_html_e( 'Kalender sofort neu laden:', 'feg-haiger-funktionen' ); ?></p>
			<?php submit_button( __( 'Zwischenspeicher leeren', 'feg-haiger-funktionen' ), 'secondary', 'feg_cache', false ); ?>
		</form>
	</div>
	<?php
}
