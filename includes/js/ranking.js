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

	// On document ready
	jQuery(document).ready( function( $ ) {

		// Enable ranking by drag-dropping list items
		$( '.gfield_' + settings.type ).sortable({
			items: 'li',
			axis: 'y',
			handle: '.ranking-sort, .item-label',
			tolerance: 'pointer',

			// Live-update the dragged list item counter
			start: function( e, ui ) {
				ui.item.find('.item-label').attr( 'data-counter', ui.placeholder.index() );
			},
			change: function( e, ui ) {
				var index = ui.placeholder.index();
				ui.item.find('.item-label').attr( 'data-counter', ( index > ui.item.index() ) ? index : index + 1 );
			},
			update: function( e, ui ) {
				ui.item.find('.item-label').removeAttr( 'data-counter' );
			}

		// Enable ranking by clicking up(1)/down(2) arrows
		}).filter( ':has(:not(.icon-sort))' ).find( 'i.ranking-up, i.ranking-down' ).on( 'click', function() {
			var $handle = $(this), 
			    $item   = $handle.parent();

			// Up: switching with previous item
			if ( $handle.is( '.ranking-up' ) && ! $item.is( ':first-of-type' ) ) {
				$item.insertBefore( $item.prev() );

			// Down: switching with next item
			} else if ( $handle.is( '.ranking-down' ) && ! $item.is( ':last-of-type' ) ) {
				$item.insertAfter( $item.next() );
			}
		});
	});

}( jQuery, window ));
