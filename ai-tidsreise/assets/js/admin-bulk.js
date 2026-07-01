/**
 * AI Tidsreise - innleggsliste: bulk-generer refleksjoner i kø.
 *
 * Fanger opp bulk-handlingen "Generer 2026-refleksjon", og kjører
 * AJAX-kallene sekvensielt med en pause mellom hvert kall for å
 * holde seg innenfor rate-limiten (maks 30 kall/min på serveren).
 *
 * @package AI_Tidsreise
 */

( function ( $ ) {
	'use strict';

	$( function () {
		if ( typeof aiTidsreiseBulk === 'undefined' ) {
			return;
		}

		var $forms = $( '#posts-filter' );
		var $progress = $( '#ai-tidsreise-bulk-progress' );

		$forms.on( 'submit', function ( event ) {
			var action = $( this ).find( 'select[name="action"]' ).val();
			var action2 = $( this ).find( 'select[name="action2"]' ).val();

			if ( action !== aiTidsreiseBulk.bulkAction && action2 !== aiTidsreiseBulk.bulkAction ) {
				return;
			}

			var postIds = $( this )
				.find( 'input[name="post[]"]:checked' )
				.map( function () {
					return $( this ).val();
				} )
				.get();

			if ( ! postIds.length ) {
				return;
			}

			event.preventDefault();
			runQueue( postIds );
		} );

		function runQueue( postIds ) {
			var total = postIds.length;
			var completed = 0;
			var errors = [];

			$progress.show().find( 'p' ).text(
				aiTidsreiseBulk.i18n.progress.replace( '%1$d', completed ).replace( '%2$d', total )
			);

			function next() {
				if ( ! postIds.length ) {
					var message = aiTidsreiseBulk.i18n.done;

					if ( errors.length ) {
						message += ' (' + errors.length + ' feilet)';
					}

					$progress.find( 'p' ).text( message );
					return;
				}

				var postId = postIds.shift();

				$.post( aiTidsreiseBulk.ajaxUrl, {
					action: 'ai_tidsreise_generate',
					nonce: aiTidsreiseBulk.nonce,
					post_id: postId
				} )
					.fail( function () {
						errors.push( postId );
					} )
					.always( function ( response ) {
						if ( response && response.success === false ) {
							errors.push( postId );
						}

						completed++;
						$progress.find( 'p' ).text(
							aiTidsreiseBulk.i18n.progress.replace( '%1$d', completed ).replace( '%2$d', total )
						);

						setTimeout( next, aiTidsreiseBulk.delayMs );
					} );
			}

			next();
		}
	} );
} )( jQuery );
