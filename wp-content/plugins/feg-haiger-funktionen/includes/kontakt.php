<?php
/**
 * Kontaktformular: [feg_kontaktformular].
 *
 * Ersetzt das bisherige Joomla-Modul „DJ Easy Contact“. Mit Spam-Schutz (Honeypot,
 * Mindest-Ausfülldauer, Nonce, Sendebegrenzung je IP) und Datenschutz-Zustimmung.
 *
 * @package feg-haiger-funktionen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ergebnis der Verarbeitung im aktuellen Aufruf (Fehler und eingegebene Werte).
 *
 * @var array{fehler:string[],werte:array<string,string>}|null
 */
$GLOBALS['feg_kontakt_status'] = null;

/**
 * Empfängeradresse des Formulars.
 *
 * @return string
 */
function feg_kontakt_empfaenger() {
	$adresse = get_option( 'feg_kontakt_empfaenger', '' );
	return is_email( $adresse ) ? $adresse : get_option( 'admin_email' );
}

/**
 * Formular verarbeiten (vor der Ausgabe, damit eine Weiterleitung möglich ist).
 */
function feg_kontakt_verarbeiten() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['feg_kontakt'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
		return;
	}

	$fehler = array();
	$werte  = array(
		'name'      => isset( $_POST['feg_name'] ) ? sanitize_text_field( wp_unslash( $_POST['feg_name'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'email'     => isset( $_POST['feg_email'] ) ? sanitize_email( wp_unslash( $_POST['feg_email'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'nachricht' => isset( $_POST['feg_nachricht'] ) ? sanitize_textarea_field( wp_unslash( $_POST['feg_nachricht'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
	);

	$nonce = isset( $_POST['_feg_kontakt_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_feg_kontakt_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'feg_kontakt' ) ) {
		$fehler[] = __( 'Die Sitzung ist abgelaufen. Bitte lade die Seite neu und sende das Formular erneut.', 'feg-haiger-funktionen' );
	}

	// Honeypot: Dieses Feld ist für Menschen unsichtbar und muss leer bleiben.
	$honig = ! empty( $_POST['feg_website'] );
	// Bots senden meist sofort ab – mindestens 3 Sekunden Ausfüllzeit verlangen.
	$zeit    = isset( $_POST['feg_zeit'] ) ? (int) $_POST['feg_zeit'] : 0;
	$zu_fix  = ( time() - $zeit ) < 3;
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$sperre  = 'feg_kontakt_' . md5( $ip );
	$anzahl  = (int) get_transient( $sperre );

	if ( '' === $werte['name'] ) {
		$fehler[] = __( 'Bitte gib deinen Namen an.', 'feg-haiger-funktionen' );
	}
	if ( ! is_email( $werte['email'] ) ) {
		$fehler[] = __( 'Bitte gib eine gültige E-Mail-Adresse an.', 'feg-haiger-funktionen' );
	}
	if ( '' === $werte['nachricht'] ) {
		$fehler[] = __( 'Bitte schreibe eine Nachricht.', 'feg-haiger-funktionen' );
	}
	if ( empty( $_POST['feg_datenschutz'] ) ) {
		$fehler[] = __( 'Bitte stimme der Datenschutzerklärung zu.', 'feg-haiger-funktionen' );
	}
	if ( $anzahl >= 5 ) {
		$fehler[] = __( 'Es wurden zu viele Nachrichten gesendet. Bitte versuche es später noch einmal.', 'feg-haiger-funktionen' );
	}

	// Nach dem Absenden zurück auf die Seite mit dem Formular.
	$ziel = is_singular() ? get_permalink( get_queried_object_id() ) : home_url( '/' );

	if ( ! $fehler && ( $honig || $zu_fix ) ) {
		// Vermutlich Spam: still verwerfen, aber wie Erfolg antworten.
		wp_safe_redirect( add_query_arg( 'kontakt', 'gesendet', $ziel ) . '#feg-kontakt' );
		exit;
	}

	if ( ! $fehler ) {
		$betreff = sprintf(
			/* translators: 1: Name der Website, 2: Name des Absenders */
			__( '[%1$s] Kontaktanfrage von %2$s', 'feg-haiger-funktionen' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$werte['name']
		);
		$text = sprintf(
			"%s: %s\n%s: %s\n\n%s\n\n-- \n%s",
			__( 'Name', 'feg-haiger-funktionen' ),
			$werte['name'],
			__( 'E-Mail', 'feg-haiger-funktionen' ),
			$werte['email'],
			$werte['nachricht'],
			/* translators: %s: Adresse der Seite */
			sprintf( __( 'Gesendet über das Kontaktformular auf %s', 'feg-haiger-funktionen' ), $ziel )
		);
		$kopf = array( 'Reply-To: ' . str_replace( array( "\r", "\n", '"' ), '', $werte['name'] ) . ' <' . $werte['email'] . '>' );

		if ( wp_mail( feg_kontakt_empfaenger(), $betreff, $text, $kopf ) ) {
			set_transient( $sperre, $anzahl + 1, HOUR_IN_SECONDS );
			wp_safe_redirect( add_query_arg( 'kontakt', 'gesendet', $ziel ) . '#feg-kontakt' );
			exit;
		}
		$fehler[] = __( 'Es ist etwas falsch gelaufen, bitte versuche es nochmals.', 'feg-haiger-funktionen' );
	}

	$GLOBALS['feg_kontakt_status'] = array(
		'fehler' => $fehler,
		'werte'  => $werte,
	);
}
add_action( 'template_redirect', 'feg_kontakt_verarbeiten' );

/**
 * Formular ausgeben.
 *
 * @param array|string $atts Attribute (einleitung).
 * @return string HTML.
 */
function feg_kontakt_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'einleitung' => __( 'Gerne kannst du uns eine Nachricht senden. Wir melden uns so bald wie möglich. Bitte fülle alle Felder aus.', 'feg-haiger-funktionen' ),
		),
		$atts,
		'feg_kontaktformular'
	);

	wp_enqueue_style( 'feg-kontakt', FEG_HAIGER_URL . 'assets/kontakt.css', array(), FEG_HAIGER_VERSION );

	$status = $GLOBALS['feg_kontakt_status'];
	$werte  = $status ? $status['werte'] : array(
		'name'      => '',
		'email'     => '',
		'nachricht' => '',
	);
	$id     = wp_unique_id( 'feg-kontakt-' );

	$datenschutz = get_privacy_policy_url();
	if ( ! $datenschutz ) {
		$seite       = get_page_by_path( 'home/datenschutzerklaerung' );
		$datenschutz = $seite ? get_permalink( $seite ) : '';
	}

	ob_start();
	?>
	<div class="feg-kontakt" id="feg-kontakt">
		<?php if ( isset( $_GET['kontakt'] ) && 'gesendet' === $_GET['kontakt'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<p class="feg-kontakt__meldung feg-kontakt__meldung--ok" role="status"><?php esc_html_e( 'Vielen Dank für deine Nachricht, wir werden uns umgehend bei dir melden.', 'feg-haiger-funktionen' ); ?></p>
		<?php endif; ?>

		<?php if ( $status && $status['fehler'] ) : ?>
			<div class="feg-kontakt__meldung feg-kontakt__meldung--fehler" role="alert">
				<ul>
					<?php foreach ( $status['fehler'] as $meldung ) : ?>
						<li><?php echo esc_html( $meldung ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form method="post" action="#feg-kontakt" novalidate>
			<?php if ( '' !== $atts['einleitung'] ) : ?>
				<p class="feg-kontakt__einleitung"><?php echo esc_html( $atts['einleitung'] ); ?></p>
			<?php endif; ?>

			<p>
				<label for="<?php echo esc_attr( $id ); ?>-name"><?php esc_html_e( 'Dein Name', 'feg-haiger-funktionen' ); ?> <span aria-hidden="true">*</span></label>
				<input type="text" id="<?php echo esc_attr( $id ); ?>-name" name="feg_name" value="<?php echo esc_attr( $werte['name'] ); ?>" required autocomplete="name">
			</p>
			<p>
				<label for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'Deine E-Mail-Adresse', 'feg-haiger-funktionen' ); ?> <span aria-hidden="true">*</span></label>
				<input type="email" id="<?php echo esc_attr( $id ); ?>-email" name="feg_email" value="<?php echo esc_attr( $werte['email'] ); ?>" required autocomplete="email">
			</p>
			<p>
				<label for="<?php echo esc_attr( $id ); ?>-nachricht"><?php esc_html_e( 'Deine Nachricht', 'feg-haiger-funktionen' ); ?> <span aria-hidden="true">*</span></label>
				<textarea id="<?php echo esc_attr( $id ); ?>-nachricht" name="feg_nachricht" rows="5" required><?php echo esc_textarea( $werte['nachricht'] ); ?></textarea>
			</p>
			<p class="feg-kontakt__honig" aria-hidden="true">
				<label for="<?php echo esc_attr( $id ); ?>-website">Website</label>
				<input type="text" id="<?php echo esc_attr( $id ); ?>-website" name="feg_website" value="" tabindex="-1" autocomplete="off">
			</p>
			<p class="feg-kontakt__zustimmung">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>-datenschutz" name="feg_datenschutz" value="1" required>
				<label for="<?php echo esc_attr( $id ); ?>-datenschutz">
					<?php
					if ( $datenschutz ) {
						printf(
							/* translators: %s: Link zur Datenschutzerklärung */
							esc_html__( 'Mit dem Absenden des Formulars stimme ich der %s dieser Seite sowie dem Speichern der übermittelten Daten zu.', 'feg-haiger-funktionen' ),
							'<a href="' . esc_url( $datenschutz ) . '">' . esc_html__( 'Datenschutzerklärung', 'feg-haiger-funktionen' ) . '</a>'
						);
					} else {
						esc_html_e( 'Mit dem Absenden des Formulars stimme ich der Datenschutzerklärung dieser Seite sowie dem Speichern der übermittelten Daten zu.', 'feg-haiger-funktionen' );
					}
					?>
					<span aria-hidden="true">*</span>
				</label>
			</p>
			<input type="hidden" name="feg_kontakt" value="1">
			<input type="hidden" name="feg_zeit" value="<?php echo esc_attr( (string) time() ); ?>">
			<?php wp_nonce_field( 'feg_kontakt', '_feg_kontakt_nonce', true ); ?>
			<p>
				<button type="submit" class="wp-element-button"><?php esc_html_e( 'Senden', 'feg-haiger-funktionen' ); ?></button>
			</p>
		</form>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'feg_kontaktformular', 'feg_kontakt_shortcode' );
