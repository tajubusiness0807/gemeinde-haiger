<?php
/**
 * Title: Kopfbereich mit Logo und Hauptmenü
 * Slug: feg-haiger/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * @package feg-haiger
 */

$feg_link = static function ( $label, $path ) {
	return sprintf(
		'<!-- wp:navigation-link {"label":"%1$s","url":"%2$s","kind":"custom","isTopLevelLink":true} /-->',
		esc_attr( $label ),
		esc_url( home_url( $path ) )
	);
};

$feg_sub = static function ( $label, $path, array $children ) {
	return sprintf(
		'<!-- wp:navigation-submenu {"label":"%1$s","url":"%2$s","kind":"custom"} -->%3$s<!-- /wp:navigation-submenu -->',
		esc_attr( $label ),
		esc_url( home_url( $path ) ),
		implode( '', $children )
	);
};
?>
<!-- wp:group {"className":"feg-header","style":{"spacing":{"padding":{"top":"8px","bottom":"8px"}}},"backgroundColor":"white","layout":{"type":"constrained","contentSize":"950px"}} -->
<div class="wp-block-group feg-header has-white-background-color has-background" style="padding-top:8px;padding-bottom:8px">
	<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group">
		<?php if ( has_custom_logo() ) : ?>
		<!-- wp:site-logo {"width":160} /-->
		<?php else : ?>
		<!-- wp:image {"width":"160px","sizeSlug":"full","linkDestination":"custom","className":"feg-logo"} -->
		<figure class="wp-block-image size-full is-resized feg-logo"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.jpg' ) ); ?>" alt="<?php esc_attr_e( 'FeG Haiger – Freie evangelische Gemeinde – zur Startseite', 'feg-haiger' ); ?>" style="width:160px"/></a></figure>
		<!-- /wp:image -->
		<?php endif; ?>

		<!-- wp:navigation {"overlayMenu":"mobile","overlayBackgroundColor":"white","overlayTextColor":"kontrast","layout":{"type":"flex","justifyContent":"right"},"style":{"spacing":{"blockGap":"28px"}}} -->
		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Blockmarkup, Werte werden in den Closures escaped.
		echo $feg_sub(
			__( 'Start', 'feg-haiger' ),
			'/',
			array(
				$feg_link( __( 'Kontakt', 'feg-haiger' ), '/home/kontakt/' ),
				$feg_link( __( 'Anfahrt', 'feg-haiger' ), '/home/anfahrt/' ),
				$feg_link( __( 'Gemeindezentrum', 'feg-haiger' ), '/home/gemeindezentrum/' ),
				$feg_link( __( 'Glaskunst im Gemeindezentrum', 'feg-haiger' ), '/home/glaskunst-im-gemeindezentrum/' ),
				$feg_link( __( 'Suche', 'feg-haiger' ), '/home/suche/' ),
				$feg_link( __( 'Impressum', 'feg-haiger' ), '/home/impressum/' ),
			)
		);
		echo $feg_sub(
			__( 'Aktuell', 'feg-haiger' ),
			'/aktuelles/',
			array(
				$feg_link( __( 'Terminübersicht', 'feg-haiger' ), '/aktuelles/veranstaltungen/' ),
				$feg_link( __( 'Predigten', 'feg-haiger' ), '/medien/' ),
			)
		);
		echo $feg_sub(
			__( 'Angebote', 'feg-haiger' ),
			'/angebote/',
			array(
				$feg_sub(
					__( 'Gottesdienst', 'feg-haiger' ),
					'/angebote/gottesdienst/',
					array(
						$feg_link( __( 'Unser Sonntagmorgen', 'feg-haiger' ), '/angebote/gottesdienst/unser-sonntagmorgen/' ),
						$feg_link( __( 'Kindergottesdienst', 'feg-haiger' ), '/angebote/gottesdienst/kindergottesdienst/' ),
						$feg_link( __( 'Kleinkindbetreuung', 'feg-haiger' ), '/angebote/gottesdienst/kleinkindbetreuung/' ),
					)
				),
				$feg_sub(
					__( 'Kinder', 'feg-haiger' ),
					'/angebote/kinder/',
					array(
						$feg_link( __( 'Krabbelmäuse', 'feg-haiger' ), '/angebote/kinder/krabbelmaeuse/' ),
						$feg_link( __( 'Spielekiste', 'feg-haiger' ), '/angebote/kinder/spielekiste/' ),
						$feg_link( __( 'Kindergottesdienst', 'feg-haiger' ), '/angebote/gottesdienst/kindergottesdienst/' ),
						$feg_link( __( 'Jungschar', 'feg-haiger' ), '/angebote/kinder/jungschar/' ),
						$feg_link( __( 'Zeltlager', 'feg-haiger' ), '/angebote/kinder/zeltlager/' ),
					)
				),
				$feg_link( __( 'Biblischer Unterricht', 'feg-haiger' ), '/angebote/biblischer-unterricht/' ),
				$feg_link( __( 'Jugend', 'feg-haiger' ), '/angebote/jugend/' ),
				$feg_link( __( 'Kreis junger Erwachsener', 'feg-haiger' ), '/angebote/kreis-junger-erwachsener/' ),
				$feg_link( __( 'Kleingruppen für Erwachsene', 'feg-haiger' ), '/angebote/hauskreise/' ),
				$feg_link( __( 'Treffpunkt Bibel', 'feg-haiger' ), '/angebote/treffpunkt-bibel/' ),
				$feg_link( __( 'Senioren', 'feg-haiger' ), '/angebote/seniorenkreis/' ),
				$feg_link( __( 'Gebetstreffen', 'feg-haiger' ), '/angebote/gebetstreffen/' ),
			)
		);
		echo $feg_sub(
			__( 'Gemeinde', 'feg-haiger' ),
			'/gemeinde/',
			array(
				$feg_link( __( 'Was heißt FeG?', 'feg-haiger' ), '/gemeinde/was-heisst-feg/' ),
				$feg_link( __( 'Unsere Vision', 'feg-haiger' ), '/gemeinde/unsere-vision/' ),
				$feg_link( __( 'Unsere Struktur', 'feg-haiger' ), '/gemeinde/struktur/' ),
				$feg_link( __( 'Pastor und Leitung', 'feg-haiger' ), '/gemeinde/pastor-und-leitung/' ),
				$feg_link( __( 'Kairos-Projekt', 'feg-haiger' ), '/gemeinde/kairos-projekt/' ),
				$feg_link( __( 'Finanzierung', 'feg-haiger' ), '/gemeinde/finanzierung/' ),
				$feg_link( __( 'Vernetzung', 'feg-haiger' ), '/gemeinde/vernetzung/' ),
			)
		);
		echo $feg_link( __( 'Predigt', 'feg-haiger' ), '/medien/' );
		// phpcs:enable
		?>
		<!-- /wp:navigation -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
