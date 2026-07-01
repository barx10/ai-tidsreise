<?php
/**
 * Dashboard-widget for AI Tidsreise.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Viser en oversikt på WordPress-dashbordet over hvor mange innlegg
 * som har fått generert, publisert eller mangler en 2026-refleksjon.
 */
class AI_Tidsreise_Dashboard_Widget {

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise_Dashboard_Widget|null
	 */
	private static ?AI_Tidsreise_Dashboard_Widget $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise_Dashboard_Widget {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktør.
	 */
	private function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
	}

	/**
	 * Registrer dashboard-widgeten.
	 */
	public function register_widget(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'ai_tidsreise_dashboard_widget',
			__( 'AI Tidsreise', 'ai-tidsreise' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Vis widgetens innhold: en oppsummering av status på tvers av innlegg.
	 */
	public function render_widget(): void {
		$counts = $this->get_status_counts();

		require AI_TIDSREISE_PLUGIN_DIR . 'includes/views/dashboard-widget.php';
	}

	/**
	 * Tell antall innlegg per refleksjonsstatus.
	 *
	 * @return array<string,int>
	 */
	private function get_status_counts(): array {
		global $wpdb;

		$counts = array(
			AI_Tidsreise_Post_Meta::STATUS_IKKE_GENERERT => 0,
			AI_Tidsreise_Post_Meta::STATUS_GENERERT      => 0,
			AI_Tidsreise_Post_Meta::STATUS_PUBLISERT     => 0,
		);

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value, COUNT(*) as antall
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				AND p.post_type = 'post'
				AND p.post_status = 'publish'
				GROUP BY meta_value",
				AI_Tidsreise_Post_Meta::META_STATUS
			)
		);

		foreach ( $results as $row ) {
			if ( isset( $counts[ $row->meta_value ] ) ) {
				$counts[ $row->meta_value ] = (int) $row->antall;
			}
		}

		$total_published = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'"
		);

		$with_status               = array_sum( $counts );
		$counts[ AI_Tidsreise_Post_Meta::STATUS_IKKE_GENERERT ] += max( 0, $total_published - $with_status );

		return $counts;
	}
}
