/**
 * Gravity Forms Field Ranking scripts
 *
 * @package Gravity Forms Field Ranking
 * @subpackage Administration
 */
( function( $, window ) {
	var l10n, settings;

	// Link any localized strings and settings
	l10n = typeof _gfFieldRankingL10n === 'undefined' ? {} : _gfFieldRankingL10n;
	settings = l10n.settings || {};

	/**
	 * Set the default values for the given Ranking field
	 *
	 * The logic is based on the 'checkbox' field type. 
	 * The function is defined within the global window scope and is
	 * used by GF in SetDefaultValues().
	 *
	 * @since 1.0.0
	 *
	 * @see SetDefaultValues()
	 *
	 * @param {object} field Field data
	 * @return {object} field Field data
	 */
	window[ 'SetDefaultValues_' + settings.type ] = function( field ) {

		// Default to 'Untitled' field label
		if ( ! field.label ) {
			field.label = l10n.labelUntitled;
		}

		// Set default field choices
		if ( ! field.choices ) {
			field.choices = [];
			for ( var i = 1; i <= settings.defaultChoices.length; i++ ) {
				field.choices.push( new Choice( settings.defaultChoices[i - 1].text, settings.defaultChoices[i - 1].value ) );
			}
		}

		// Setup field inputs
		field.inputs = [];
		for ( var i = 1; i <= field.choices.length; i++ ) {
			field.inputs.push( new Input( field.id + '.' + i, field.choices[i - 1].text ) );
		}

		return field;
	};

	/**
	 * GF uses UpdateFieldChoices() to translate the choice settings back
	 * to the field's preview. But since UpdateFieldChoices() does not allow 
	 * for field-type specific logic or hooking (only hardcoded 'select', 
	 * 'checkbox', 'radio' or 'list'), we here do declare our own implementation.
	 */

	/**
	 * Override jQuery's html method to manipulate UpdateFieldChoices() for Ranking fields
	 *
	 * @since 1.0.0
	 *
	 * @see UpdateFieldChoices() Mimicing logic for the 'checkbox' field type
	 * 
	 * @param  {object} $    jQuery
	 * @param  {object} html jQuery's original html method
	 * @return {object}      jQuery
	 */
	( function( $, htmlMethod ) {

		// Override the core html method in the jQuery object
		$.fn.html = function() {
			$this = $(this);

			// When applied to the current Ranking field
			if ( $this.parents( '.field_selected' ).length && $this.hasClass( 'gfield_' + settings.type ) ) {

				// Get the current field object, define local variable(s)
				var field = GetSelectedField(),
				    html = '', i;

				// Walk the field's choices, which are up to date at this point
				for ( i = 0; i < field.choices.length; i++ ) {
					field.inputs.push( new Input( field.id + '.' + ( i + 1 ), field.choices[i].text ) );

					// Only display the first 5 choices
					if ( i < 5 ) {
						html += ParseChoiceTemplate( field.choices[i] );
					}
				}

				// Append notification of 5+ choices
				if ( field.choices.length > 5 ) {
					html += '<li class="gchoice_total">' + gf_vars['editToViewAll'].replace( '%d', field.choices.length ) + '</li>';
				}

				// Set the replacement to the generated field preview
				arguments[ 0 ] = html;
			}

			// Execute the original method with augmented arguments
			return htmlMethod.apply( this, arguments );
		};

	}( $, $.fn.html ) );

	/**
	 * Return a parsed choice template
	 *
	 * @since 1.0.0
	 * 
	 * @param {object} choice Choice data
	 * @return {string} Choice HTML
	 */
	function ParseChoiceTemplate( choice ) {
		var tmpl = settings.choiceTemplate;

		// Walk all replacements
		$.each( [ 'text', 'value' ], function( index, item ) {

			// Replace all instances of item in the template
			tmpl = tmpl.replace( 
				new RegExp( '{{' + item + '}}', 'g' ), 
				typeof choice[item] !== 'undefined' ? choice[item] : ''
			);
		});

		return tmpl;
	}

	// On document ready
	jQuery(document).ready( function( $ ) {

		// Define the Ranking fields' settings
		fieldSettings[ settings.type ] = fieldSettings['checkbox'];
	});

}( jQuery, window ) );
