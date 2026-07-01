<?php
/**
 * Innstillingsside for AI Tidsreise.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registrerer og viser innstillingssiden, inkludert kryptert lagring av API-nøkler.
 */
class AI_Tidsreise_Settings {

	/**
	 * Options-nøkkel i wp_options.
	 */
	private const OPTION_KEY = 'ai_tidsreise_settings';

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise_Settings|null
	 */
	private static ?AI_Tidsreise_Settings $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise_Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktør.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Registrer undermeny for innstillinger under Innlegg.
	 */
	public function register_settings_page(): void {
		add_options_page(
			__( 'AI Tidsreise', 'ai-tidsreise' ),
			__( 'AI Tidsreise', 'ai-tidsreise' ),
			'manage_options',
			'ai-tidsreise-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registrer innstillinger, seksjon og felter.
	 */
	public function register_settings(): void {
		register_setting(
			'ai_tidsreise_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'ai_tidsreise_main_section',
			__( 'AI-leverandør', 'ai-tidsreise' ),
			'__return_false',
			'ai-tidsreise-settings'
		);
	}

	/**
	 * Rens og krypter innsendte innstillinger før lagring.
	 *
	 * @param array<string,mixed> $input Rå input fra skjemaet.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( array $input ): array {
		$existing = $this->get_settings();

		$providers = array_keys( AI_Tidsreise_AI_Provider::get_supported_providers() );
		$provider  = isset( $input['provider'] ) && in_array( $input['provider'], $providers, true )
			? $input['provider']
			: $existing['provider'];

		$sanitized = array(
			'provider'          => $provider,
			'auto_vis_standard' => ! empty( $input['auto_vis_standard'] ),
		);

		foreach ( array( 'claude', 'openai', 'gemini' ) as $key ) {
			$field = 'api_key_' . $key;

			if ( isset( $input[ $field ] ) && '' !== trim( (string) $input[ $field ] ) ) {
				$sanitized[ $field ] = AI_Tidsreise_Encryption::encrypt( sanitize_text_field( (string) $input[ $field ] ) );
			} else {
				// Behold eksisterende krypterte nøkkel dersom feltet ikke er endret.
				$sanitized[ $field ] = $existing[ $field ] ?? '';
			}
		}

		return $sanitized;
	}

	/**
	 * Hent gjeldende innstillinger, med standardverdier.
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings(): array {
		$defaults = array(
			'provider'          => 'claude',
			'api_key_claude'    => '',
			'api_key_openai'    => '',
			'api_key_gemini'    => '',
			'auto_vis_standard' => false,
		);

		$saved = get_option( self::OPTION_KEY, array() );

		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	}

	/**
	 * Hent dekryptert API-nøkkel for en gitt leverandør.
	 *
	 * @param string $provider Leverandørnøkkel: claude, openai eller gemini.
	 */
	public function get_api_key( string $provider ): string {
		$settings = $this->get_settings();
		$field    = 'api_key_' . $provider;

		if ( empty( $settings[ $field ] ) ) {
			return '';
		}

		return AI_Tidsreise_Encryption::decrypt( (string) $settings[ $field ] );
	}

	/**
	 * Vis innstillingssiden.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings  = $this->get_settings();
		$providers = AI_Tidsreise_AI_Provider::get_supported_providers();

		require AI_TIDSREISE_PLUGIN_DIR . 'includes/views/settings-page.php';
	}
}
