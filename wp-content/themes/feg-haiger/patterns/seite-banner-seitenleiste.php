<?php
/**
 * Title: Seite mit Banner und Seitenleiste
 * Slug: feg-haiger/seite-banner-seitenleiste
 * Categories: feg-haiger, featured
 * Description: Aufbau wie die bisherigen Gruppenseiten: Bereichsbanner, Text links, graue Infobox rechts (WANN / WO / FÜR WEN / INFOS).
 * Keywords: seite, banner, seitenleiste, infobox, gruppe
 * Block Types: core/post-content
 * Post Types: page
 *
 * @package feg-haiger
 */

$feg_url = esc_url( get_theme_file_uri( 'assets/images/banner/kontakt2.jpg' ) );
?>
<!-- wp:cover {"url":"<?php echo $feg_url; // phpcs:ignore ?>","dimRatio":0,"isUserOverlayColor":true,"minHeight":200,"isDark":false,"className":"feg-banner","style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light feg-banner" style="margin-top:var(--wp--preset--spacing--20);min-height:200px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $feg_url; // phpcs:ignore ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","textColor":"white","fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color has-xx-large-font-size">Bereich</h2>
<!-- /wp:heading -->
</div></div>
<!-- /wp:cover -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"},"blockGap":{"left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--40)">
<!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%">
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Seitentitel</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Hier steht der Text der Seite.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%","className":"feg-sidebar","backgroundColor":"light","style":{"spacing":{"padding":{"top":"14px","bottom":"14px","left":"14px","right":"14px"}}}} -->
<div class="wp-block-column feg-sidebar has-light-background-color has-background" style="padding-top:14px;padding-right:14px;padding-bottom:14px;padding-left:14px;flex-basis:33.33%">
<!-- wp:pattern {"slug":"feg-haiger/infobox"} /-->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
