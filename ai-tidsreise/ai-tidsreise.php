<?php
/**
 * Plugin Name:       AI Tidsreise
 * Plugin URI:        https://github.com/barx10/ai-tidsreise
 * Description:       Genererer reflekterte 2026-perspektiver på gamle blogginnlegg, skrevet i forfatterens egen stemme, med etterpåklokskapens blikk.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Bård
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-tidsreise
 * Domain Path:       /languages
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

// Stopp direkte tilgang.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_TIDSREISE_VERSION', '0.1.0' );
define( 'AI_TIDSREISE_PLUGIN_FILE', __FILE__ );
define( 'AI_TIDSREISE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_TIDSREISE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AI_TIDSREISE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Hovedklasse som setter opp og starter pluginen.
 */
final class AI_Tidsreise {

	/**
	 * Singleton-instans.
	 *
	 * @var AI_Tidsreise|null
	 */
	private static ?AI_Tidsreise $instance = null;

	/**
	 * Hent singleton-instansen.
	 */
	public static function get_instance(): AI_Tidsreise {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktør. Privat for å håndheve singleton-mønsteret.
	 */
	private function __construct() {
		$this->include_files();
		$this->init_hooks();
	}

	/**
	 * Inkluder alle klassefiler pluginen er avhengig av.
	 */
	private function include_files(): void {
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-encryption.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-rate-limiter.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-ai-provider.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-post-meta.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-settings.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-metabox.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-ajax.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-bulk.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-shortcode.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-content-filter.php';
		require_once AI_TIDSREISE_PLUGIN_DIR . 'includes/class-ai-tidsreise-dashboard-widget.php';
	}

	/**
	 * Registrer WordPress-hooks og last inn moduler.
	 */
	private function init_hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		AI_Tidsreise_Post_Meta::get_instance();
		AI_Tidsreise_Settings::get_instance();
		AI_Tidsreise_Metabox::get_instance();
		AI_Tidsreise_Ajax::get_instance();
		AI_Tidsreise_Bulk::get_instance();
		AI_Tidsreise_Shortcode::get_instance();
		AI_Tidsreise_Content_Filter::get_instance();
		AI_Tidsreise_Dashboard_Widget::get_instance();
	}

	/**
	 * Last inn oversettelser.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'ai-tidsreise', false, dirname( AI_TIDSREISE_PLUGIN_BASENAME ) . '/languages' );
	}
}

/**
 * Kjøres ved aktivering av pluginen.
 */
function ai_tidsreise_activate(): void {
	if ( ! get_option( 'ai_tidsreise_settings' ) ) {
		add_option(
			'ai_tidsreise_settings',
			array(
				'provider'          => 'gemini',
				'api_key_claude'    => '',
				'api_key_openai'    => '',
				'api_key_gemini'    => '',
				'model_claude'      => 'claude-sonnet-5',
				'model_openai'      => 'gpt-4.1-mini',
				'model_gemini'      => 'gemini-3.5-flash',
				'auto_vis_standard' => false,
			)
		);
	}
}
register_activation_hook( __FILE__, 'ai_tidsreise_activate' );

/**
 * Kjøres ved deaktivering av pluginen.
 */
function ai_tidsreise_deactivate(): void {
	// Ingen opprydding av data ved deaktivering. Data fjernes kun ved avinstallering.
}
register_deactivation_hook( __FILE__, 'ai_tidsreise_deactivate' );

/**
 * Start pluginen.
 */
function ai_tidsreise(): AI_Tidsreise {
	return AI_Tidsreise::get_instance();
}
ai_tidsreise();
