<?php
/**
 * Shortcode for manuell visning av 2026-refleksjon.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registrerer shortcoden [etterpaklokskap].
 */
class AI_Tidsreise_Shortcode {

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise_Shortcode|null
	 */
	private static ?AI_Tidsreise_Shortcode $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise_Shortcode {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktør.
	 */
	private function __construct() {
		add_shortcode( 'etterpaklokskap', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render shortcoden [etterpaklokskap].
	 *
	 * @param array<string,mixed>|string $atts Shortcode-attributter.
	 */
	public function render_shortcode( $atts ): string {
		$attributes = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
			),
			$atts,
			'etterpaklokskap'
		);

		$post_id = absint( $attributes['post_id'] );

		if ( ! $post_id ) {
			return '';
		}

		return AI_Tidsreise_Content_Filter::get_markup( $post_id );
	}
}
