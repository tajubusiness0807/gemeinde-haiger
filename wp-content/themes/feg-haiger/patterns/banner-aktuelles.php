<?php
/**
 * Title: Banner „Aktuelles“
 * Slug: feg-haiger/banner-aktuelles
 * Categories: feg-haiger, banner
 * Inserter: no
 *
 * @package feg-haiger
 */

$feg_url = esc_url( get_theme_file_uri( 'assets/images/banner/aktuell.jpg' ) );
?>
<!-- wp:cover {"url":"<?php echo $feg_url; // phpcs:ignore ?>","dimRatio":0,"isUserOverlayColor":true,"minHeight":200,"isDark":false,"className":"feg-banner","style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light feg-banner" style="margin-top:var(--wp--preset--spacing--20);min-height:200px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $feg_url; // phpcs:ignore ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
	<!-- wp:heading {"textAlign":"center","level":2,"textColor":"white","fontSize":"xx-large"} -->
	<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color has-xx-large-font-size">Aktuelles</h2>
	<!-- /wp:heading -->
</div></div>
<!-- /wp:cover -->
