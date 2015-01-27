/**
 * Gravity Forms Ranking Field script
 *
 * @package Gravity Forms Ranking Field
 * @subpackage Administration
 */
( function( $, window ) {
	var l10n, settings;

	// Link any localized strings and settings
	l10n = typeof _gfRankingFieldL10n === 'undefined' ? {} : _gfRankingFieldL10n;
	settings = l10n.settings || {};

	// On document load
	jQuery(document).ready( function( $ ) {

		// Enable drag-drop per list item
		$( '.gfield_' + settings.type ).sortable({
			items: 'li',
			axis: 'y',
			handle: 'i:nth-child(3), .item-label',
			tolerance: 'pointer'

		// Enable list item sorting by clicking up/down
		}).find( 'i:nth-child(1), i:nth-child(2)' ).on( 'click', function() {
			var $handle = $(this), 
			    $item   = $handle.parent();

			// UP: switching with previous item
			if ( $handle.is(':nth-child(1)') && ! $item.is(':first-of-type') ) {
				$item.insertBefore( $item.prev() );

			// Down: switching with next item
			} else if ( $handle.is(':nth-child(2)') && ! $item.is(':last-of-type') ) {
				$item.insertAfter( $item.next() );
			}
		});

	});
}( jQuery, window ));
