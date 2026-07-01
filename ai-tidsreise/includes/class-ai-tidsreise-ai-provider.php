<?php
/**
 * Abstraksjon for AI-leverandører (Claude, OpenAI, Gemini).
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

Skriv i ren løpende tekst, som vanlig prosa i avsnitt. Bruk aldri Markdown-syntaks eller andre formateringstegn som stjerner, firkanttegn eller understreker rundt ord eller overskrifter. Ikke bruk punktlister eller nummererte lister. Ikke inkluder noen overskrift eller tittel før selve teksten.
PROMPT;

	/**
	 * Maks antall tegn av innleggsinnholdet som sendes til AI-en.
	 */
	private const MAX_CONTENT_LENGTH = 8000;

	/**
	 * Maks antall tokens AI-en får generere i svaret.
	 */
	private const MAX_OUTPUT_TOKENS = 3000;

	/**
	 * Tidsavbrudd for eksterne API-kall, i sekunder.
	 */
	private const REQUEST_TIMEOUT = 45;

	/**
	 * Støttede leverandører og tilhørende visningsnavn.
	 *
	 * @return array<string, string>
	 */
	public static function get_supported_providers(): array {
		return array(
			'gemini' => 'Google Gemini',
			'claude' => 'Claude (Anthropic)',
			'openai' => 'OpenAI',
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

		$settings_service = AI_Tidsreise_Settings::get_instance();
		$settings          = $settings_service->get_settings();
		$provider          = $settings['provider'] ?? 'gemini';
		$api_key           = $settings_service->get_api_key( $provider );

		if ( '' === $api_key ) {
			return new WP_Error(
				'ai_tidsreise_missing_api_key',
				__( 'Ingen API-nøkkel er konfigurert for valgt leverandør. Gå til AI Tidsreise-innstillingene.', 'ai-tidsreise' )
			);
		}

		$model = $settings_service->get_model( $provider );

		AI_Tidsreise_Rate_Limiter::register_call();

		return match ( $provider ) {
			'claude' => $this->call_claude( $post, $api_key, $model ),
			'openai' => $this->call_openai( $post, $api_key, $model ),
			'gemini' => $this->call_gemini( $post, $api_key, $model ),
			default  => new WP_Error( 'ai_tidsreise_unknown_provider', __( 'Ukjent AI-leverandør.', 'ai-tidsreise' ) ),
		};
	}

	/**
	 * Kall Anthropic Claude sitt Messages-API.
	 *
	 * @param WP_Post $post    Innlegget som skal analyseres.
	 * @param string  $api_key Dekryptert API-nøkkel.
	 * @param string  $model   Modellnavn.
	 * @return string|WP_Error
	 */
	private function call_claude( WP_Post $post, string $api_key, string $model ) {
		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => self::REQUEST_TIMEOUT,
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'content-type'      => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => $model,
						'max_tokens' => self::MAX_OUTPUT_TOKENS,
						'system'     => self::SYSTEM_PROMPT,
						'messages'   => array(
							array(
								'role'    => 'user',
								'content' => $this->build_user_prompt( $post ),
							),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->wrap_transport_error( $response );
		}

		$data = $this->decode_response( $response, 'claude' );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$text = $data['content'][0]['text'] ?? '';

		return $this->finalize_text( (string) $text );
	}

	/**
	 * Kall OpenAI sitt Chat Completions-API.
	 *
	 * @param WP_Post $post    Innlegget som skal analyseres.
	 * @param string  $api_key Dekryptert API-nøkkel.
	 * @param string  $model   Modellnavn.
	 * @return string|WP_Error
	 */
	private function call_openai( WP_Post $post, string $api_key, string $model ) {
		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => self::REQUEST_TIMEOUT,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => $model,
						'max_tokens' => self::MAX_OUTPUT_TOKENS,
						'messages'   => array(
							array(
								'role'    => 'system',
								'content' => self::SYSTEM_PROMPT,
							),
							array(
								'role'    => 'user',
								'content' => $this->build_user_prompt( $post ),
							),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->wrap_transport_error( $response );
		}

		$data = $this->decode_response( $response, 'openai' );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$text = $data['choices'][0]['message']['content'] ?? '';

		return $this->finalize_text( (string) $text );
	}

	/**
	 * Kall Google Gemini sitt generateContent-API.
	 *
	 * @param WP_Post $post    Innlegget som skal analyseres.
	 * @param string  $api_key Dekryptert API-nøkkel.
	 * @param string  $model   Modellnavn.
	 * @return string|WP_Error
	 */
	private function call_gemini( WP_Post $post, string $api_key, string $model ) {
		$url = sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
			rawurlencode( $model )
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => self::REQUEST_TIMEOUT,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'x-goog-api-key' => $api_key,
				),
				'body'    => wp_json_encode(
					array(
						'system_instruction' => array(
							'parts' => array(
								array( 'text' => self::SYSTEM_PROMPT ),
							),
						),
						'contents'            => array(
							array(
								'role'  => 'user',
								'parts' => array(
									array( 'text' => $this->build_user_prompt( $post ) ),
								),
							),
						),
						'generationConfig'    => array(
							'maxOutputTokens' => self::MAX_OUTPUT_TOKENS,
							'thinkingConfig'  => array(
								// Dette er en ren skrivejobb uten behov for resonnering,
								// så vi slår av «thinking» for å bruke hele token-budsjettet
								// på selve teksten i stedet for intern tankekjede.
								'thinkingBudget' => 0,
							),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->wrap_transport_error( $response );
		}

		$data = $this->decode_response( $response, 'gemini' );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$parts = $data['candidates'][0]['content']['parts'] ?? array();
		$text  = '';

		foreach ( $parts as $part ) {
			if ( isset( $part['text'] ) ) {
				$text .= $part['text'];
			}
		}

		$finish_reason = $data['candidates'][0]['finishReason'] ?? '';

		if ( '' === $text && ( 'SAFETY' === $finish_reason || 'RECITATION' === $finish_reason ) ) {
			return new WP_Error(
				'ai_tidsreise_blocked',
				__( 'Gemini avviste forespørselen (innholdsfilter). Prøv å redigere innlegget eller prøv igjen.', 'ai-tidsreise' )
			);
		}

		if ( 'MAX_TOKENS' === $finish_reason ) {
			return new WP_Error(
				'ai_tidsreise_truncated',
				__( 'Svaret ble kuttet av fordi token-grensen ble nådd før teksten var ferdig. Prøv igjen, eller be om en kortere refleksjon.', 'ai-tidsreise' )
			);
		}

		return $this->finalize_text( $text );
	}

	/**
	 * Tolk en HTTP-respons som JSON, og gjør om HTTP-feilkoder til WP_Error.
	 *
	 * @param array<string,mixed> $response Respons fra wp_remote_post().
	 * @param string               $provider Leverandørnøkkel, brukt i feilmeldinger.
	 * @return array<string,mixed>|WP_Error
	 */
	private function decode_response( array $response, string $provider ) {
		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( $status < 200 || $status >= 300 ) {
			$message = is_array( $data )
				? (string) ( $data['error']['message'] ?? $data['error'] ?? '' )
				: '';

			return new WP_Error(
				'ai_tidsreise_api_error',
				sprintf(
					/* translators: 1: leverandørnøkkel, 2: HTTP-statuskode, 3: feilmelding fra API-et. */
					__( 'Feil fra %1$s-API (%2$d): %3$s', 'ai-tidsreise' ),
					$provider,
					$status,
					'' !== $message ? $message : __( 'ukjent feil', 'ai-tidsreise' )
				)
			);
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'ai_tidsreise_invalid_response',
				__( 'Kunne ikke tolke svaret fra AI-leverandøren.', 'ai-tidsreise' )
			);
		}

		return $data;
	}

	/**
	 * Rens opp en WP_Error fra transportlaget (f.eks. tidsavbrudd, DNS-feil).
	 *
	 * @param WP_Error $error Feilen fra wp_remote_post().
	 */
	private function wrap_transport_error( WP_Error $error ): WP_Error {
		return new WP_Error(
			'ai_tidsreise_transport_error',
			sprintf(
				/* translators: %s: underliggende feilmelding. */
				__( 'Kunne ikke nå AI-leverandøren: %s', 'ai-tidsreise' ),
				$error->get_error_message()
			)
		);
	}

	/**
	 * Trim og valider den genererte teksten før den returneres.
	 *
	 * @param string $text Rå tekst fra AI-leverandøren.
	 * @return string|WP_Error
	 */
	private function finalize_text( string $text ) {
		$text = trim( $this->strip_markdown( $text ) );

		if ( '' === $text ) {
			return new WP_Error(
				'ai_tidsreise_empty_response',
				__( 'AI-leverandøren returnerte et tomt svar.', 'ai-tidsreise' )
			);
		}

		return $text;
	}

	/**
	 * Fjern vanlig Markdown-syntaks AI-modellen kan slippe gjennom med,
	 * til tross for instruksen om ren løpende tekst i systemprompten.
	 *
	 * @param string $text Rå tekst fra AI-leverandøren.
	 */
	private function strip_markdown( string $text ): string {
		// Fet/kursiv skrift markert med stjerner eller understreker: **tekst**, *tekst*, __tekst__.
		$text = preg_replace( '/(\*\*|__)(.+?)\1/s', '$2', $text );
		$text = preg_replace( '/(?<!\w)([*_])(.+?)\1(?!\w)/s', '$2', $text );

		// Overskrifter markert med #.
		$text = preg_replace( '/^#{1,6}\s*/m', '', $text );

		// Punktlister markert med - eller *.
		$text = preg_replace( '/^[*-]\s+/m', '', $text );

		return $text;
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

		if ( function_exists( 'mb_strlen' ) && mb_strlen( $content ) > self::MAX_CONTENT_LENGTH ) {
			$content = mb_substr( $content, 0, self::MAX_CONTENT_LENGTH ) . ' […]';
		} elseif ( strlen( $content ) > self::MAX_CONTENT_LENGTH ) {
			$content = substr( $content, 0, self::MAX_CONTENT_LENGTH ) . ' […]';
		}

		return sprintf(
			"Innlegg publisert %s\nTittel: %s\n\nInnhold:\n%s",
			$date,
			$title,
			$content
		);
	}
}
