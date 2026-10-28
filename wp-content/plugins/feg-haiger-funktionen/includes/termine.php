<?php
/**
 * Terminvorschau aus den öffentlichen Google-Kalendern: [feg_termine].
 *
 * @package feg-haiger-funktionen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Voreingestellte Kalender (öffentliche Google-Kalender der FeG Haiger).
 *
 * @return string Eine Zeile je Kalender: „Name | Farbe | ICS-Adresse“.
 */
function feg_termine_standard_kalender() {
	$basis = 'https://calendar.google.com/calendar/ical/%s/public/basic.ics';
	$liste = array(
		array( 'Gottesdienste', '#4986e7', 'au69ful198d5rme2av625drou0%40group.calendar.google.com' ),
		array( 'Besondere Termine', '#f6851f', 'haiger.feg%40gmail.com' ),
		array( 'Kinder & Jugend', '#42d692', 'css99qedihe4p3un3httdnh0t0%40group.calendar.google.com' ),
		array( 'Senioren', '#9a9cff', 'gghe0vicftsaivtsrut7s4pc94%40group.calendar.google.com' ),
		array( 'Treffpunkt Bibel', '#cd74e6', '61sloonf5a2otfk5ekmlnp106o%40group.calendar.google.com' ),
	);
	$zeilen = array();
	foreach ( $liste as $k ) {
		$zeilen[] = $k[0] . ' | ' . $k[1] . ' | ' . sprintf( $basis, $k[2] );
	}
	return implode( "\n", $zeilen );
}

/**
 * Konfigurierte Kalender als Liste.
 *
 * @return array<int,array{name:string,farbe:string,url:string}>
 */
function feg_termine_kalender() {
	$text   = get_option( 'feg_termine_kalender', feg_termine_standard_kalender() );
	$result = array();
	foreach ( preg_split( '/\R/', (string) $text ) as $zeile ) {
		$teile = array_map( 'trim', explode( '|', $zeile ) );
		if ( count( $teile ) < 3 || ! wp_http_validate_url( $teile[2] ) ) {
			continue;
		}
		$farbe    = sanitize_hex_color( $teile[1] );
		$result[] = array(
			'name'  => $teile[0],
			'farbe' => $farbe ? $farbe : '#0063ae',
			'url'   => $teile[2],
		);
	}
	return $result;
}

/**
 * Lädt einen ICS-Kalender (mit Zwischenspeicher).
 *
 * @param string $url ICS-Adresse.
 * @return string ICS-Daten oder leerer String.
 */
function feg_termine_ics_laden( $url ) {
	$schluessel = 'feg_ics_' . md5( $url );
	$daten      = get_transient( $schluessel );
	if ( false !== $daten ) {
		return $daten;
	}

	$antwort = wp_safe_remote_get(
		$url,
		array(
			'timeout'    => 15,
			'user-agent' => 'FeG-Haiger-WordPress/' . FEG_HAIGER_VERSION,
		)
	);

	if ( is_wp_error( $antwort ) || 200 !== (int) wp_remote_retrieve_response_code( $antwort ) ) {
		// Letzten erfolgreichen Stand verwenden und erst in 10 Minuten erneut versuchen.
		$alt = get_option( $schluessel . '_backup', '' );
		set_transient( $schluessel, $alt, 10 * MINUTE_IN_SECONDS );
		return $alt;
	}

	$daten = (string) wp_remote_retrieve_body( $antwort );
	if ( false === strpos( $daten, 'BEGIN:VCALENDAR' ) ) {
		$daten = '';
	}
	set_transient( $schluessel, $daten, HOUR_IN_SECONDS );
	update_option( $schluessel . '_backup', $daten, false );

	return $daten;
}

/**
 * Kommende Termine aller Kalender.
 *
 * @param int $tage Zeitraum in Tagen.
 * @return array<int,array<string,mixed>>
 */
function feg_termine_laden( $tage ) {
	$zeitzone = wp_timezone();
	$parser   = new FEG_ICal( $zeitzone );
	$jetzt    = new DateTimeImmutable( 'now', $zeitzone );
	$von      = $jetzt->setTime( 0, 0 );
	$bis      = $von->modify( '+' . max( 1, (int) $tage ) . ' days' );
	$termine  = array();

	foreach ( feg_termine_kalender() as $kalender ) {
		$ics = feg_termine_ics_laden( $kalender['url'] );
		if ( '' === $ics ) {
			continue;
		}
		foreach ( $parser->termine( $ics, $von, $bis ) as $termin ) {
			// Bereits beendete Termine des heutigen Tages ausblenden.
			if ( ! $termin['ganztags'] && $termin['ende'] < $jetzt && $termin['ende'] > $termin['start'] ) {
				continue;
			}
			$termin['kalender'] = $kalender['name'];
			$termin['farbe']    = $kalender['farbe'];
			$termine[]          = $termin;
		}
	}

	usort(
		$termine,
		static function ( $a, $b ) {
			return $a['start'] <=> $b['start'];
		}
	);

	return $termine;
}

/**
 * Shortcode [feg_termine anzahl="12" tage="90" ansicht="kacheln|liste" kalender="Name, Name"].
 *
 * @param array|string $atts Attribute.
 * @return string HTML.
 */
function feg_termine_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'anzahl'   => 12,
			'tage'     => (int) get_option( 'feg_termine_tage', 90 ),
			'ansicht'  => 'kacheln',
			'kalender' => '',
		),
		$atts,
		'feg_termine'
	);

	$termine = feg_termine_laden( (int) $atts['tage'] );

	if ( '' !== trim( $atts['kalender'] ) ) {
		$filter  = array_map( 'trim', explode( ',', strtolower( $atts['kalender'] ) ) );
		$termine = array_values(
			array_filter(
				$termine,
				static function ( $t ) use ( $filter ) {
					return in_array( strtolower( $t['kalender'] ), $filter, true );
				}
			)
		);
	}

	$termine = array_slice( $termine, 0, max( 1, (int) $atts['anzahl'] ) );

	wp_enqueue_style( 'feg-termine' );

	if ( ! $termine ) {
		return '<p class="feg-termine-leer">' . esc_html__( 'Zurzeit sind keine Termine eingetragen.', 'feg-haiger-funktionen' ) . '</p>';
	}

	if ( 'liste' === $atts['ansicht'] ) {
		return feg_termine_liste( $termine );
	}

	wp_enqueue_script( 'feg-termine' );

	ob_start();
	?>
	<div class="feg-termine" data-pro-seite="6">
		<div class="feg-termine__steuerung" hidden>
			<button type="button" class="feg-termine__zurueck" aria-label="<?php esc_attr_e( 'Vorherige Termine', 'feg-haiger-funktionen' ); ?>">‹</button>
			<button type="button" class="feg-termine__weiter" aria-label="<?php esc_attr_e( 'Nächste Termine', 'feg-haiger-funktionen' ); ?>">›</button>
		</div>
		<ul class="feg-termine__liste">
			<?php foreach ( $termine as $termin ) : ?>
				<li class="feg-termin" style="--feg-termin-farbe: <?php echo esc_attr( $termin['farbe'] ); ?>">
					<span class="feg-termin__kalender"><?php echo esc_html( $termin['kalender'] ); ?></span>
					<span class="feg-termin__monat"><?php echo esc_html( wp_date( 'M', $termin['start']->getTimestamp() ) ); ?></span>
					<span class="feg-termin__tag"><?php echo esc_html( $termin['start']->format( 'd' ) ); ?></span>
					<strong class="feg-termin__titel"><?php echo esc_html( $termin['titel'] ); ?></strong>
					<span class="feg-termin__zeit"><?php echo esc_html( feg_termine_zeit( $termin ) ); ?></span>
					<?php if ( '' !== $termin['ort'] ) : ?>
						<span class="feg-termin__ort"><?php echo esc_html( $termin['ort'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Listenansicht, nach Tagen gruppiert.
 *
 * @param array $termine Termine.
 * @return string HTML.
 */
function feg_termine_liste( array $termine ) {
	$html = '<div class="feg-termine-liste">';
	$tag  = '';
	foreach ( $termine as $termin ) {
		$neu = $termin['start']->format( 'Y-m-d' );
		if ( $neu !== $tag ) {
			if ( '' !== $tag ) {
				$html .= '</ul>';
			}
			$html .= '<h3 class="feg-termine-liste__tag">' . esc_html( wp_date( 'l, j. F Y', $termin['start']->getTimestamp() ) ) . '</h3><ul>';
			$tag   = $neu;
		}
		$html .= sprintf(
			'<li style="--feg-termin-farbe:%1$s"><span class="feg-termine-liste__zeit">%2$s</span> <strong>%3$s</strong> <span class="feg-termine-liste__kalender">%4$s</span>%5$s</li>',
			esc_attr( $termin['farbe'] ),
			esc_html( feg_termine_zeit( $termin ) ),
			esc_html( $termin['titel'] ),
			esc_html( $termin['kalender'] ),
			'' !== $termin['ort'] ? '<br><small>' . esc_html( $termin['ort'] ) . '</small>' : ''
		);
	}
	return $html . '</ul></div>';
}

/**
 * Zeitangabe eines Termins.
 *
 * @param array $termin Termin.
 * @return string
 */
function feg_termine_zeit( array $termin ) {
	if ( $termin['ganztags'] ) {
		$letzter = $termin['ende']->modify( '-1 day' );
		if ( $letzter->format( 'Ymd' ) > $termin['start']->format( 'Ymd' ) ) {
			/* translators: %s: Enddatum */
			return sprintf( __( 'ganztägig bis %s', 'feg-haiger-funktionen' ), wp_date( 'j. M', $letzter->getTimestamp() ) );
		}
		return __( 'ganztägig', 'feg-haiger-funktionen' );
	}
	$text = $termin['start']->format( 'H:i' );
	if ( $termin['ende'] > $termin['start'] ) {
		$text .= ' – ' . $termin['ende']->format( 'H:i' );
	}
	return $text;
}

/**
 * Assets registrieren.
 */
function feg_termine_assets() {
	wp_register_style( 'feg-termine', FEG_HAIGER_URL . 'assets/termine.css', array(), FEG_HAIGER_VERSION );
	wp_register_script(
		'feg-termine',
		FEG_HAIGER_URL . 'assets/termine.js',
		array(),
		FEG_HAIGER_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'init', 'feg_termine_assets' );
add_shortcode( 'feg_termine', 'feg_termine_shortcode' );

/**
 * Shortcode-Blöcke auch in Theme-Vorlagen und Patterns ausführen (dort läuft kein „the_content“-Filter).
 *
 * @param string $html Block-HTML.
 * @return string
 */
function feg_shortcode_block_ausfuehren( $html ) {
	return do_shortcode( $html );
}
add_filter( 'render_block_core/shortcode', 'feg_shortcode_block_ausfuehren' );
