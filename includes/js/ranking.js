/**
 * Gravity Forms Field Ranking script
 *
 * @package Gravity Forms Field Ranking
 * @subpackage Administration
 */
( function( $, window ) {
	var l10n, settings;

	// Link any localized strings and settings
	l10n = typeof _gfFieldRankingL10n === 'undefined' ? {} : _gfFieldRankingL10n;
	settings = l10n.settings || {};

	// On document load
	jQuery(document).ready( function( $ ) {

		// Enable drag-drop per list item
		$( '.gfield_' + settings.type ).sortable({
			axis: 'y',
			handle: 'li',
		});

	});
}( jQuery, window ));
