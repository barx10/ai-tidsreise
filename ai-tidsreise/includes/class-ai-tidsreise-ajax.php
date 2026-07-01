<?php
/**
 * AJAX-endepunkter for AI Tidsreise.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Håndterer AJAX-kall fra meta-boksen og bulk-generering.
 */
class AI_Tidsreise_Ajax {

	/**
	 * Nonce-handling brukt for alle AJAX-kall i pluginen.
	 */
	public const NONCE_ACTION = 'ai_tidsreise_ajax_nonce';

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise_Ajax|null
	 */
	private static ?AI_Tidsreise_Ajax $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise_Ajax {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktør.
	 */
	private function __construct() {
		add_action( 'wp_ajax_ai_tidsreise_generate', array( $this, 'handle_generate' ) );
	}

	/**
	 * Håndter AJAX-kall for å generere en refleksjon for ett innlegg.
	 *
	 * Brukes både av meta-boksen og av bulk-generering (kalt sekvensielt fra JS-en).
	 */
	public function handle_generate(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Du har ikke tilgang til å gjøre dette.', 'ai-tidsreise' ) ),
				403
			);
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Ugyldig innlegg.', 'ai-tidsreise' ) ),
				400
			);
		}

		if ( ! AI_Tidsreise_Rate_Limiter::is_allowed() ) {
			wp_send_json_error(
				array( 'message' => __( 'For mange forespørsler akkurat nå. Vent litt og prøv igjen.', 'ai-tidsreise' ) ),
				429
			);
		}

		$provider = new AI_Tidsreise_AI_Provider();
		$result   = $provider->generate_reflection( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_REFLEKSJON, wp_kses_post( $result ) );
		update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_STATUS, AI_Tidsreise_Post_Meta::STATUS_GENERERT );

		wp_send_json_success(
			array(
				'refleksjon' => wp_kses_post( $result ),
				'status'     => AI_Tidsreise_Post_Meta::STATUS_GENERERT,
			)
		);
	}
}
