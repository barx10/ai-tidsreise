<?php
/**
 * Avinstalleringsrutine for AI Tidsreise.
 *
 * Fjerner pluginens innstillinger og post meta ved avinstallering.
 *
 * @package AI_Tidsreise
 */

declare( strict_types = 1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ai_tidsreise_settings' );

global $wpdb;

$meta_keys = array(
	'_etterpaklokskap_refleksjon',
	'_etterpaklokskap_status',
	'_etterpaklokskap_synlig',
);

foreach ( $meta_keys as $meta_key ) {
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ) );
}
