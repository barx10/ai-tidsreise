<?php
/**
 * Automatisk innsetting av 2026-refleksjon under innholdet.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setter inn refleksjonsboksen automatisk under innholdet på innlegg
 * der forfatteren har slått på automatisk visning, og tilbyr felles
 * markup-bygging som også brukes av shortcoden.
 */
class AI_Tidsreise_Content_Filter {

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise_Content_Filter|null
	 */
	private static ?AI_Tidsreise_Content_Filter $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise_Content_Filter {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktør.
	 */
	private function __construct() {
		add_filter( 'the_content', array( $this, 'maybe_append_reflection' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Last inn frontend-stilark for refleksjonsboksen.
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'ai-tidsreise-frontend',
			AI_TIDSREISE_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			AI_TIDSREISE_VERSION
		);
	}

	/**
	 * Legg til refleksjonsboksen under innholdet, dersom den er slått på
	 * for gjeldende innlegg og en refleksjon finnes.
	 *
	 * @param string $content Innleggets innhold.
	 */
	public function maybe_append_reflection( string $content ): string {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();

		if ( ! $post_id || ! AI_Tidsreise_Post_Meta::is_synlig( $post_id ) ) {
			return $content;
		}

		return $content . self::get_markup( $post_id );
	}

	/**
	 * Bygg HTML-markup for refleksjonsboksen for et gitt innlegg.
	 *
	 * @param int $post_id Innleggets ID.
	 */
	public static function get_markup( int $post_id ): string {
		$refleksjon = AI_Tidsreise_Post_Meta::get_refleksjon( $post_id );

		if ( '' === trim( $refleksjon ) ) {
			return '';
		}

		$heading = apply_filters( 'ai_tidsreise_boks_heading', __( 'Etterpåklokskapens blikk – 2026', 'ai-tidsreise' ), $post_id );

		ob_start();
		?>
		<div class="etterpaklokskap-boks">
			<h3 class="etterpaklokskap-boks-tittel"><?php echo esc_html( $heading ); ?></h3>
			<div class="etterpaklokskap-boks-innhold">
				<?php echo wp_kses_post( wpautop( $refleksjon ) ); ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
