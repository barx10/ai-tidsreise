/**
 * AI Tidsreise - meta-boks: generer refleksjon via AJAX.
 *
 * @package AI_Tidsreise
 */

( function ( $ ) {
	'use strict';

	$( function () {
		var $button = $( '#ai-tidsreise-generate-button' );
		var $spinner = $( '#ai-tidsreise-spinner' );
		var $feedback = $( '#ai-tidsreise-feedback' );
		var $statusLabel = $( '#ai-tidsreise-status-label' );

		if ( ! $button.length || typeof aiTidsreiseMetabox === 'undefined' ) {
			return;
		}

		$button.on( 'click', function () {
			if ( $button.prop( 'disabled' ) ) {
				return;
			}

			$button.prop( 'disabled', true );
			$spinner.addClass( 'is-active' );
			$feedback
				.removeClass( 'is-error is-success' )
				.text( aiTidsreiseMetabox.i18n.generating );

			$.post( aiTidsreiseMetabox.ajaxUrl, {
				action: 'ai_tidsreise_generate',
				nonce: aiTidsreiseMetabox.nonce,
				post_id: aiTidsreiseMetabox.postId
			} )
				.done( function ( response ) {
					if ( response && response.success ) {
						if ( typeof tinymce !== 'undefined' && tinymce.get( 'ai_tidsreise_refleksjon' ) && ! tinymce.get( 'ai_tidsreise_refleksjon' ).isHidden() ) {
							tinymce.get( 'ai_tidsreise_refleksjon' ).setContent( response.data.refleksjon );
						} else {
							$( '#ai_tidsreise_refleksjon' ).val( response.data.refleksjon );
						}

						$( '#ai_tidsreise_naeste_id' ).val( response.data.naesteId );

						$feedback.addClass( 'is-success' ).text( aiTidsreiseMetabox.i18n.success );
						$statusLabel.text( aiTidsreiseMetabox.i18n.statusGenerert );
					} else {
						var message = response && response.data && response.data.message
							? response.data.message
							: aiTidsreiseMetabox.i18n.error;
						$feedback.addClass( 'is-error' ).text( message );
					}
				} )
				.fail( function () {
					$feedback.addClass( 'is-error' ).text( aiTidsreiseMetabox.i18n.error );
				} )
				.always( function () {
					$button.prop( 'disabled', false );
					$spinner.removeClass( 'is-active' );
				} );
		} );
	} );
} )( jQuery );
