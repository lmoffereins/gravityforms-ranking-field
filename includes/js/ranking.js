/**
 * Gravity Forms Ranking Field Front script
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
			containment: 'parent',
			handle: '.ranking-sort, .item-label', // Use both elements as handle
			tolerance: 'pointer',

			// Act when list item dragging starts
			start: function( e, ui ) {

				// Define original list item counter
				ui.item.find( '.item-label' ).attr( 'data-counter', ui.placeholder.index() );
			},

			// Act when list item is being dragged into a new place
			change: function( e, ui ) {

				// Live update the dragged list item counter
				var index = ui.placeholder.index();
				ui.item.find( '.item-label' ).attr( 'data-counter', ( index > ui.item.index() ) ? index : index + 1 );
			},

			// Act when list item was dropped and the list order was changed
			update: function( e, ui ) {

				// Remove the dragged list item counter
				ui.item.find( '.item-label' ).removeAttr( 'data-counter' );

				/**
				 * Fire Conditional Logic trigger
				 */
				var clFields = ui.item.parent().attr( 'data-clfields' );
				if ( clFields ) {
					// Send the form ID and an array of field IDs to apply their rules
					gf_apply_rules( ui.item.parents( 'form[id^="gform_"]' ).attr( 'id' ).match( /\d+/ )[0], clFields.split( ',' ) );
				}
			}

		// Enable ranking by clicking up/down arrows
		}).find( 'i.ranking-up, i.ranking-down' ).on( 'click', function() {
			var $handle = $(this), 
			    $item   = $handle.parent();

			// Up: switch with previous item
			if ( $handle.is( '.ranking-up' ) && ! $item.is( ':first-of-type' ) ) {
				$item.insertBefore( $item.prev() );

			// Down: switch with next item
			} else if ( $handle.is( '.ranking-down' ) && ! $item.is( ':last-of-type' ) ) {
				$item.insertAfter( $item.next() );
			}
		});
	});

	/**
	 * Conditional Logic
	 */

	gform.addFilter( 'gform_is_value_match', 'EvalConditionalLogicRule', 10, 3 );

	/**
	 * Filter the result of the Conditional Logic rule for Ranking fields
	 *
	 * @since 1.2.0
	 * 
	 * @param {bool} match The rule's result
	 * @param {int} formId The current form ID
	 * @param {object} rule Rule data
	 * @return {bool} The rule's result
	 */
	EvalConditionalLogicRule = function( match, formId, rule ) {
		var $field = $( '#gform_fields_' + formId + ' #field_' + formId + '_' + rule.fieldId ),
		    choices = [];

		// When this rule concerns a Ranking field
		if ( $field && $field.hasClass( settings.type + '-field' ) ) {

			// Get the field choices' values. Assuming all hidden inputs are ranking choices.
			$field.find( 'li input[type="hidden"]' ).each( function( i, el ) {
				choices.push( el.value );
			});

			// Check the target value for the expected position
			match = parseInt( rule.operator ) === choices.indexOf( rule.value );
		}

		return match;
	};

}( jQuery, window ));
