/**
 * Gravity Forms Field Ranking Editor script
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
	 * executed by GF in SetDefaultValues().
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
			for ( var i = 0; i < settings.defaultChoices.length; i++ ) {
				field.choices.push( new Choice( settings.defaultChoices[ i ].text, settings.defaultChoices[ i ].value ) );
			}
		}

		// Field values are stored as a single input
		field.inputs = null;

		return field;
	};

	/**
	 * GF uses UpdateFieldChoices() to translate the choice settings back
	 * to the field's preview. But since UpdateFieldChoices() does not allow 
	 * for field-type specific logic or hooking (only hardcoded 'select', 
	 * 'checkbox', 'radio' or 'list'), we here do declare our own implementation.
	 */

	/**
	 * Override jQuery's html method to manipulate UpdateFieldChoices() for 
	 * Ranking fields
	 *
	 * The logic is based on the 'checkbox' field type.
	 * 
	 * @since 1.0.0
	 *
	 * @see UpdateFieldChoices() 
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
					// Only display the first 5 choices
					if ( i < 5 ) {
						html += ParseChoiceTemplate( field.choices[i], field );
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
	 * @param {object} field Field data
	 * @return {string} Choice HTML
	 */
	function ParseChoiceTemplate( choice, field ) {
		var tmpl = settings.choiceTemplate, replacement;

		// Walk all replacements
		$.each( [ 'text', 'value', 'name' ], function( index, item ) {

			// Define replacement value
			switch ( item ) {
				case 'text':
				case 'value':
					replacement = typeof choice[item] !== 'undefined' ? choice[item] : '';
					break;
				case 'name':
					replacement = 'input_' + field.id;
					break;
			}

			// Replace all instances of item in the template
			tmpl = tmpl.replace( new RegExp( '{{' + item + '}}', 'g' ), replacement );
		});

		return tmpl;
	}

	// On document ready
	jQuery(document).ready( function( $ ) {
		/**
		 * @todo Define other settings
		 *        - Select arrow type
		 */

		// Define the Ranking fields' settings
		fieldSettings[ settings.type ] = fieldSettings['checkbox'] + ', .ranking_randomize_setting';
	});

}( jQuery, window ) );
