<?php
/**
 * View: Innstillingsside for AI Tidsreise.
 *
 * Forventer variablene $settings og $providers fra AI_Tidsreise_Settings::render_settings_page().
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap ai-tidsreise-settings">
	<h1><?php esc_html_e( 'AI Tidsreise – innstillinger', 'ai-tidsreise' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'ai_tidsreise_settings_group' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="ai_tidsreise_provider"><?php esc_html_e( 'AI-leverandør', 'ai-tidsreise' ); ?></label>
				</th>
				<td>
					<select name="ai_tidsreise_settings[provider]" id="ai_tidsreise_provider">
						<?php foreach ( $providers as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['provider'], $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<?php foreach ( $providers as $value => $label ) : ?>
				<tr>
					<th scope="row">
						<label for="ai_tidsreise_api_key_<?php echo esc_attr( $value ); ?>">
							<?php
							printf(
								/* translators: %s: navn på AI-leverandør. */
								esc_html__( 'API-nøkkel (%s)', 'ai-tidsreise' ),
								esc_html( $label )
							);
							?>
						</label>
					</th>
					<td>
						<input
							type="password"
							class="regular-text"
							id="ai_tidsreise_api_key_<?php echo esc_attr( $value ); ?>"
							name="ai_tidsreise_settings[api_key_<?php echo esc_attr( $value ); ?>]"
							value=""
							autocomplete="new-password"
							placeholder="<?php echo ! empty( $settings[ 'api_key_' . $value ] ) ? esc_attr__( '•••••••• (lagret)', 'ai-tidsreise' ) : ''; ?>"
						/>
						<p class="description">
							<?php esc_html_e( 'La feltet stå tomt for å beholde eksisterende lagret nøkkel.', 'ai-tidsreise' ); ?>
						</p>
					</td>
				</tr>
			<?php endforeach; ?>

			<tr>
				<th scope="row"><?php esc_html_e( 'Automatisk visning', 'ai-tidsreise' ); ?></th>
				<td>
					<label for="ai_tidsreise_auto_vis_standard">
						<input
							type="checkbox"
							id="ai_tidsreise_auto_vis_standard"
							name="ai_tidsreise_settings[auto_vis_standard]"
							value="1"
							<?php checked( ! empty( $settings['auto_vis_standard'] ) ); ?>
						/>
						<?php esc_html_e( 'Slå på automatisk visning av refleksjon som standard for nye innlegg', 'ai-tidsreise' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
