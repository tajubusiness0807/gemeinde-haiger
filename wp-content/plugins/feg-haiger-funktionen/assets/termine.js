/**
 * Blättern in der Terminvorschau (6 Termine je Seite, auf dem Handy 3).
 */
( function () {
	'use strict';

	document.querySelectorAll( '.feg-termine' ).forEach( function ( box ) {
		var termine = Array.prototype.slice.call( box.querySelectorAll( '.feg-termin' ) );
		var steuerung = box.querySelector( '.feg-termine__steuerung' );
		var zurueck = box.querySelector( '.feg-termine__zurueck' );
		var weiter = box.querySelector( '.feg-termine__weiter' );
		var seite = 0;
		var mobil = window.matchMedia( '(max-width: 781px)' );

		function proSeite() {
			var basis = parseInt( box.getAttribute( 'data-pro-seite' ), 10 ) || 6;
			return mobil.matches ? Math.ceil( basis / 2 ) : basis;
		}

		function zeigen() {
			var n = proSeite();
			var seiten = Math.max( 1, Math.ceil( termine.length / n ) );
			seite = Math.min( Math.max( seite, 0 ), seiten - 1 );
			termine.forEach( function ( el, i ) {
				el.hidden = i < seite * n || i >= ( seite + 1 ) * n;
			} );
			steuerung.hidden = seiten < 2;
			zurueck.disabled = seite === 0;
			weiter.disabled = seite >= seiten - 1;
		}

		zurueck.addEventListener( 'click', function () {
			seite--;
			zeigen();
		} );
		weiter.addEventListener( 'click', function () {
			seite++;
			zeigen();
		} );
		if ( mobil.addEventListener ) {
			mobil.addEventListener( 'change', zeigen );
		}
		zeigen();
	} );
}() );
