<?php
/**
 * Title: Beitragsliste (Veranstaltungen / Aktuelles)
 * Slug: feg-haiger/beitragsliste
 * Categories: feg-haiger, query
 * Description: Liste der Beiträge mit Bild links, Auszug und „Weiterlesen“-Link.
 *
 * @package feg-haiger
 */

?>
<!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true}} -->
<div class="wp-block-query">
	<!-- wp:post-template -->
		<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|30"},"margin":{"bottom":"var:preset|spacing|30"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;margin-bottom:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
			<!-- wp:post-title {"isLink":true} /-->

			<!-- wp:columns -->
			<div class="wp-block-columns">
				<!-- wp:column {"width":"45%"} -->
				<div class="wp-block-column" style="flex-basis:45%">
					<!-- wp:post-featured-image {"isLink":true,"className":"is-style-feg-rahmen"} /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"55%"} -->
				<div class="wp-block-column" style="flex-basis:55%">
					<!-- wp:post-excerpt {"moreText":"Weiterlesen …","excerptLength":45} /-->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->

	<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
		<!-- wp:query-pagination-previous {"label":"Neuere"} /-->
		<!-- wp:query-pagination-numbers /-->
		<!-- wp:query-pagination-next {"label":"Ältere"} /-->
	<!-- /wp:query-pagination -->

	<!-- wp:query-no-results -->
		<!-- wp:paragraph -->
		<p>Zurzeit sind keine besonderen Veranstaltungen angekündigt.</p>
		<!-- /wp:paragraph -->
	<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
