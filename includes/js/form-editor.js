/**
 * Gravity Forms Ranking Field Form Editor script
 *
 * @package Gravity Forms Ranking Field
 * @subpackage Administration
 */
( function( $, window ) {
	var l10n, settings;

	// Link any localized strings and settings
	l10n = typeof _gfRankingFieldL10n === 'undefined' ? {} : _gfRankingFieldL10n;
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

		// Set field label default to 'Untitled'
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

		// Set default arrowType
		if ( ! field[ settings.arrowTypeSetting ] ) {
			field[ settings.arrowTypeSetting ] = settings.defaultArrowType;
		}

		// Store field values as individual inputs
		field.inputs = [];
		for ( var i = 0; i < field.choices.length; i++ ) {
			field.inputs.push( new Input( field.id + '.' + ( i + 1 ), field.choices[ i ].text ) );
		}

		return field;
	};

	/**
	 * GF uses UpdateFieldChoices() to translate the choice settings back
	 * to the field's preview. But since UpdateFieldChoices() does not allow 
	 * for field-type specific logic or hooking (only hardcoded 'select', 
	 * 'checkbox', 'radio' or 'list' types can have field choices), we here 
	 * do declare our own implementation.
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
	 * @param  {object} $          jQuery
	 * @param  {object} htmlMethod jQuery's original html method
	 * @return {object}            jQuery
	 */
	( function( $, htmlMethod ) {

		// Override the core html method in the jQuery object
		$.fn.html = function() {
			var $this = $(this);

			// When applied to the current Ranking field
			if ( $this.parents( '.field_selected' ).length && $this.hasClass( 'gfield_' + settings.type ) ) {

				// Get the current field object, define local variable(s)
				var field = GetSelectedField(),
				    html = '';

				// Reset field inputs
				field.inputs = [];

				// Walk the field's choices, which are up to date at this point
				for ( var i = 0; i < field.choices.length; i++ ) {

					// Regenerate inputs
					field.inputs.push( new Input( field.id + '.' + ( i + 1 ), field.choices[ i ].text ) );

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
	ParseChoiceTemplate = function( choice, field ) {
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
					replacement = 'input_' + field.id + '.' + ( field.choices.map( function( i ) { return i.value; } ).indexOf( choice.value ) + 1 );
					break;
			}

			// Replace all instances of item in the template
			tmpl = tmpl.replace( new RegExp( '{{' + item + '}}', 'g' ), replacement );
		});

		return tmpl;
	};

	// On document ready
	jQuery(document).ready( function( $ ) {

		// Define the Ranking field's settings
		fieldSettings[ settings.type ] = [

			// Default settings
			'.conditional_logic_field_setting',
			'.label_setting',
			'.admin_label_setting',
			'.choices_setting',
			'.visibility_setting',
			'.description_setting',
			'.css_class_setting',

			// Ranking settings
			'.ranking_randomize_setting',
			'.ranking_invert_setting',
			'.ranking_arrow_type_setting'
		].join();
	});

	/**
	 * Conditional Logic
	 */

	gform.addFilter( 'gform_is_conditional_logic_field',     'EnableConditionalLogic',    10, 2 );
	gform.addFilter( 'gform_conditional_logic_operators',    'ConditionalLogicOperators', 10, 3 );
	gform.addFilter( 'gform_conditional_logic_values_input', 'ConditionalLogicValues',    10, 5 );

	/**
	 * Enable Conditional Logic for Ranking fields
	 *
	 * @since 1.2.0
	 *
	 * @param {bool} enabled Whether the field has Conditional Logic enabled
	 * @param {object} field The evaluated field data
	 * @return {bool} Enabled
	 */
	EnableConditionalLogic = function( enabled, field ) {
		return ( field.type === settings.type ) ? true : enabled;
	};

	/**
	 * Filter the Conditional Logic operators for Ranking fields
	 *
	 * @since 1.2.0
	 * 
	 * @param {object} operators Collection of operators
	 * @param {string} objectType Conditional Logic object type
	 * @param {string} fieldId The field ID of the current rule
	 * @return {object} Collection of operators
	 */
	ConditionalLogicOperators = function( operators, objectType, fieldId ) {
		var field = GetFieldById( fieldId ), stringKey, l10nStringKey;

		// When these are a Ranking field's operators
		if ( 'field' === objectType && field && field.type === settings.type ) {

			// Clean operators collection
			operators = {};

			// Define operators. Keys are operator IDs, values are string keys in gf_vars global
			for ( var i = 1; field.choices.length >= i; i++ ) {

				// Define l10n string to use
				if ( i == 1 ) {
					l10nStringKey = 'rankingRuleOperatorFirst';
				} else if ( i == field.choices.length ) {
					l10nStringKey = 'rankingRuleOperatorLast';
				} else {
					l10nStringKey = 'rankingRuleOperatorNum';
				}

				// Define operator's stringKey
				stringKey = 'rankingRuleOperator' + i;

				// Set operator
				operators[ i - 1 ] = stringKey;

				// Add translatable string to GF's string collection
				gf_vars[ stringKey ] = l10n[ l10nStringKey ].replace( '%d', i );
			};
		}

		return operators;
	};

	/**
	 * Filter the Conditional Logic values for Ranking fields
	 *
	 * @since 1.2.0
	 *
	 * @uses GetRuleValuesDropdown()
	 * 
	 * @param {string} html Values input element
	 * @param {string} objectType Conditional Logic object type
	 * @param {int} ruleIndex Index of the current rule
	 * @param {int} fieldId The field ID of the current rule
	 * @param {string} selectedValue The selected rule's value
	 * @return {string} Values input element
	 */
	ConditionalLogicValues = function( html, objectType, ruleIndex, fieldId, selectedValue ) {
		var field = GetFieldById( fieldId );

		// When these are a Ranking field's values
		if ( 'field' === objectType && field && field.type === settings.type ) {
			html = GetRuleValuesDropDown( field.choices, objectType, ruleIndex, selectedValue, false );
		}

		return html;
	};

}( jQuery, window ) );
