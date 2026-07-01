<?php
/**
 * Meta-boks for generering og redigering av 2026-refleksjon.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registrerer meta-boksen som vises under hovedinnholdet i redigeringsvisningen.
 */
class AI_Tidsreise_Metabox {

	/**
	 * Nonce-handling brukt for meta-boksen.
	 */
	public const NONCE_ACTION = 'ai_tidsreise_metabox_nonce';

	/**
	 * Navn på nonce-feltet i skjemaet.
	 */
	public const NONCE_FIELD = 'ai_tidsreise_metabox_nonce_field';

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise_Metabox|null
	 */
	private static ?AI_Tidsreise_Metabox $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise_Metabox {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktør.
	 */
	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post', array( $this, 'save_manual_edit' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registrer meta-boksen for innlegg, plassert under hovedinnholdet.
	 */
	public function register_metabox(): void {
		add_meta_box(
			'ai_tidsreise_metabox',
			__( 'AI Tidsreise – 2026-refleksjon', 'ai-tidsreise' ),
			array( $this, 'render_metabox' ),
			'post',
			'normal',
			'default'
		);
	}

	/**
	 * Last inn CSS/JS kun på innleggets redigeringsside.
	 *
	 * @param string $hook Gjeldende admin-side.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'ai-tidsreise-admin',
			AI_TIDSREISE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			AI_TIDSREISE_VERSION
		);

		wp_enqueue_script(
			'ai-tidsreise-admin-metabox',
			AI_TIDSREISE_PLUGIN_URL . 'assets/js/admin-metabox.js',
			array( 'jquery' ),
			AI_TIDSREISE_VERSION,
			true
		);

		wp_localize_script(
			'ai-tidsreise-admin-metabox',
			'aiTidsreiseMetabox',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ai_tidsreise_ajax_nonce' ),
				'postId'  => get_the_ID() ?: 0,
				'i18n'    => array(
					'generating'      => __( 'Genererer refleksjon …', 'ai-tidsreise' ),
					'success'         => __( 'Refleksjon generert. Husk å lagre eller oppdatere innlegget.', 'ai-tidsreise' ),
					'error'           => __( 'Noe gikk galt under generering.', 'ai-tidsreise' ),
					'statusGenerert'  => __( 'Generert', 'ai-tidsreise' ),
				),
			)
		);
	}

	/**
	 * Vis meta-boksens innhold.
	 *
	 * @param WP_Post $post Innlegget som redigeres.
	 */
	public function render_metabox( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$refleksjon = AI_Tidsreise_Post_Meta::get_refleksjon( $post->ID );
		$status     = AI_Tidsreise_Post_Meta::get_status( $post->ID );
		$synlig     = AI_Tidsreise_Post_Meta::is_synlig( $post->ID );

		require AI_TIDSREISE_PLUGIN_DIR . 'includes/views/metabox.php';
	}

	/**
	 * Lagre manuelle endringer gjort i meta-boksen (redigert tekst, synlighet).
	 *
	 * @param int $post_id Innleggets ID.
	 */
	public function save_manual_edit( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['ai_tidsreise_refleksjon'] ) ) {
			$refleksjon = wp_kses_post( wp_unslash( $_POST['ai_tidsreise_refleksjon'] ) );
			update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_REFLEKSJON, $refleksjon );

			if ( '' !== trim( $refleksjon ) ) {
				$current_status = AI_Tidsreise_Post_Meta::get_status( $post_id );

				if ( AI_Tidsreise_Post_Meta::STATUS_IKKE_GENERERT === $current_status ) {
					update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_STATUS, AI_Tidsreise_Post_Meta::STATUS_GENERERT );
				}
			}
		}

		$synlig = isset( $_POST['ai_tidsreise_synlig'] ) ? '1' : '';
		update_post_meta( $post_id, AI_Tidsreise_Post_Meta::META_SYNLIG, $synlig );
	}
}
