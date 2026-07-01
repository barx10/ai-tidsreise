<?php
/**
 * Abstraksjon for AI-leverandører (Claude, OpenAI, Gemini).
 *
 * SKJELETT: Metodekroppene for de faktiske API-kallene er ikke implementert
 * ennå. Full implementasjon fylles inn i et eget steg etter bekreftelse.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genererer 2026-refleksjoner ved hjelp av en valgt AI-leverandør.
 */
class AI_Tidsreise_AI_Provider {

	/**
	 * Hardkodet systemprompt som styrer hvordan refleksjonen skrives.
	 *
	 * Skal IKKE eksponeres eller gjøres redigerbar i UI.
	 */
	private const SYSTEM_PROMPT = <<<'PROMPT'
Du skal analysere et blogginnlegg som om det var en dagboksnotat fra fortiden, skrevet av forfatteren selv.

Skriv en reflektert innsikt datert 2026, i forfatterens egen stil: intellektuell, varm og tilgjengelig.

Vurder om ideen i innlegget holdt vann. Drøft hvorfor den eventuelt forsvant, ble glemt, eller utviklet seg videre i lys av det som har skjedd siden.

Skap idékoblinger til andre kjente temaer hos forfatteren, som kritisk tenkning, litteratur, KI i skolen og app-utvikling, der det er naturlig.

Skriv på feilfritt norsk (bokmål). Bruk ikke tankestreker. Unngå amerikansk skrivestil og anglisismer. Hold en fast, personlig og reflektert tone gjennom hele teksten.
PROMPT;

	/**
	 * Støttede leverandører og tilhørende visningsnavn.
	 *
	 * @return array<string, string>
	 */
	public static function get_supported_providers(): array {
		return array(
			'claude' => 'Claude (Anthropic)',
			'openai' => 'OpenAI',
			'gemini' => 'Google Gemini',
		);
	}

	/**
	 * Generer en 2026-refleksjon for et gitt innlegg.
	 *
	 * @param int $post_id Innleggets ID.
	 * @return string|WP_Error Generert tekst, eller WP_Error ved feil.
	 */
	public function generate_reflection( int $post_id ) {
		if ( ! AI_Tidsreise_Rate_Limiter::is_allowed() ) {
			return new WP_Error(
				'ai_tidsreise_rate_limited',
				__( 'For mange forespørsler. Vent litt før du prøver igjen.', 'ai-tidsreise' )
			);
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'ai_tidsreise_invalid_post',
				__( 'Fant ikke innlegget.', 'ai-tidsreise' )
			);
		}

		$settings = AI_Tidsreise_Settings::get_instance()->get_settings();
		$provider = $settings['provider'] ?? 'claude';

		AI_Tidsreise_Rate_Limiter::register_call();

		return match ( $provider ) {
			'claude' => $this->call_claude( $post, $settings ),
			'openai' => $this->call_openai( $post, $settings ),
			'gemini' => $this->call_gemini( $post, $settings ),
			default  => new WP_Error( 'ai_tidsreise_unknown_provider', __( 'Ukjent AI-leverandør.', 'ai-tidsreise' ) ),
		};
	}

	/**
	 * Kall Anthropic Claude sitt API.
	 *
	 * @param WP_Post             $post     Innlegget som skal analyseres.
	 * @param array<string,mixed> $settings Pluginens innstillinger.
	 * @return string|WP_Error
	 */
	private function call_claude( WP_Post $post, array $settings ) {
		// TODO: Implementeres i eget steg. Skal bruke wp_remote_post() mot
		// Anthropic sitt Messages-API, med SYSTEM_PROMPT som system-parameter.
		return new WP_Error( 'ai_tidsreise_not_implemented', __( 'Claude-integrasjonen er ikke implementert ennå.', 'ai-tidsreise' ) );
	}

	/**
	 * Kall OpenAI sitt API.
	 *
	 * @param WP_Post             $post     Innlegget som skal analyseres.
	 * @param array<string,mixed> $settings Pluginens innstillinger.
	 * @return string|WP_Error
	 */
	private function call_openai( WP_Post $post, array $settings ) {
		// TODO: Implementeres i eget steg. Skal bruke wp_remote_post() mot
		// OpenAI sitt Chat Completions- eller Responses-API.
		return new WP_Error( 'ai_tidsreise_not_implemented', __( 'OpenAI-integrasjonen er ikke implementert ennå.', 'ai-tidsreise' ) );
	}

	/**
	 * Kall Google Gemini sitt API.
	 *
	 * @param WP_Post             $post     Innlegget som skal analyseres.
	 * @param array<string,mixed> $settings Pluginens innstillinger.
	 * @return string|WP_Error
	 */
	private function call_gemini( WP_Post $post, array $settings ) {
		// TODO: Implementeres i eget steg. Skal bruke wp_remote_post() mot
		// Google sitt Gemini generateContent-API.
		return new WP_Error( 'ai_tidsreise_not_implemented', __( 'Gemini-integrasjonen er ikke implementert ennå.', 'ai-tidsreise' ) );
	}

	/**
	 * Bygg brukerprompten som sendes sammen med systemprompten.
	 *
	 * @param WP_Post $post Innlegget som skal analyseres.
	 */
	private function build_user_prompt( WP_Post $post ): string {
		$title   = wp_strip_all_tags( get_the_title( $post ) );
		$content = wp_strip_all_tags( $post->post_content );
		$date    = get_the_date( 'j. F Y', $post );

		return sprintf(
			"Innlegg publisert %s\nTittel: %s\n\nInnhold:\n%s",
			$date,
			$title,
			$content
		);
	}
}
