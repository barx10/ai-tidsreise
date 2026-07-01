<?php
/**
 * View: Meta-boks for AI Tidsreise.
 *
 * Forventer $post, $refleksjon, $status, $synlig og $naeste_id fra AI_Tidsreise_Metabox::render_metabox().
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_labels = array(
	AI_Tidsreise_Post_Meta::STATUS_IKKE_GENERERT => __( 'Ikke generert', 'ai-tidsreise' ),
	AI_Tidsreise_Post_Meta::STATUS_GENERERT      => __( 'Generert', 'ai-tidsreise' ),
	AI_Tidsreise_Post_Meta::STATUS_PUBLISERT     => __( 'Publisert', 'ai-tidsreise' ),
);

?>
<div class="ai-tidsreise-metabox" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>">
	<p class="ai-tidsreise-status">
		<strong><?php esc_html_e( 'Status:', 'ai-tidsreise' ); ?></strong>
		<span class="ai-tidsreise-status-label" id="ai-tidsreise-status-label">
			<?php echo esc_html( $status_labels[ $status ] ?? $status_labels[ AI_Tidsreise_Post_Meta::STATUS_IKKE_GENERERT ] ); ?>
		</span>
	</p>

	<p>
		<button type="button" class="button button-primary" id="ai-tidsreise-generate-button">
			<?php esc_html_e( 'Generer refleksjon (2026)', 'ai-tidsreise' ); ?>
		</button>
		<span class="spinner" id="ai-tidsreise-spinner"></span>
	</p>

	<p class="description">
		<?php esc_html_e( 'Dette er private notater til deg selv. Ingenting vises for leserne med mindre du aktivt velger det under.', 'ai-tidsreise' ); ?>
	</p>

	<div id="ai-tidsreise-feedback" class="ai-tidsreise-feedback" role="status" aria-live="polite"></div>

	<p>
		<label for="ai_tidsreise_refleksjon">
			<strong><?php esc_html_e( 'Generert refleksjon (kan redigeres før lagring)', 'ai-tidsreise' ); ?></strong>
		</label>
	</p>
	<?php
	wp_editor(
		$refleksjon,
		'ai_tidsreise_refleksjon',
		array(
			'textarea_name' => 'ai_tidsreise_refleksjon',
			'textarea_rows' => 10,
			'media_buttons' => false,
			'teeny'         => true,
		)
	);
	?>

	<p>
		<label for="ai_tidsreise_naeste_id">
			<strong><?php esc_html_e( 'Idé til neste innlegg (kan redigeres før lagring)', 'ai-tidsreise' ); ?></strong>
		</label>
	</p>
	<textarea
		id="ai_tidsreise_naeste_id"
		name="ai_tidsreise_naeste_id"
		class="widefat"
		rows="4"
	><?php echo esc_textarea( $naeste_id ); ?></textarea>

	<p class="ai-tidsreise-synlig-toggle">
		<label for="ai_tidsreise_synlig">
			<input
				type="checkbox"
				id="ai_tidsreise_synlig"
				name="ai_tidsreise_synlig"
				value="1"
				<?php checked( $synlig ); ?>
			/>
			<?php esc_html_e( 'Vis refleksjonen automatisk under innholdet på forsiden av innlegget (valgfritt)', 'ai-tidsreise' ); ?>
		</label>
	</p>

	<p class="description">
		<?php
		printf(
			/* translators: %s: shortcode. */
			esc_html__( 'Du kan også plassere refleksjonen manuelt hvor som helst med shortcoden %s', 'ai-tidsreise' ),
			'<code>[etterpaklokskap]</code>'
		);
		?>
	</p>
</div>
