<?php
/**
 * Title: Startseite: Slideshow
 * Slug: feg-haiger/startseite-slideshow
 * Categories: feg-haiger, banner
 * Description: Wechselnde Titelbilder mit Begrüßungstexten (wie die bisherige LayerSlider-Slideshow). Weitere Folien einfach als Cover-Block in die Gruppe kopieren.
 * Keywords: slider, slideshow, titelbild, startseite
 *
 * @package feg-haiger
 */

$feg_bild = static function ( $datei ) {
	return esc_url( get_theme_file_uri( 'assets/images/slider/' . $datei ) );
};
?>
<!-- wp:group {"metadata":{"name":"Slideshow"},"align":"wide","className":"feg-slider","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide feg-slider">
	<!-- wp:cover {"url":"<?php echo $feg_bild( '01-eingang.jpg' ); // phpcs:ignore ?>","dimRatio":0,"isUserOverlayColor":true,"minHeight":580,"isDark":false,"metadata":{"name":"Folie 1 – Willkommen"},"style":{"spacing":{"padding":{"top":"36px","bottom":"40px","left":"40px","right":"32px"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-cover is-light" style="padding-top:36px;padding-right:32px;padding-bottom:40px;padding-left:40px;min-height:580px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $feg_bild( '01-eingang.jpg' ); // phpcs:ignore ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
		<!-- wp:group {"style":{"dimensions":{"minHeight":"500px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"space-between"}} -->
		<div class="wp-block-group" style="min-height:500px">
			<!-- wp:heading {"level":1,"className":"feg-slider__box","style":{"typography":{"fontSize":"2.4rem","fontWeight":"700","lineHeight":"1.05"},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"12px","right":"12px"}}},"textColor":"primary"} -->
			<h1 class="wp-block-heading feg-slider__box has-primary-color has-text-color" style="padding-top:10px;padding-right:12px;padding-bottom:10px;padding-left:12px;font-size:2.4rem;font-weight:700;line-height:1.05"><a href="<?php echo esc_url( home_url( '/gemeinde/' ) ); ?>">Herzlich<br>willkommen!</a></h1>
			<!-- /wp:heading -->

			<!-- wp:group {"className":"feg-slider__box feg-slider__box--rechts","style":{"spacing":{"padding":{"top":"12px","bottom":"16px","left":"12px","right":"12px"},"blockGap":"12px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"right"}} -->
			<div class="wp-block-group feg-slider__box feg-slider__box--rechts" style="padding-top:12px;padding-right:12px;padding-bottom:16px;padding-left:12px">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25rem","lineHeight":"1.3"}},"textColor":"kontrast"} -->
				<p class="has-kontrast-color has-text-color" style="font-size:1.25rem;line-height:1.3">Du möchtest gerne mehr<br>über die FeG Haiger erfahren?<br><br>Dann bist du hier richtig.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"style":{"border":{"radius":"7px"},"typography":{"fontSize":"1.1rem"}}} -->
					<div class="wp-block-button"><a class="wp-block-button__link has-custom-font-size wp-element-button" href="<?php echo esc_url( home_url( '/gemeinde/' ) ); ?>" style="border-radius:7px;font-size:1.1rem">Neu hier?</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div></div>
	<!-- /wp:cover -->

	<!-- wp:cover {"url":"<?php echo $feg_bild( '02-kreuz.jpg' ); // phpcs:ignore ?>","dimRatio":0,"isUserOverlayColor":true,"minHeight":580,"contentPosition":"bottom right","isDark":false,"metadata":{"name":"Folie 2 – Gottesdienst"},"style":{"spacing":{"padding":{"top":"40px","bottom":"60px","left":"40px","right":"60px"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-right" style="padding-top:40px;padding-right:60px;padding-bottom:60px;padding-left:40px;min-height:580px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $feg_bild( '02-kreuz.jpg' ); // phpcs:ignore ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
		<!-- wp:group {"className":"feg-slider__box","style":{"spacing":{"padding":{"top":"12px","bottom":"16px","left":"16px","right":"16px"},"blockGap":"12px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group feg-slider__box" style="padding-top:12px;padding-right:16px;padding-bottom:16px;padding-left:16px">
			<!-- wp:heading {"style":{"typography":{"fontSize":"2.2rem","fontWeight":"700","lineHeight":"1.15"}},"textColor":"primary"} -->
			<h2 class="wp-block-heading has-primary-color has-text-color" style="font-size:2.2rem;font-weight:700;line-height:1.15"><a href="<?php echo esc_url( home_url( '/angebote/gottesdienst/' ) ); ?>">Sonntags – 10 Uhr?<br>Gottesdienst …</a></h2>
			<!-- /wp:heading -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"style":{"border":{"radius":"7px"},"typography":{"fontSize":"1.4rem"}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-custom-font-size wp-element-button" href="<?php echo esc_url( home_url( '/angebote/gottesdienst/' ) ); ?>" style="border-radius:7px;font-size:1.4rem">Herzliche Einladung!</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div></div>
	<!-- /wp:cover -->

	<!-- wp:cover {"url":"<?php echo $feg_bild( '03-angebote.jpg' ); // phpcs:ignore ?>","dimRatio":0,"isUserOverlayColor":true,"minHeight":580,"contentPosition":"bottom right","isDark":false,"metadata":{"name":"Folie 3 – Angebote"},"style":{"spacing":{"padding":{"top":"40px","bottom":"60px","left":"40px","right":"60px"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-right" style="padding-top:40px;padding-right:60px;padding-bottom:60px;padding-left:40px;min-height:580px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $feg_bild( '03-angebote.jpg' ); // phpcs:ignore ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
		<!-- wp:group {"className":"feg-slider__box","style":{"spacing":{"padding":{"top":"12px","bottom":"12px","left":"16px","right":"16px"}}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group feg-slider__box" style="padding-top:12px;padding-right:16px;padding-bottom:12px;padding-left:16px">
			<!-- wp:heading {"style":{"typography":{"fontSize":"2.2rem","fontWeight":"500","lineHeight":"1.15"}},"textColor":"kontrast"} -->
			<h2 class="wp-block-heading has-kontrast-color has-text-color" style="font-size:2.2rem;font-weight:500;line-height:1.15"><a href="<?php echo esc_url( home_url( '/angebote/' ) ); ?>">Angebote<br>und mehr …</a></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->
	</div></div>
	<!-- /wp:cover -->
</div>
<!-- /wp:group -->
