<?php
/**
 * Oversiktsside over genererte refleksjoner.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lister alle innlegg med genererte refleksjoner, slik at forfatteren kan
 * bla gjennom dem uten å måtte åpne hvert innlegg for seg.
 */
class AI_Tidsreise_Overview {

	/**
	 * Antall innlegg per side i oversikten.
	 */
	private const POSTS_PER_PAGE = 20;

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise_Overview|null
	 */
	private static ?AI_Tidsreise_Overview $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise_Overview {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook-suffix returnert av add_submenu_page(), brukt til å målrette asset-lasting.
	 */
	private ?string $hook_suffix = null;

	/**
	 * Konstruktør.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_ai_tidsreise_delete_reflection', array( $this, 'handle_delete' ) );
	}

	/**
	 * Registrer siden som et eget toppnivå-menypunkt, slik at den er lett å finne igjen.
	 */
	public function register_page(): void {
		$this->hook_suffix = (string) add_menu_page(
			__( 'AI Tidsreise – Refleksjoner', 'ai-tidsreise' ),
			__( 'AI Tidsreise', 'ai-tidsreise' ),
			'edit_posts',
			'ai-tidsreise-oversikt',
			array( $this, 'render_page' ),
			'dashicons-clock',
			26
		);
	}

	/**
	 * Last inn CSS kun på oversiktssiden.
	 *
	 * @param string $hook Gjeldende admin-side.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'ai-tidsreise-admin',
			AI_TIDSREISE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			AI_TIDSREISE_VERSION
		);
	}

	/**
	 * Vis oversiktssiden.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;

		$query = new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => self::POSTS_PER_PAGE,
				'paged'          => $paged,
				'meta_key'       => AI_Tidsreise_Post_Meta::META_STATUS,
				'meta_value'     => AI_Tidsreise_Post_Meta::STATUS_IKKE_GENERERT,
				'meta_compare'   => '!=',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		require AI_TIDSREISE_PLUGIN_DIR . 'includes/views/overview-page.php';

		wp_reset_postdata();
	}

	/**
	 * Håndter innsending av slette-skjemaet fra oversiktssiden.
	 *
	 * Sletter refleksjonen, idéen og synlighetsvalget for det oppgitte innlegget,
	 * og sender forfatteren tilbake til oversikten med en bekreftelse.
	 */
	public function handle_delete(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Du har ikke tilgang til å gjøre dette.', 'ai-tidsreise' ), '', array( 'response' => 403 ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		check_admin_referer( 'ai_tidsreise_delete_' . $post_id );

		if ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
			AI_Tidsreise_Post_Meta::delete_reflection( $post_id );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'ai-tidsreise-oversikt',
					'deleted' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
