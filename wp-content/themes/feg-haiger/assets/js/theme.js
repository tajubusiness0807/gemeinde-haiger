/**
 * FeG Haiger – kleine Frontend-Helfer: Slideshow und „Nach oben“-Button.
 */
( function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function initSlider( slider ) {
		var slides = Array.prototype.filter.call( slider.children, function ( el ) {
			return el.classList.contains( 'wp-block-cover' );
		} );
		if ( slides.length < 2 ) {
			return;
		}

		var current = 0;
		var timer = null;
		var interval = parseInt( slider.getAttribute( 'data-interval' ), 10 ) || 7000;
		var nav = document.createElement( 'div' );
		nav.className = 'feg-slider__nav';

		var dots = slides.map( function ( slide, i ) {
			var dot = document.createElement( 'button' );
			dot.type = 'button';
			dot.className = 'feg-slider__dot';
			dot.setAttribute( 'aria-label', 'Bild ' + ( i + 1 ) + ' von ' + slides.length );
			dot.addEventListener( 'click', function () {
				show( i );
				restart();
			} );
			nav.appendChild( dot );
			return dot;
		} );

		function show( index ) {
			current = ( index + slides.length ) % slides.length;
			slides.forEach( function ( slide, i ) {
				var active = i === current;
				slide.classList.toggle( 'is-active', active );
				slide.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
				if ( 'inert' in slide ) {
					slide.inert = ! active;
				}
				dots[ i ].setAttribute( 'aria-current', active ? 'true' : 'false' );
			} );
		}

		function restart() {
			if ( reduceMotion ) {
				return;
			}
			window.clearInterval( timer );
			timer = window.setInterval( function () {
				show( current + 1 );
			}, interval );
		}

		slider.appendChild( nav );
		slider.classList.add( 'is-ready' );
		slider.addEventListener( 'mouseenter', function () {
			window.clearInterval( timer );
		} );
		slider.addEventListener( 'mouseleave', restart );
		slider.addEventListener( 'focusin', function () {
			window.clearInterval( timer );
		} );
		show( 0 );
		restart();
	}

	function initBackToTop() {
		var button = document.querySelector( '.feg-nach-oben' );
		if ( ! button ) {
			return;
		}
		function update() {
			button.classList.toggle( 'is-visible', window.scrollY > 300 );
		}
		window.addEventListener( 'scroll', update, { passive: true } );
		button.addEventListener( 'click', function () {
			window.scrollTo( { top: 0, behavior: reduceMotion ? 'auto' : 'smooth' } );
		} );
		update();
	}

	document.querySelectorAll( '.feg-slider' ).forEach( initSlider );
	initBackToTop();
}() );
