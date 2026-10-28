<?php
/**
 * Title: Fußbereich mit Anschrift und Links
 * Slug: feg-haiger/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package feg-haiger
 */

?>
<!-- wp:group {"className":"feg-footer","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"margin":{"top":"var:preset|spacing|50"}}},"backgroundColor":"footer","layout":{"type":"constrained","contentSize":"950px"}} -->
<div class="wp-block-group feg-footer has-footer-background-color has-background" style="margin-top:var(--wp--preset--spacing--50);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"className":"feg-footer-kontakt"} -->
		<div class="wp-block-column feg-footer-kontakt">
			<!-- wp:paragraph {"className":"feg-icon-haus"} -->
			<p class="feg-icon-haus"><a href="<?php echo esc_url( home_url( '/home/anfahrt/' ) ); ?>">Hickenweg 34<br>35708 Haiger</a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"feg-icon-mail"} -->
			<p class="feg-icon-mail"><a href="mailto:kontakt@haiger.feg.de"><?php esc_html_e( 'E-Mail-Kontakt', 'feg-haiger' ); ?></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"feg-icon-karte"} -->
			<p class="feg-icon-karte"><a href="<?php echo esc_url( home_url( '/gemeinde/finanzierung/' ) ); ?>"><?php esc_html_e( 'Bankverbindung', 'feg-haiger' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"feg-footer-links"} -->
		<div class="wp-block-column feg-footer-links">
			<!-- wp:paragraph -->
			<p><a href="<?php echo esc_url( home_url( '/home/anfahrt/' ) ); ?>"><?php esc_html_e( 'Anfahrt', 'feg-haiger' ); ?></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><a href="<?php echo esc_url( home_url( '/home/kontakt/' ) ); ?>"><?php esc_html_e( 'Kontakt', 'feg-haiger' ); ?></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><a href="https://www.skbwitten-spendenportal.de/projekt-einbettung.html?id=47" target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'Spenden', 'feg-haiger' ); ?> <img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/spenden.png' ) ); ?>" alt="PayPal / SEPA" style="height:22px;width:auto;vertical-align:middle;margin-left:6px"></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><a href="https://feghaiger.church.tools/" target="_blank" rel="noreferrer noopener">ChurchTools</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"feg-footer-links"} -->
		<div class="wp-block-column feg-footer-links">
			<!-- wp:paragraph -->
			<p><a href="<?php echo esc_url( home_url( '/home/suche/' ) ); ?>"><?php esc_html_e( 'Suche', 'feg-haiger' ); ?></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><a href="<?php echo esc_url( home_url( '/home/datenschutzerklaerung/' ) ); ?>"><?php esc_html_e( 'Datenschutzerklärung', 'feg-haiger' ); ?></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><a href="<?php echo esc_url( home_url( '/home/impressum/' ) ); ?>"><?php esc_html_e( 'Impressum', 'feg-haiger' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}},"textColor":"muted","fontSize":"small"} -->
	<p class="has-text-align-center has-muted-color has-text-color has-small-font-size" style="margin-top:var(--wp--preset--spacing--30)">© <?php echo esc_html( gmdate( 'Y' ) ); ?> Freie evangelische Gemeinde Haiger</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
