<?php
/**
 * Bulk-generering fra innleggslisten.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legger til en bulk-handling i innleggslisten som starter kø-basert
 * generering av 2026-refleksjoner for flere innlegg samtidig.
 */
class AI_Tidsreise_Bulk {

	/**
	 * Verdi brukt i bulk-handling-nedtrekksmenyen.
	 */
	private const BULK_ACTION = 'ai_tidsreise_bulk_generate';

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise_Bulk|null
	 */
	private static ?AI_Tidsreise_Bulk $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise_Bulk {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktør.
	 */
	private function __construct() {
		add_filter( 'bulk_actions-edit-post', array( $this, 'register_bulk_action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_queue_notice' ) );
	}

	/**
	 * Legg til bulk-handlingen i innleggslistens nedtrekksmeny.
	 *
	 * @param array<string,string> $bulk_actions Eksisterende bulk-handlinger.
	 * @return array<string,string>
	 */
	public function register_bulk_action( array $bulk_actions ): array {
		$bulk_actions[ self::BULK_ACTION ] = __( 'Generer 2026-refleksjon (AI Tidsreise)', 'ai-tidsreise' );

		return $bulk_actions;
	}

	/**
	 * Last inn JS for bulk-kø kun på innleggslisten.
	 *
	 * @param string $hook Gjeldende admin-side.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'edit.php' !== $hook ) {
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
			'ai-tidsreise-admin-bulk',
			AI_TIDSREISE_PLUGIN_URL . 'assets/js/admin-bulk.js',
			array( 'jquery' ),
			AI_TIDSREISE_VERSION,
			true
		);

		wp_localize_script(
			'ai-tidsreise-admin-bulk',
			'aiTidsreiseBulk',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( AI_Tidsreise_Ajax::NONCE_ACTION ),
				'bulkAction'   => self::BULK_ACTION,
				'delayMs'      => 2100, // Ca. 28 kall/min, med margin under 30/min-grensen.
				'i18n'         => array(
					'progress' => __( 'Genererer refleksjoner: %1$d av %2$d fullført.', 'ai-tidsreise' ),
					'done'     => __( 'Ferdig! Refleksjoner generert for de valgte innleggene.', 'ai-tidsreise' ),
					'error'    => __( 'En feil oppstod under generering for innlegg-ID %d.', 'ai-tidsreise' ),
				),
			)
		);
	}

	/**
	 * Vis en tom beholder for kø-statusmeldinger på innleggslisten.
	 *
	 * Selve logikken for å fange opp bulk-handlingen og starte køen
	 * håndteres av admin-bulk.js, som lytter på innsending av skjemaet.
	 */
	public function maybe_render_queue_notice(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-post' !== $screen->id ) {
			return;
		}

		echo '<div id="ai-tidsreise-bulk-progress" class="notice notice-info" style="display:none;"><p></p></div>';
	}
}
