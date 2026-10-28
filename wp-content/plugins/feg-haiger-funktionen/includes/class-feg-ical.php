<?php
/**
 * Schlanker iCalendar-Parser (RFC 5545) für die öffentlichen Google-Kalender der Gemeinde.
 *
 * Unterstützt die von Google Kalender erzeugten Wiederholungsregeln:
 * FREQ=DAILY|WEEKLY|MONTHLY|YEARLY mit INTERVAL, COUNT, UNTIL, BYDAY (auch mit Ordinalzahl
 * wie „-1TH“ = letzter Donnerstag), BYMONTHDAY und BYMONTH sowie EXDATE und
 * geänderte bzw. abgesagte Einzeltermine (RECURRENCE-ID, STATUS:CANCELLED).
 *
 * @package feg-haiger-funktionen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parser und Expander für iCalendar-Daten.
 */
class FEG_ICal {

	/**
	 * Wochentagskürzel → ISO-Wochentag (1 = Montag).
	 *
	 * @var array<string,int>
	 */
	const WOCHENTAGE = array(
		'MO' => 1,
		'TU' => 2,
		'WE' => 3,
		'TH' => 4,
		'FR' => 5,
		'SA' => 6,
		'SU' => 7,
	);

	/**
	 * Obergrenze für erzeugte Wiederholungen je Serie (Schutz vor Endlosschleifen).
	 */
	const MAX_ITERATIONEN = 5000;

	/**
	 * Standard-Zeitzone für Termine ohne Zeitzonenangabe.
	 *
	 * @var DateTimeZone
	 */
	private $zeitzone;

	/**
	 * Konstruktor.
	 *
	 * @param DateTimeZone $zeitzone Zeitzone der Website.
	 */
	public function __construct( DateTimeZone $zeitzone ) {
		$this->zeitzone = $zeitzone;
	}

	/**
	 * Liefert alle Termine aus $ics, die im Zeitraum [$von, $bis) beginnen oder noch laufen.
	 *
	 * @param string            $ics ICS-Rohdaten.
	 * @param DateTimeImmutable $von Beginn des Zeitraums.
	 * @param DateTimeImmutable $bis Ende des Zeitraums.
	 * @return array<int,array<string,mixed>> Termine, sortiert nach Beginn.
	 */
	public function termine( $ics, DateTimeImmutable $von, DateTimeImmutable $bis ) {
		$ereignisse = $this->parse( $ics );
		$serien     = array();
		$ausnahmen  = array();

		foreach ( $ereignisse as $ereignis ) {
			if ( empty( $ereignis['DTSTART'] ) ) {
				continue;
			}
			$uid = isset( $ereignis['UID'] ) ? $ereignis['UID']['value'] : uniqid( 'feg', true );
			if ( isset( $ereignis['RECURRENCE-ID'] ) ) {
				$rid = $this->datum( $ereignis['RECURRENCE-ID'] );
				if ( $rid ) {
					$ausnahmen[ $uid ][ $this->schluessel( $rid['datum'] ) ] = $ereignis;
				}
				continue;
			}
			$serien[ $uid ] = $ereignis;
		}

		$ergebnis = array();

		foreach ( $serien as $uid => $ereignis ) {
			$start = $this->datum( $ereignis['DTSTART'] );
			if ( ! $start ) {
				continue;
			}
			$dauer = $this->dauer( $ereignis, $start );

			if ( isset( $ereignis['RRULE'] ) ) {
				$exdates = $this->exdates( $ereignis );
				foreach ( $this->wiederholungen( $start['datum'], $ereignis['RRULE']['value'], $bis ) as $vorkommen ) {
					$key = $this->schluessel( $vorkommen );
					if ( isset( $exdates[ $key ] ) || isset( $ausnahmen[ $uid ][ $key ] ) ) {
						continue;
					}
					$this->hinzufuegen( $ergebnis, $ereignis, $vorkommen, $vorkommen->add( $dauer ), $start['ganztags'], $von, $bis );
				}
			} else {
				$this->hinzufuegen( $ergebnis, $ereignis, $start['datum'], $start['datum']->add( $dauer ), $start['ganztags'], $von, $bis );
			}
		}

		// Geänderte Einzeltermine einer Serie.
		foreach ( $ausnahmen as $uid => $liste ) {
			foreach ( $liste as $ereignis ) {
				$start = $this->datum( $ereignis['DTSTART'] );
				if ( ! $start ) {
					continue;
				}
				$this->hinzufuegen( $ergebnis, $ereignis, $start['datum'], $start['datum']->add( $this->dauer( $ereignis, $start ) ), $start['ganztags'], $von, $bis );
			}
		}

		usort(
			$ergebnis,
			static function ( $a, $b ) {
				return $a['start'] <=> $b['start'];
			}
		);

		return $ergebnis;
	}

	/**
	 * Zerlegt ICS-Text in VEVENT-Eigenschaften.
	 *
	 * @param string $ics ICS-Rohdaten.
	 * @return array<int,array<string,array{value:string,params:array<string,string>,all?:array}>>
	 */
	public function parse( $ics ) {
		$ics    = str_replace( array( "\r\n", "\r" ), "\n", (string) $ics );
		$ics    = preg_replace( "/\n[ \t]/", '', $ics ); // Zeilenumbrüche nach RFC 5545 entfalten.
		$zeilen = explode( "\n", $ics );

		$ereignisse = array();
		$aktuell    = null;
		$tiefe      = 0;

		foreach ( $zeilen as $zeile ) {
			if ( '' === $zeile ) {
				continue;
			}
			if ( 'BEGIN:VEVENT' === $zeile ) {
				$aktuell = array();
				$tiefe   = 0;
				continue;
			}
			if ( 'END:VEVENT' === $zeile ) {
				if ( null !== $aktuell ) {
					$ereignisse[] = $aktuell;
				}
				$aktuell = null;
				continue;
			}
			if ( null === $aktuell ) {
				continue;
			}
			// Verschachtelte Komponenten (z.B. VALARM) überspringen.
			if ( 0 === strpos( $zeile, 'BEGIN:' ) ) {
				++$tiefe;
				continue;
			}
			if ( 0 === strpos( $zeile, 'END:' ) ) {
				$tiefe = max( 0, $tiefe - 1 );
				continue;
			}
			if ( $tiefe > 0 ) {
				continue;
			}

			$eigenschaft = $this->zeile( $zeile );
			if ( ! $eigenschaft ) {
				continue;
			}
			list( $name, $params, $wert ) = $eigenschaft;

			$eintrag = array(
				'value'  => $wert,
				'params' => $params,
			);
			if ( isset( $aktuell[ $name ] ) ) {
				$aktuell[ $name ]['all'][] = $eintrag;
			} else {
				$eintrag['all']   = array( $eintrag );
				$aktuell[ $name ] = $eintrag;
			}
		}

		return $ereignisse;
	}

	/**
	 * Zerlegt eine Inhaltszeile in Name, Parameter und Wert.
	 *
	 * @param string $zeile Zeile.
	 * @return array{0:string,1:array<string,string>,2:string}|null
	 */
	private function zeile( $zeile ) {
		// Doppelpunkt außerhalb von Anführungszeichen trennt Name/Parameter vom Wert.
		$laenge  = strlen( $zeile );
		$zitat   = false;
		$trenner = -1;
		for ( $i = 0; $i < $laenge; $i++ ) {
			$c = $zeile[ $i ];
			if ( '"' === $c ) {
				$zitat = ! $zitat;
			} elseif ( ':' === $c && ! $zitat ) {
				$trenner = $i;
				break;
			}
		}
		if ( $trenner < 1 ) {
			return null;
		}

		$kopf   = substr( $zeile, 0, $trenner );
		$wert   = substr( $zeile, $trenner + 1 );
		$teile  = explode( ';', $kopf );
		$name   = strtoupper( array_shift( $teile ) );
		$params = array();
		foreach ( $teile as $teil ) {
			$pos = strpos( $teil, '=' );
			if ( false !== $pos ) {
				$params[ strtoupper( substr( $teil, 0, $pos ) ) ] = trim( substr( $teil, $pos + 1 ), '"' );
			}
		}

		return array( $name, $params, $wert );
	}

	/**
	 * Entfernt die iCalendar-Maskierung aus Textwerten.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function text( $text ) {
		return strtr(
			(string) $text,
			array(
				'\\n'  => "\n",
				'\\N'  => "\n",
				'\\,'  => ',',
				'\\;'  => ';',
				'\\\\' => '\\',
			)
		);
	}

	/**
	 * Wandelt einen DATE- bzw. DATE-TIME-Wert in ein Datum um.
	 *
	 * @param array{value:string,params:array<string,string>} $eigenschaft Eigenschaft.
	 * @param string|null                                     $wert        Optional abweichender Wert (für EXDATE-Listen).
	 * @return array{datum:DateTimeImmutable,ganztags:bool}|null
	 */
	private function datum( array $eigenschaft, $wert = null ) {
		$wert   = trim( null === $wert ? $eigenschaft['value'] : $wert );
		$params = $eigenschaft['params'];

		try {
			if ( ( isset( $params['VALUE'] ) && 'DATE' === strtoupper( $params['VALUE'] ) ) || preg_match( '/^\d{8}$/', $wert ) ) {
				$datum = DateTimeImmutable::createFromFormat( '!Ymd', substr( $wert, 0, 8 ), $this->zeitzone );
				return $datum ? array(
					'datum'    => $datum,
					'ganztags' => true,
				) : null;
			}

			if ( 'Z' === substr( $wert, -1 ) ) {
				$datum = DateTimeImmutable::createFromFormat( 'Ymd\THis\Z', $wert, new DateTimeZone( 'UTC' ) );
				$datum = $datum ? $datum->setTimezone( $this->zeitzone ) : null;
			} else {
				$zone = $this->zeitzone;
				if ( ! empty( $params['TZID'] ) ) {
					try {
						$zone = new DateTimeZone( $params['TZID'] );
					} catch ( Exception $e ) {
						$zone = $this->zeitzone;
					}
				}
				$datum = DateTimeImmutable::createFromFormat( 'Ymd\THis', $wert, $zone );
			}
		} catch ( Exception $e ) {
			return null;
		}

		return $datum ? array(
			'datum'    => $datum,
			'ganztags' => false,
		) : null;
	}

	/**
	 * Dauer eines Termins aus DTEND bzw. DURATION.
	 *
	 * @param array                                         $ereignis Ereignis.
	 * @param array{datum:DateTimeImmutable,ganztags:bool} $start    Beginn.
	 * @return DateInterval
	 */
	private function dauer( array $ereignis, array $start ) {
		if ( isset( $ereignis['DTEND'] ) ) {
			$ende = $this->datum( $ereignis['DTEND'] );
			if ( $ende && $ende['datum'] >= $start['datum'] ) {
				return $start['datum']->diff( $ende['datum'] );
			}
		}
		if ( isset( $ereignis['DURATION'] ) ) {
			try {
				return new DateInterval( ltrim( $ereignis['DURATION']['value'], '+' ) );
			} catch ( Exception $e ) {
				// Ungültige Angabe – Standarddauer verwenden.
				unset( $e );
			}
		}
		return new DateInterval( $start['ganztags'] ? 'P1D' : 'PT0S' );
	}

	/**
	 * Ausgeschlossene Termine einer Serie.
	 *
	 * @param array $ereignis Ereignis.
	 * @return array<string,bool>
	 */
	private function exdates( array $ereignis ) {
		$liste = array();
		if ( empty( $ereignis['EXDATE'] ) ) {
			return $liste;
		}
		foreach ( $ereignis['EXDATE']['all'] as $eigenschaft ) {
			foreach ( explode( ',', $eigenschaft['value'] ) as $wert ) {
				$datum = $this->datum( $eigenschaft, $wert );
				if ( $datum ) {
					$liste[ $this->schluessel( $datum['datum'] ) ] = true;
				}
			}
		}
		return $liste;
	}

	/**
	 * Vergleichsschlüssel für ein Vorkommen (UTC, sekundengenau).
	 *
	 * @param DateTimeImmutable $datum Datum.
	 * @return string
	 */
	private function schluessel( DateTimeImmutable $datum ) {
		return $datum->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Ymd\THis' );
	}

	/**
	 * Fügt ein Vorkommen dem Ergebnis hinzu, sofern es in den Zeitraum fällt.
	 *
	 * @param array             $ergebnis Ergebnisliste (Referenz).
	 * @param array             $ereignis Ereignis.
	 * @param DateTimeImmutable $start    Beginn.
	 * @param DateTimeImmutable $ende     Ende.
	 * @param bool              $ganztags Ganztägiger Termin.
	 * @param DateTimeImmutable $von      Beginn des Zeitraums.
	 * @param DateTimeImmutable $bis      Ende des Zeitraums.
	 */
	private function hinzufuegen( array &$ergebnis, array $ereignis, DateTimeImmutable $start, DateTimeImmutable $ende, $ganztags, DateTimeImmutable $von, DateTimeImmutable $bis ) {
		if ( isset( $ereignis['STATUS'] ) && 'CANCELLED' === strtoupper( $ereignis['STATUS']['value'] ) ) {
			return;
		}
		$ende_effektiv = $ende > $start ? $ende : $start->modify( '+1 second' );
		if ( $start >= $bis || $ende_effektiv <= $von ) {
			return;
		}

		$ergebnis[] = array(
			'start'        => $start,
			'ende'         => $ende,
			'ganztags'     => (bool) $ganztags,
			'titel'        => isset( $ereignis['SUMMARY'] ) ? self::text( $ereignis['SUMMARY']['value'] ) : '',
			'ort'          => isset( $ereignis['LOCATION'] ) ? self::text( $ereignis['LOCATION']['value'] ) : '',
			'beschreibung' => isset( $ereignis['DESCRIPTION'] ) ? self::text( $ereignis['DESCRIPTION']['value'] ) : '',
			'url'          => isset( $ereignis['URL'] ) ? $ereignis['URL']['value'] : '',
		);
	}

	/**
	 * Erzeugt alle Vorkommen einer Wiederholungsregel bis $bis.
	 *
	 * @param DateTimeImmutable $start Beginn der Serie.
	 * @param string            $rrule Regel, z.B. „FREQ=WEEKLY;BYDAY=MO“.
	 * @param DateTimeImmutable $bis   Ende des Zeitraums.
	 * @return DateTimeImmutable[]
	 */
	public function wiederholungen( DateTimeImmutable $start, $rrule, DateTimeImmutable $bis ) {
		$regel = array();
		foreach ( explode( ';', strtoupper( trim( $rrule ) ) ) as $teil ) {
			$pos = strpos( $teil, '=' );
			if ( false !== $pos ) {
				$regel[ substr( $teil, 0, $pos ) ] = substr( $teil, $pos + 1 );
			}
		}

		$freq     = isset( $regel['FREQ'] ) ? $regel['FREQ'] : '';
		$interval = isset( $regel['INTERVAL'] ) ? max( 1, (int) $regel['INTERVAL'] ) : 1;
		$anzahl   = isset( $regel['COUNT'] ) ? (int) $regel['COUNT'] : 0;
		$grenze   = $bis;

		if ( isset( $regel['UNTIL'] ) ) {
			$until = $this->datum(
				array(
					'value'  => $regel['UNTIL'],
					'params' => array(),
				)
			);
			if ( $until ) {
				$ende = $until['ganztags'] ? $until['datum']->setTime( 23, 59, 59 ) : $until['datum'];
				if ( $ende < $grenze ) {
					$grenze = $ende->modify( '+1 second' );
				}
			}
		}

		$byday = array();
		if ( ! empty( $regel['BYDAY'] ) ) {
			foreach ( explode( ',', $regel['BYDAY'] ) as $tag ) {
				if ( preg_match( '/^([+-]?\d{1,2})?(MO|TU|WE|TH|FR|SA|SU)$/', $tag, $m ) ) {
					$byday[] = array(
						'nr'  => '' === $m[1] ? 0 : (int) $m[1],
						'tag' => self::WOCHENTAGE[ $m[2] ],
					);
				}
			}
		}
		$bymonthday = empty( $regel['BYMONTHDAY'] ) ? array() : array_map( 'intval', explode( ',', $regel['BYMONTHDAY'] ) );
		$bymonth    = empty( $regel['BYMONTH'] ) ? array() : array_map( 'intval', explode( ',', $regel['BYMONTH'] ) );

		$h = (int) $start->format( 'G' );
		$i = (int) $start->format( 'i' );
		$s = (int) $start->format( 's' );

		$ergebnis = array();
		$gezaehlt = 0;

		for ( $periode = 0; $periode < self::MAX_ITERATIONEN; $periode++ ) {
			$kandidaten = array();

			switch ( $freq ) {
				case 'DAILY':
					$kandidaten[] = $start->modify( '+' . ( $periode * $interval ) . ' days' );
					$periodenbeginn = $kandidaten[0];
					break;

				case 'WEEKLY':
					// Woche beginnt am Montag (WKST=MO, Standard bei Google).
					$wochenbeginn   = $start->modify( '-' . ( (int) $start->format( 'N' ) - 1 ) . ' days' )->modify( '+' . ( $periode * $interval ) . ' weeks' );
					$periodenbeginn = $wochenbeginn->setTime( 0, 0 );
					$tage           = $byday ? array_column( $byday, 'tag' ) : array( (int) $start->format( 'N' ) );
					sort( $tage );
					foreach ( $tage as $tag ) {
						$kandidaten[] = $wochenbeginn->modify( '+' . ( $tag - 1 ) . ' days' )->setTime( $h, $i, $s );
					}
					break;

				case 'MONTHLY':
					$monatsbeginn   = $start->modify( 'first day of this month' )->modify( '+' . ( $periode * $interval ) . ' months' );
					$periodenbeginn = $monatsbeginn->setTime( 0, 0 );
					$kandidaten     = $this->tage_im_monat( $monatsbeginn, $byday, $bymonthday ? $bymonthday : ( $byday ? array() : array( (int) $start->format( 'j' ) ) ), $h, $i, $s );
					break;

				case 'YEARLY':
					$jahr           = (int) $start->format( 'Y' ) + $periode * $interval;
					$periodenbeginn = $start->setDate( $jahr, 1, 1 )->setTime( 0, 0 );
					$monate         = $bymonth ? $bymonth : array( (int) $start->format( 'n' ) );
					foreach ( $monate as $monat ) {
						$monatsbeginn = $start->setDate( $jahr, $monat, 1 );
						$tage         = $bymonthday ? $bymonthday : ( $byday ? array() : array( (int) $start->format( 'j' ) ) );
						$kandidaten   = array_merge( $kandidaten, $this->tage_im_monat( $monatsbeginn, $byday, $tage, $h, $i, $s ) );
					}
					break;

				default:
					return array( $start );
			}

			if ( $periodenbeginn >= $grenze ) {
				break;
			}

			usort(
				$kandidaten,
				static function ( $a, $b ) {
					return $a <=> $b;
				}
			);

			foreach ( $kandidaten as $kandidat ) {
				if ( $kandidat < $start ) {
					continue;
				}
				if ( $kandidat >= $grenze ) {
					break 2;
				}
				$ergebnis[] = $kandidat;
				++$gezaehlt;
				if ( $anzahl && $gezaehlt >= $anzahl ) {
					break 2;
				}
			}
		}

		return $ergebnis;
	}

	/**
	 * Tage eines Monats nach BYDAY (mit/ohne Ordinalzahl) bzw. BYMONTHDAY.
	 *
	 * @param DateTimeImmutable $monat     Erster Tag des Monats.
	 * @param array             $byday     Wochentage.
	 * @param int[]             $monatstag Monatstage.
	 * @param int               $h         Stunde.
	 * @param int               $i         Minute.
	 * @param int               $s         Sekunde.
	 * @return DateTimeImmutable[]
	 */
	private function tage_im_monat( DateTimeImmutable $monat, array $byday, array $monatstag, $h, $i, $s ) {
		$jahr       = (int) $monat->format( 'Y' );
		$nr         = (int) $monat->format( 'n' );
		$tage_monat = (int) $monat->format( 't' );
		$ergebnis   = array();

		foreach ( $monatstag as $tag ) {
			$tag = $tag < 0 ? $tage_monat + $tag + 1 : $tag;
			if ( $tag >= 1 && $tag <= $tage_monat ) {
				$ergebnis[] = $monat->setDate( $jahr, $nr, $tag )->setTime( $h, $i, $s );
			}
		}

		foreach ( $byday as $eintrag ) {
			$treffer = array();
			for ( $tag = 1; $tag <= $tage_monat; $tag++ ) {
				$datum = $monat->setDate( $jahr, $nr, $tag );
				if ( (int) $datum->format( 'N' ) === $eintrag['tag'] ) {
					$treffer[] = $datum->setTime( $h, $i, $s );
				}
			}
			if ( 0 === $eintrag['nr'] ) {
				$ergebnis = array_merge( $ergebnis, $treffer );
			} else {
				$index = $eintrag['nr'] > 0 ? $eintrag['nr'] - 1 : count( $treffer ) + $eintrag['nr'];
				if ( isset( $treffer[ $index ] ) ) {
					$ergebnis[] = $treffer[ $index ];
				}
			}
		}

		return $ergebnis;
	}
}
