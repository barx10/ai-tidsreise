<?php
/**
 * Registrering av post meta for AI Tidsreise.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registrerer og gir tilgang til post meta-feltene pluginen bruker.
 */
class AI_Tidsreise_Post_Meta {

	/**
	 * Meta-nøkkel for generert refleksjonstekst.
	 */
	public const META_REFLEKSJON = '_etterpaklokskap_refleksjon';

	/**
	 * Meta-nøkkel for status: ikke_generert, generert, publisert.
	 */
	public const META_STATUS = '_etterpaklokskap_status';

	/**
	 * Meta-nøkkel for om refleksjonen skal vises automatisk.
	 */
	public const META_SYNLIG = '_etterpaklokskap_synlig';

	/**
	 * Status: ikke generert.
	 */
	public const STATUS_IKKE_GENERERT = 'ikke_generert';

	/**
	 * Status: generert, men ikke publisert/godkjent.
	 */
	public const STATUS_GENERERT = 'generert';

	/**
	 * Status: publisert/godkjent for visning.
	 */
	public const STATUS_PUBLISERT = 'publisert';

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise_Post_Meta|null
	 */
	private static ?AI_Tidsreise_Post_Meta $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise_Post_Meta {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktør.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Registrer post meta-feltene på post-posttypen.
	 */
	public function register_meta(): void {
		register_post_meta(
			'post',
			self::META_REFLEKSJON,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => false,
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'post',
			self::META_STATUS,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => self::STATUS_IKKE_GENERERT,
				'show_in_rest'      => false,
				'sanitize_callback' => array( $this, 'sanitize_status' ),
				'auth_callback'     => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'post',
			self::META_SYNLIG,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Sørg for at status alltid er en av de gyldige verdiene.
	 *
	 * @param string $value Innsendt verdi.
	 */
	public function sanitize_status( string $value ): string {
		$valid = array( self::STATUS_IKKE_GENERERT, self::STATUS_GENERERT, self::STATUS_PUBLISERT );

		return in_array( $value, $valid, true ) ? $value : self::STATUS_IKKE_GENERERT;
	}

	/**
	 * Hent refleksjonsteksten for et innlegg.
	 *
	 * @param int $post_id Innleggets ID.
	 */
	public static function get_refleksjon( int $post_id ): string {
		return (string) get_post_meta( $post_id, self::META_REFLEKSJON, true );
	}

	/**
	 * Hent statusen for et innlegg.
	 *
	 * @param int $post_id Innleggets ID.
	 */
	public static function get_status( int $post_id ): string {
		$status = get_post_meta( $post_id, self::META_STATUS, true );

		return '' === $status ? self::STATUS_IKKE_GENERERT : (string) $status;
	}

	/**
	 * Sjekk om automatisk visning er slått på for et innlegg.
	 *
	 * @param int $post_id Innleggets ID.
	 */
	public static function is_synlig( int $post_id ): bool {
		return (bool) get_post_meta( $post_id, self::META_SYNLIG, true );
	}
}
