<?php
/**
 * Kryptering og dekryptering av API-nøkler.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Håndterer symmetrisk kryptering av sensitive innstillinger, som API-nøkler.
 *
 * Bruker openssl med en nøkkel avledet fra WordPress sine AUTH_KEY/AUTH_SALT-
 * konstanter, slik at nøkkelen aldri lagres i klartekst i databasen.
 */
class AI_Tidsreise_Encryption {

	/**
	 * Krypteringsmetode brukt av openssl.
	 */
	private const CIPHER = 'aes-256-cbc';

	/**
	 * Krypter en tekststreng.
	 *
	 * @param string $value Verdi som skal krypteres.
	 * @return string Base64-kodet krypteringstekst, eller tom streng hvis input er tom.
	 */
	public static function encrypt( string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );
		$iv        = openssl_random_pseudo_bytes( $iv_length );

		$encrypted = openssl_encrypt( $value, self::CIPHER, self::get_key(), 0, $iv );

		if ( false === $encrypted ) {
			return '';
		}

		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Dekrypter en tekststreng kryptert med encrypt().
	 *
	 * @param string $value Kryptert, base64-kodet verdi.
	 * @return string Dekryptert verdi, eller tom streng ved feil.
	 */
	public static function decrypt( string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		$decoded = base64_decode( $value, true );

		if ( false === $decoded ) {
			return '';
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );
		$iv        = substr( $decoded, 0, $iv_length );
		$encrypted = substr( $decoded, $iv_length );

		$decrypted = openssl_decrypt( $encrypted, self::CIPHER, self::get_key(), 0, $iv );

		return false === $decrypted ? '' : $decrypted;
	}

	/**
	 * Avled en krypteringsnøkkel fra WordPress sine saltverdier.
	 */
	private static function get_key(): string {
		$secret = defined( 'AUTH_KEY' ) && AUTH_KEY ? AUTH_KEY : 'ai-tidsreise-fallback-key';
		$salt   = defined( 'AUTH_SALT' ) && AUTH_SALT ? AUTH_SALT : 'ai-tidsreise-fallback-salt';

		return hash( 'sha256', $secret . $salt, true );
	}
}
