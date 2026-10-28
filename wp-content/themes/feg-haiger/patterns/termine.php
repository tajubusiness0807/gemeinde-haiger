<?php
/**
 * Title: Veranstaltungshinweise (Terminvorschau)
 * Slug: feg-haiger/termine
 * Categories: feg-haiger
 * Description: Die nächsten Termine aus den Google-Kalendern der Gemeinde (benötigt das Plugin „FeG Haiger – Funktionen“).
 * Keywords: termine, kalender, veranstaltungen
 *
 * @package feg-haiger
 */

?>
<!-- wp:group {"metadata":{"name":"Veranstaltungshinweise"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--50)">
	<!-- wp:heading -->
	<h2 class="wp-block-heading">Veranstaltungshinweise</h2>
	<!-- /wp:heading -->

	<!-- wp:shortcode -->
	[feg_termine anzahl="12"]
	<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
