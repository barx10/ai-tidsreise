<?php
/**
 * Rate limiting for AI-API-kall.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Begrenser antall AI-API-kall til maks 30 per minutt, delt for hele pluginen.
 */
class AI_Tidsreise_Rate_Limiter {

	/**
	 * Transient-nøkkel som holder tellingen for gjeldende vindu.
	 */
	private const TRANSIENT_KEY = 'ai_tidsreise_rate_limit';

	/**
	 * Maks antall kall per vindu.
	 */
	private const MAX_CALLS = 30;

	/**
	 * Vindusstørrelse i sekunder.
	 */
	private const WINDOW_SECONDS = 60;

	/**
	 * Sjekk om et nytt kall er tillatt innenfor gjeldende tidsvindu.
	 */
	public static function is_allowed(): bool {
		$count = (int) get_transient( self::TRANSIENT_KEY );

		return $count < self::MAX_CALLS;
	}

	/**
	 * Registrer at et kall er utført, og øk telleren.
	 */
	public static function register_call(): void {
		$count = (int) get_transient( self::TRANSIENT_KEY );

		if ( 0 === $count ) {
			set_transient( self::TRANSIENT_KEY, 1, self::WINDOW_SECONDS );
			return;
		}

		set_transient( self::TRANSIENT_KEY, $count + 1, self::WINDOW_SECONDS );
	}

	/**
	 * Hent antall gjenværende kall i inneværende vindu.
	 */
	public static function get_remaining_calls(): int {
		$count = (int) get_transient( self::TRANSIENT_KEY );

		return max( 0, self::MAX_CALLS - $count );
	}
}
