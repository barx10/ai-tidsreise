<?php
/**
 * View: Dashboard-widget for AI Tidsreise.
 *
 * Forventer $counts fra AI_Tidsreise_Dashboard_Widget::render_widget().
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<ul class="ai-tidsreise-dashboard-widget">
	<li>
		<?php
		printf(
			/* translators: %d: antall innlegg. */
			esc_html__( 'Ikke generert: %d', 'ai-tidsreise' ),
			(int) $counts[ AI_Tidsreise_Post_Meta::STATUS_IKKE_GENERERT ]
		);
		?>
	</li>
	<li>
		<?php
		printf(
			/* translators: %d: antall innlegg. */
			esc_html__( 'Generert, ikke publisert: %d', 'ai-tidsreise' ),
			(int) $counts[ AI_Tidsreise_Post_Meta::STATUS_GENERERT ]
		);
		?>
	</li>
	<li>
		<?php
		printf(
			/* translators: %d: antall innlegg. */
			esc_html__( 'Publisert: %d', 'ai-tidsreise' ),
			(int) $counts[ AI_Tidsreise_Post_Meta::STATUS_PUBLISERT ]
		);
		?>
	</li>
</ul>
<p>
	<a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
		<?php esc_html_e( 'Gå til innleggslisten for å bulk-generere', 'ai-tidsreise' ); ?>
	</a>
</p>
