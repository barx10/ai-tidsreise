/**
 * AI Tidsreise - meta-boks: generer refleksjon/idé og lagre via AJAX.
 *
 * @package AI_Tidsreise
 */

( function ( $ ) {
	'use strict';

	$( function () {
		if ( typeof aiTidsreiseMetabox === 'undefined' ) {
			return;
		}

		var $statusLabel = $( '#ai-tidsreise-status-label' );

		/**
		 * Hent gjeldende innhold i refleksjons-editoren, uavhengig av om
		 * den visuelle (TinyMCE) eller tekst-visningen er aktiv.
		 */
		function getRefleksjonValue() {
			if ( typeof tinymce !== 'undefined' && tinymce.get( 'ai_tidsreise_refleksjon' ) && ! tinymce.get( 'ai_tidsreise_refleksjon' ).isHidden() ) {
				return tinymce.get( 'ai_tidsreise_refleksjon' ).getContent();
			}

			return $( '#ai_tidsreise_refleksjon' ).val();
		}

		/**
		 * Sett innhold i refleksjons-editoren, uavhengig av aktiv visning.
		 */
		function setRefleksjonValue( value ) {
			if ( typeof tinymce !== 'undefined' && tinymce.get( 'ai_tidsreise_refleksjon' ) && ! tinymce.get( 'ai_tidsreise_refleksjon' ).isHidden() ) {
				tinymce.get( 'ai_tidsreise_refleksjon' ).setContent( value );
			} else {
				$( '#ai_tidsreise_refleksjon' ).val( value );
			}
		}

		// Generer refleksjon.
		var $generateButton = $( '#ai-tidsreise-generate-button' );
		var $generateSpinner = $( '#ai-tidsreise-spinner' );
		var $generateFeedback = $( '#ai-tidsreise-feedback' );

		$generateButton.on( 'click', function () {
			if ( $generateButton.prop( 'disabled' ) ) {
				return;
			}

			$generateButton.prop( 'disabled', true );
			$generateSpinner.addClass( 'is-active' );
			$generateFeedback
				.removeClass( 'is-error is-success' )
				.text( aiTidsreiseMetabox.i18n.generating );

			$.post( aiTidsreiseMetabox.ajaxUrl, {
				action: 'ai_tidsreise_generate',
				nonce: aiTidsreiseMetabox.nonce,
				post_id: aiTidsreiseMetabox.postId
			} )
				.done( function ( response ) {
					if ( response && response.success ) {
						setRefleksjonValue( response.data.refleksjon );
						$generateFeedback.addClass( 'is-success' ).text( aiTidsreiseMetabox.i18n.success );
						$statusLabel.text( aiTidsreiseMetabox.i18n.statusGenerert );
					} else {
						var message = response && response.data && response.data.message
							? response.data.message
							: aiTidsreiseMetabox.i18n.error;
						$generateFeedback.addClass( 'is-error' ).text( message );
					}
				} )
				.fail( function () {
					$generateFeedback.addClass( 'is-error' ).text( aiTidsreiseMetabox.i18n.error );
				} )
				.always( function () {
					$generateButton.prop( 'disabled', false );
					$generateSpinner.removeClass( 'is-active' );
				} );
		} );

		// Generer idé til neste innlegg (kun når forfatteren ber om det).
		var $naesteIdButton = $( '#ai-tidsreise-naeste-id-button' );
		var $naesteIdSpinner = $( '#ai-tidsreise-naeste-id-spinner' );
		var $naesteIdFeedback = $( '#ai-tidsreise-naeste-id-feedback' );

		$naesteIdButton.on( 'click', function () {
			if ( $naesteIdButton.prop( 'disabled' ) ) {
				return;
			}

			$naesteIdButton.prop( 'disabled', true );
			$naesteIdSpinner.addClass( 'is-active' );
			$naesteIdFeedback
				.removeClass( 'is-error is-success' )
				.text( aiTidsreiseMetabox.i18n.generatingNaesteId );

			$.post( aiTidsreiseMetabox.ajaxUrl, {
				action: 'ai_tidsreise_generate_naeste_id',
				nonce: aiTidsreiseMetabox.nonce,
				post_id: aiTidsreiseMetabox.postId
			} )
				.done( function ( response ) {
					if ( response && response.success ) {
						$( '#ai_tidsreise_naeste_id' ).val( response.data.naesteId );
						$naesteIdFeedback.addClass( 'is-success' ).text( aiTidsreiseMetabox.i18n.successNaesteId );
					} else {
						var message = response && response.data && response.data.message
							? response.data.message
							: aiTidsreiseMetabox.i18n.errorNaesteId;
						$naesteIdFeedback.addClass( 'is-error' ).text( message );
					}
				} )
				.fail( function () {
					$naesteIdFeedback.addClass( 'is-error' ).text( aiTidsreiseMetabox.i18n.errorNaesteId );
				} )
				.always( function () {
					$naesteIdButton.prop( 'disabled', false );
					$naesteIdSpinner.removeClass( 'is-active' );
				} );
		} );

		// Lagre refleksjon, idé og synlighet umiddelbart, uten å publisere/oppdatere hele innlegget.
		var $saveButton = $( '#ai-tidsreise-save-button' );
		var $saveSpinner = $( '#ai-tidsreise-save-spinner' );
		var $saveFeedback = $( '#ai-tidsreise-save-feedback' );

		$saveButton.on( 'click', function () {
			if ( $saveButton.prop( 'disabled' ) ) {
				return;
			}

			$saveButton.prop( 'disabled', true );
			$saveSpinner.addClass( 'is-active' );
			$saveFeedback
				.removeClass( 'is-error is-success' )
				.text( aiTidsreiseMetabox.i18n.saving );

			$.post( aiTidsreiseMetabox.ajaxUrl, {
				action: 'ai_tidsreise_save',
				nonce: aiTidsreiseMetabox.nonce,
				post_id: aiTidsreiseMetabox.postId,
				refleksjon: getRefleksjonValue(),
				naeste_id: $( '#ai_tidsreise_naeste_id' ).val(),
				synlig: $( '#ai_tidsreise_synlig' ).is( ':checked' ) ? 1 : 0
			} )
				.done( function ( response ) {
					if ( response && response.success ) {
						$saveFeedback.addClass( 'is-success' ).text( aiTidsreiseMetabox.i18n.saved );
						$statusLabel.text( aiTidsreiseMetabox.i18n.statusGenerert );
					} else {
						var message = response && response.data && response.data.message
							? response.data.message
							: aiTidsreiseMetabox.i18n.errorSave;
						$saveFeedback.addClass( 'is-error' ).text( message );
					}
				} )
				.fail( function () {
					$saveFeedback.addClass( 'is-error' ).text( aiTidsreiseMetabox.i18n.errorSave );
				} )
				.always( function () {
					$saveButton.prop( 'disabled', false );
					$saveSpinner.removeClass( 'is-active' );
				} );
		} );
	} );
} )( jQuery );
