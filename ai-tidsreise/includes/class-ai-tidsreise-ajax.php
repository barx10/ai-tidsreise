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
		add_action( 'wp_ajax_ai_tidsreise_generate_naeste_id', array( $this, 'handle_generate_naeste_id' ) );
		add_action( 'wp_ajax_ai_tidsreise_save', array( $this, 'handle_save' ) );
	}

	/**
	 * Sjekk nonce, tilgang og hent et gyldig innlegg-ID fra requesten.
	 *
	 * Avslutter forespørselen med en feilrespons dersom noe ikke stemmer.
	 */
	private function get_authorized_post_id(): int {
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

		return $post_id;
	}

	/**
	 * Håndter AJAX-kall for å generere refleksjonen for ett innlegg.
	 *
	 * Brukes både av meta-boksen og av bulk-generering (kalt sekvensielt fra JS-en).
	 * Genererer kun selve refleksjonen, ikke idéen til neste innlegg, som forfatteren
	 * kun skal få når det bes eksplisitt om det.
	 */
	public function handle_generate(): void {
		$post_id = $this->get_authorized_post_id();

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

		$refleksjon = wp_kses_post( $result );

		update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_REFLEKSJON, $refleksjon );
		update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_STATUS, AI_Tidsreise_Post_Meta::STATUS_GENERERT );

		wp_send_json_success(
			array(
				'refleksjon' => $refleksjon,
				'status'     => AI_Tidsreise_Post_Meta::STATUS_GENERERT,
			)
		);
	}

	/**
	 * Håndter AJAX-kall for å generere en idé til neste innlegg.
	 *
	 * Kalles kun når forfatteren eksplisitt trykker på egen knapp for dette,
	 * og krever at refleksjonen for innlegget allerede er generert.
	 */
	public function handle_generate_naeste_id(): void {
		$post_id = $this->get_authorized_post_id();

		if ( ! AI_Tidsreise_Rate_Limiter::is_allowed() ) {
			wp_send_json_error(
				array( 'message' => __( 'For mange forespørsler akkurat nå. Vent litt og prøv igjen.', 'ai-tidsreise' ) ),
				429
			);
		}

		$provider = new AI_Tidsreise_AI_Provider();
		$result   = $provider->generate_follow_up_idea( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		$naeste_id = wp_kses_post( $result );

		update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_NAESTE_ID, $naeste_id );

		wp_send_json_success(
			array(
				'naesteId' => $naeste_id,
			)
		);
	}

	/**
	 * Håndter AJAX-kall for å lagre redigert refleksjon, idé og synlighet umiddelbart,
	 * uten å måtte oppdatere/publisere hele innlegget.
	 */
	public function handle_save(): void {
		$post_id = $this->get_authorized_post_id();

		$refleksjon = isset( $_POST['refleksjon'] ) ? wp_kses_post( wp_unslash( $_POST['refleksjon'] ) ) : '';
		$naeste_id  = isset( $_POST['naeste_id'] ) ? wp_kses_post( wp_unslash( $_POST['naeste_id'] ) ) : '';
		$synlig     = ! empty( $_POST['synlig'] ) ? '1' : '';

		update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_REFLEKSJON, $refleksjon );
		update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_NAESTE_ID, $naeste_id );
		update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_SYNLIG, $synlig );

		if ( '' !== trim( wp_strip_all_tags( $refleksjon ) ) ) {
			$current_status = AI_Tidsreise_Post_Meta::get_status( $post_id );

			if ( AI_Tidsreise_Post_Meta::STATUS_IKKE_GENERERT === $current_status ) {
				update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_STATUS, AI_Tidsreise_Post_Meta::STATUS_GENERERT );
			}
		}

		wp_send_json_success(
			array(
				'status' => AI_Tidsreise_Post_Meta::get_status( $post_id ),
			)
		);
	}
}
