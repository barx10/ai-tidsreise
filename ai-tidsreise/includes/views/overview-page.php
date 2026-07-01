<?php
/**
 * View: Oversikt over genererte refleksjoner.
 *
 * Forventer $query og $paged fra AI_Tidsreise_Overview::render_page().
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
<div class="wrap ai-tidsreise-overview">
	<h1><?php esc_html_e( 'AI Tidsreise – Refleksjoner', 'ai-tidsreise' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Bla gjennom tidligere innlegg som har fått en 2026-refleksjon. Klikk på et innlegg for å lese, redigere eller generere på nytt.', 'ai-tidsreise' ); ?>
	</p>

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Refleksjonen ble slettet.', 'ai-tidsreise' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! $query->have_posts() ) : ?>
		<p><?php esc_html_e( 'Ingen refleksjoner er generert ennå.', 'ai-tidsreise' ); ?></p>
	<?php else : ?>
		<?php
		while ( $query->have_posts() ) :
			$query->the_post();

			$post_id    = get_the_ID();
			$status     = AI_Tidsreise_Post_Meta::get_status( $post_id );
			$refleksjon = AI_Tidsreise_Post_Meta::get_refleksjon( $post_id );
			$naeste_id  = AI_Tidsreise_Post_Meta::get_naeste_id( $post_id );
			?>
			<div class="ai-tidsreise-overview-item">
				<h2>
					<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">
						<?php echo esc_html( get_the_title( $post_id ) ); ?>
					</a>
				</h2>
				<p class="ai-tidsreise-overview-meta">
					<?php
					printf(
						/* translators: 1: publiseringsdato, 2: status. */
						esc_html__( 'Publisert %1$s · Status: %2$s', 'ai-tidsreise' ),
						esc_html( get_the_date( 'j. F Y', $post_id ) ),
						esc_html( $status_labels[ $status ] ?? $status_labels[ AI_Tidsreise_Post_Meta::STATUS_IKKE_GENERERT ] )
					);
					?>
				</p>

				<?php if ( '' !== $refleksjon ) : ?>
					<p class="ai-tidsreise-overview-excerpt">
						<strong><?php esc_html_e( 'Refleksjon:', 'ai-tidsreise' ); ?></strong>
						<?php echo esc_html( wp_trim_words( wp_strip_all_tags( $refleksjon ), 40 ) ); ?>
					</p>
				<?php endif; ?>

				<?php if ( '' !== $naeste_id ) : ?>
					<p class="ai-tidsreise-overview-excerpt">
						<strong><?php esc_html_e( 'Idé til neste innlegg:', 'ai-tidsreise' ); ?></strong>
						<?php echo esc_html( wp_trim_words( wp_strip_all_tags( $naeste_id ), 40 ) ); ?>
					</p>
				<?php endif; ?>

				<p>
					<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" class="button">
						<?php esc_html_e( 'Åpne innlegg', 'ai-tidsreise' ); ?>
					</a>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ai-tidsreise-delete-form" onsubmit="return confirm( '<?php echo esc_js( __( 'Er du sikker på at du vil slette refleksjonen og idéen for dette innlegget? Dette kan ikke angres.', 'ai-tidsreise' ) ); ?>' );">
						<input type="hidden" name="action" value="ai_tidsreise_delete_reflection" />
						<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $post_id ); ?>" />
						<?php wp_nonce_field( 'ai_tidsreise_delete_' . $post_id ); ?>
						<button type="submit" class="button button-link-delete">
							<?php esc_html_e( 'Slett refleksjon', 'ai-tidsreise' ); ?>
						</button>
					</form>
				</p>
			</div>
			<hr />
		<?php endwhile; ?>

		<?php
		$total_pages = (int) $query->max_num_pages;

		if ( $total_pages > 1 ) :
			?>
			<div class="tablenav">
				<div class="tablenav-pages">
					<?php
					echo paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $paged,
							'total'     => $total_pages,
							'prev_text' => __( '&laquo; Forrige', 'ai-tidsreise' ),
							'next_text' => __( 'Neste &raquo;', 'ai-tidsreise' ),
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
