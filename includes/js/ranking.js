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
			axis: 'y',
			handle: '.item-label',
		});

	});
}( jQuery, window ));
