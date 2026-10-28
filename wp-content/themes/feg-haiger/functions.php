<?php
/**
 * FeG Haiger – Theme-Funktionen.
 *
 * @package feg-haiger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme-Grundeinstellungen.
 */
function feg_haiger_setup() {
	load_theme_textdomain( 'feg-haiger', get_template_directory() . '/languages' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'feg_haiger_setup' );

/**
 * Stylesheet und Skripte im Frontend laden.
 */
function feg_haiger_assets() {
	$version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style( 'feg-haiger', get_stylesheet_uri(), array(), $version );
	wp_enqueue_script(
		'feg-haiger',
		get_theme_file_uri( 'assets/js/theme.js' ),
		array(),
		$version,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'feg_haiger_assets' );

/**
 * Zusätzliche Blockstile.
 */
function feg_haiger_block_styles() {
	register_block_style(
		'core/button',
		array(
			'name'  => 'feg-klein',
			'label' => __( 'Klein (E-Mail)', 'feg-haiger' ),
		)
	);
	register_block_style(
		'core/image',
		array(
			'name'  => 'feg-rahmen',
			'label' => __( 'Mit Rahmen', 'feg-haiger' ),
		)
	);
	register_block_style(
		'core/post-featured-image',
		array(
			'name'  => 'feg-rahmen',
			'label' => __( 'Mit Rahmen', 'feg-haiger' ),
		)
	);
}
add_action( 'init', 'feg_haiger_block_styles' );

/**
 * Eigene Kategorie für die Vorlagen (Patterns) des Themes.
 */
function feg_haiger_pattern_categories() {
	register_block_pattern_category(
		'feg-haiger',
		array( 'label' => __( 'FeG Haiger', 'feg-haiger' ) )
	);
}
add_action( 'init', 'feg_haiger_pattern_categories' );

/**
 * Favicon des bisherigen Auftritts verwenden, solange kein Website-Icon gesetzt ist.
 */
function feg_haiger_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	printf(
		'<link rel="icon" type="image/png" sizes="32x32" href="%s" />' . "\n",
		esc_url( get_theme_file_uri( 'assets/images/favicon.png' ) )
	);
}
add_action( 'wp_head', 'feg_haiger_favicon' );

/**
 * „Nach oben“-Button im Footer ausgeben.
 */
function feg_haiger_back_to_top() {
	?>
	<button type="button" class="feg-nach-oben" aria-label="<?php esc_attr_e( 'Nach oben', 'feg-haiger' ); ?>">
		<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6l-8 9h16z"/></svg>
	</button>
	<?php
}
add_action( 'wp_footer', 'feg_haiger_back_to_top' );

/**
 * Platzhalter, falls das Plugin „FeG Haiger – Funktionen“ (noch) nicht aktiv ist:
 * verhindert, dass Shortcodes als Rohtext auf der Seite erscheinen.
 */
function feg_haiger_shortcode_platzhalter() {
	foreach ( array( 'feg_termine', 'feg_kontaktformular' ) as $tag ) {
		if ( ! shortcode_exists( $tag ) ) {
			add_shortcode( $tag, '__return_empty_string' );
		}
	}
}
add_action( 'init', 'feg_haiger_shortcode_platzhalter', 99 );
