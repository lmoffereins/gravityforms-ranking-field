<?php

/**
 * The Gravity Forms Ranking Field Plugin
 * 
 * @package Gravity Forms Ranking Field
 * @subpackage Main
 */

/**
 * Plugin Name:       Gravity Forms Ranking Field
 * Description:       Adds a field to Gravity Forms for ranking choices
 * Plugin URI:        https://github.com/lmoffereins/gravityforms-ranking-field/
 * Version:           1.2.0
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins/
 * Text Domain:       gravityforms-ranking-field
 * Domain Path:       /languages/
 * GitHub Plugin URI: lmoffereins/gravityforms-ranking-field
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GravityForms_Ranking_Field' ) ) :
/**
 * The main plugin class
 *
 * @since 1.0.0
 */
final class GravityForms_Ranking_Field {

	/**
	 * The plugin's field type name
	 * @var string
	 */
	protected $type = 'ranking';

	/**
	 * Randomize field setting key
	 * @var string
	 */
	protected $randomize_setting = 'rankingRandomize';

	/**
	 * Invert field setting key
	 * @var string
	 */
	protected $invert_setting = 'rankingInvert';

	/**
	 * Arrow Type field setting key
	 * @var string
	 */
	protected $arrow_type_setting = 'rankingArrowType';

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses GravityForms_Ranking_Field::setup_globals()
	 * @uses GravityForms_Ranking_Field::setup_actions()
	 * @return The single GravityForms_Ranking_Field
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new GravityForms_Ranking_Field;
			$instance->setup_globals();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once
	 */
	private function __construct() { /* Nothing to do */ }

	/** Private methods *************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/
		
		$this->version      = '1.2.0';
		
		/** Paths *************************************************************/
		
		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );
		
		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes'  );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes'  );
		
		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );
		
		/** Misc **************************************************************/
		
		$this->extend       = new stdClass();
		$this->domain       = 'gravityforms-ranking-field';
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Bail when GF is not active
		if ( ! class_exists( 'GFForms' ) )
			return;

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		
		// Add field button
		add_filter( 'gform_add_field_buttons', array( $this, 'add_field_button' ) );

		// Set field type title
		add_filter( 'gform_field_type_title', array( $this, 'set_field_title' ) );

		// Render the field
		add_filter( 'gform_field_input', array( $this, 'render_field' ), 10, 5 );

		// Add editor scripts
		add_action( 'gform_editor_js', array( $this, 'admin_scripts' ) );

		// Add form scripts
		add_action( 'gform_enqueue_scripts', array( $this, 'form_scripts' ), 10, 2 );

		// Field classes
		add_filter( 'gform_field_css_class', array( $this, 'field_classes' ), 10, 2 );

		// Sanitize value
		add_filter( 'gform_save_field_value', array( $this, 'sanitize_input_value' ), 10, 5 );

		// Evaluate conditional logic
		add_filter( 'gform_is_value_match', array( $this, 'eval_conditional_logic_rule' ), 10, 5 );

		// Add field settings
		add_action( 'gform_field_standard_settings', array( $this, 'register_field_settings' ), 10, 2 );

		// Add tooltips
		add_filter( 'gform_tooltips', array( $this, 'add_tooltips' ) );

		// Display field value on entry details page
		add_filter( 'gform_entry_field_value', array( $this, 'display_field_value'    ), 10, 4 );
	}

	/** Public methods **************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the plugin folder will be
	 * removed on plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.2.0
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the textdomain
	 */
	public function load_textdomain() {
	
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );
	
		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/gravityforms-ranking-field/' . $mofile;
	
		// Look in global /wp-content/languages/gravityforms-ranking-field folder
		load_textdomain( $this->domain, $mofile_global );
	
		// Look in local /wp-content/plugins/gravityforms-ranking-field/languages/ folder
		load_textdomain( $this->domain, $mofile_local );
	
		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/**
	 * Return whether the given field is a Ranking field
	 *
	 * @since 1.0.0
	 * 
	 * @param array|string $field Field data or field type
	 * @return bool This is a Ranking field
	 */
	public function is_ranking_field( $field ) {
		return $this->type === ( isset( $field['type'] ) ? $field['type'] : $field );
	}

	/**
	 * Return whether the given form contains a Ranking field
	 *
	 * @since 1.0.0
	 *
	 * @uses GravityForms_Ranking_Field::get_form_ranking_fields()
	 * 
	 * @param object|int $form Form data or form ID
	 * @return bool Form contains a Ranking field
	 */
	public function has_form_ranking_fields( $form ) {
		return (bool) $this->get_form_ranking_fields( $form );
	}

	/**
	 * Return the given form's Ranking fields
	 *
	 * @since 1.0.0
	 * 
	 * @uses GFFormsModel::get_form_meta()
	 * 
	 * @param object|int $form Form data or form ID
	 * @return array Form field ids or empty array when no Ranking fields found
	 */
	public function get_form_ranking_fields( $form ) {

		// Get the form by form ID
		if ( ! is_array( $form ) ) {
			$form = GFFormsModel::get_form_meta( (int) $form );
		}

		// This form has Ranking fields
		if ( isset( $form['fields'] ) ) {
			$fields = wp_list_filter( $form['fields'], array( 'type' => $this->type ) );
			if ( ! empty( $fields ) ) {
				return wp_list_pluck( $fields, 'id' );
			}
		}

		return array();
	}

	/**
	 * Add the plugin's field button to GF's buttons
	 *
	 * @since 1.0.0
	 *
	 * @param array $field_groups Groups of field buttons
	 * @return array Groups of field buttons
	 */
	public function add_field_button( $field_groups ) {

		// Get the Standard Fields group position
		$group = array_search( 'standard_fields', wp_list_pluck( $field_groups, 'name' ) );

		// Append to the Standard Fields group
		$field_groups[ $group ]['fields'][] = array(
			'class'   => 'button',
			'value'   => __( 'Ranking', 'gravityforms-ranking-field' ),
			'onclick' => "StartAddField( '{$this->type}' );" // Default GF onclick function
		);

		return $field_groups;
	}

	/**
	 * Return the proper field title for the plugin's field
	 *
	 * @since 1.0.0
	 * 
	 * @uses GravityForms_Ranking_Field::is_ranking_field()
	 * 
	 * @param string $type Field type
	 * @return string Field title or field type
	 */
	public function set_field_title( $type ) {

		// For Ranking fields, name accordingly
		if ( $this->is_ranking_field( $type ) ) {
			$title = __( 'Ranking', 'gravityforms-ranking-field' );
		} else {
			$title = $type;
		}

		return $title;
	}

	/**
	 * Return the field's input markup
	 *
	 * @since 1.0.0
	 * 
	 * @uses GravityForms_Ranking_Field::is_ranking_field()
	 * @uses GravityForms_Ranking_Field::get_field_choices()
	 * @uses GravityForms_Ranking_Field::get_choice_template()
	 * @uses apply_filters() Calls 'gravityforms_ranking_field_field'
	 * 
	 * @param string $input Field's input markup
	 * @param array $field Field data
	 * @param string $value Field's value
	 * @param int $lead_id Lead ID
	 * @param int $form_id Form ID
	 * @return string Field's input markup
	 */
	public function render_field( $input, $field, $value, $lead_id, $form_id ) {

		// Bail when not rendering a Ranking field
		if ( ! $this->is_ranking_field( $field ) )
			return $input;

		// Define local variable(s)
		$is_admin = is_admin();

		// Setup input name. See GFFormsModel::save_input()
		$name    = 'input_' . $field['id'];
		$choices = $this->get_field_choices( $field, $value );

		// Define field classes
		$class   = isset( $field['cssClass'] ) ? array_map( 'esc_attr', explode( ' ', $field['cssClass'] ) ) : array();
		$class[] = 'gfield_' . $this->type;
		$class[] = ! isset( $field[ $this->arrow_type_setting ] ) || ! in_array( $field[ $this->arrow_type_setting ], array( 'sort', 'arrow', 'arrow-alt', 'arrow-alt2' ) ) ? 'icon-' . $this->get_default_arrow_type() : 'icon-' . $field[ $this->arrow_type_setting ];

		// Conditional Logic
		$cl_fields = ( $is_admin || empty( $field['conditionalLogicFields'] ) ) ? '' : ' data-clfields="' . implode( ',', $field['conditionalLogicFields'] ) . '"';

		// Start output buffer
		ob_start(); ?>

		<div class="ginput_container">
			<ol class="<?php echo implode( ' ', $class ); ?>"<?php echo $cl_fields; ?>>
				<?php foreach ( $choices as $key => $choice ) : 

					// Within the form editor, preview up to 5 items ...
					if ( ! $is_admin || $key < 5 ) {
						echo $this->get_choice_template( $choice, $name . '.' . ( $key + 1 ) );

					// ... and notify accordingly
					} else {
						echo '<li class="gchoice_total">' . sprintf( __( '5 of %d items shown. Edit field to view all', 'gravityforms' ), count( $choices ) ) . '</li>';
						break;
					}
				endforeach; ?>
			</ol>
		</div>

		<?php

		// End output buffer
		$input = ob_get_clean();

		return apply_filters( 'gravityforms_ranking_field_field', $input, $field, $value, $lead_id, $form_id );
	}

		/**
		 * Return the input choices for the given field
		 *
		 * @since 1.0.0
		 * 
		 * @param array $field Field data
		 * @param string $value Field input value
		 * @return array Field choices
		 */
		public function get_field_choices( $field = array(), $value = array() ) {

			// Field has choices
			if ( isset( $field['choices'] ) ) {
				$choices = $field['choices'];

			// Setup default choices
			} else {
				$choices = array();
				foreach ( array(
					__( 'First Choice',  'gravityforms-ranking-field' ),
					__( 'Second Choice', 'gravityforms-ranking-field' ),
					__( 'Third Choice',  'gravityforms-ranking-field' )
				) as $choice ) {
					$choices[] = array( 'text' => $choice, 'value' => $choice );
				}
			}

			// Sort choices according to ranked value
			if ( ! empty( $value ) ) {

				// Sanitize value
				$value = maybe_unserialize( $value );
				$value = wp_parse_id_list( $value );

				// Sort choices array by value order. http://stackoverflow.com/a/348418/3601434
				$ordered = array();
				foreach ( $value as $key ) {
					if ( ! empty( $key ) && array_key_exists( $key, $choices ) ) {
						$ordered[ $key ] = $choices[ $key ];
						unset( $choices[ $key ] );
					}
				}
				$choices = $ordered + $choices;

			// Randomize default choices, not for admin
			} elseif ( ! is_admin() && isset( $field[ $this->randomize_setting ] ) && $field[ $this->randomize_setting ] ) {

				// Shuffle array contents randomly
				shuffle( $choices );
			}

			return $choices;
		}

		/**
		 * Return the (parsed) template for a single field's choice
		 *
		 * @since 1.0.0
		 *
		 * @uses GravityForms_Ranking_Field::get_tabindex()
		 * @uses apply_filters() Calls 'gravityforms_ranking_field_choice_template'
		 * 
		 * @param array $choice Optional. Choice data to parse
		 * @param string $name Optional. Input name to parse
		 * @return string A single choice's template markup
		 */
		public function get_choice_template( $choice = array(), $name = '' ) {

			// Start output buffer
			ob_start(); 

			// Output the choice template
			?><li>
				<i class="dashicons ranking-up"<?php echo $this->get_tabindex(); ?>></i><i class="dashicons ranking-down"<?php echo $this->get_tabindex(); ?>></i>
				<i class="dashicons ranking-sort"<?php echo $this->get_tabindex(); ?>></i>
				<span class="item-label">{{text}}</span><input type="hidden" name="{{name}}" value="{{value}}"/>
			</li><?php 

			// Return output buffer content
			$tmpl = ob_get_clean();

			// Parse choice data in template while we're at it
			if ( ! empty( $choice ) && ! empty( $name ) ) {
				$tmpl = str_replace( '{{text}}',  isset( $choice['text']  ) ? esc_attr( $choice['text']  ) : '', $tmpl );
				$tmpl = str_replace( '{{value}}', isset( $choice['value'] ) ? esc_attr( $choice['value'] ) : '', $tmpl );
				$tmpl = str_replace( '{{name}}', esc_attr( $name ), $tmpl );
			}

			return apply_filters( 'gravityforms_ranking_field_choice_template', $tmpl, $choice, $name );
		}

		/**
		 * Return a js-ready version of GF's tabindex attribute
		 * 
		 * GF's version uses single-quotes for wrapping attribute values.
		 *
		 * @since 1.0.0
		 * 
		 * @return string Tabindex attribute
		 */
		public function get_tabindex() {
			return GFCommon::$tab_index > 0 ? ' tabindex="' . GFCommon::$tab_index++ . '"' : '';
		}

		/**
		 * Return the default arrow type
		 *
		 * @since 1.0.0
		 *
		 * @uses apply_filters() Calls 'gravityforms_ranking_field_default_arrow_type'
		 * @return string Arrow type
		 */
		public function get_default_arrow_type() {
			return apply_filters( 'gravityforms_ranking_field_default_arrow_type', 'sort' );
		}

	/**
	 * Enqueue scripts for the form settings editor
	 *
	 * @since 1.0.0
	 * 
	 * @uses GravityForms_Ranking_Field::localize_script()
	 */
	public function admin_scripts() { 

		// Register and enqueue form editor script
		wp_enqueue_script( 'gravityforms-ranking-field-editor', $this->includes_url . 'js/form-editor.js', array( 'gform_form_editor' ), $this->version, true );

		// Localize script
		$this->localize_script( 'gravityforms-ranking-field-editor' );

		// Add form settings editor styles
		add_action( 'admin_footer', array( $this, 'field_styles' ) );
		add_action( 'admin_footer', array( $this, 'admin_styles' ) );
	}

	/**
	 * Output styles for the form settings editor
	 *
	 * @since 1.0.0
	 */
	public function admin_styles() { ?>

		<style>
			/* Hide radio inputs */
			.field_selected.<?php echo $this->type; ?>-field .choices_setting .gfield_choice_radio {
				display: none;
			}
		</style>

		<?php
	}

	/**
	 * Enqueue scripts for the given form on the front-end
	 *
	 * @since 1.0.0
	 * 
	 * @uses GravityForms_Ranking_Field::has_form_ranking_fields()
	 * @uses GravityForms_Ranking_Field::localize_script()
	 *
	 * @param array $form Form data
	 * @param bool $ajax Whether we're doing an AJAX form
	 */
	public function form_scripts( $form, $ajax ) {

		// Bail when this form does not contain a Ranking Field
		if ( ! $this->has_form_ranking_fields( $form ) )
			return;

		// Enqueue Ranking script
		wp_enqueue_script( 'gravityforms-ranking-field', $this->includes_url . 'js/ranking.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version );

		// Localize script
		$this->localize_script( 'gravityforms-ranking-field' );

		// Ensure dashicons are loaded
		wp_enqueue_style( 'dashicons' );

		// Output Ranking styles
		add_action( 'wp_footer', array( $this, 'field_styles' ) );
	}

	/**
	 * Call wp_localize_script for the given script handle
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'gravityforms_ranking_field_localize_script'
	 * @uses apply_filters() Calls 'gravityforms_ranking_field_editor_settings'
	 * @uses wp_localize_script()
	 * 
	 * @param string $handle Script handle
	 */
	private function localize_script( $handle ) {

		// Define js strings
		$localize = apply_filters( 'gravityforms_ranking_field_localize_script', array(
			'labelUntitled' => __( 'Untitled', 'gravityforms-ranking-field' ),

			// Conditional Logic. Text is used ltr as GF does: 
			/* translators: Conditional Logic rule operators, used ltr: <field> %s <value> */
			'rankingRuleOperatorFirst' => _x( 'starts with',  'gravityforms-ranking-field' ),
			'rankingRuleOperatorNum'   => _x( 'choice %d is', 'gravityforms-ranking-field' ),
			'rankingRuleOperatorLast'  => _x( 'ends with',    'gravityforms-ranking-field' ),
		) );

		// Define js settings
		$settings = apply_filters( 'gravityforms_ranking_field_script_settings', array(
			'defaultChoices'   => $this->get_field_choices(),
			'choiceTemplate'   => $this->get_choice_template(),
			'arrowTypeSetting' => $this->arrow_type_setting,
			'defaultArrowType' => $this->get_default_arrow_type(),
		) );

		// Merge strings and settings
		$settings['type']     = $this->type;
		$localize['settings'] = $settings;

		// Localize script
		wp_localize_script( $handle, '_gfRankingFieldL10n', $localize );
	}

	/**
	 * Output styles for the Ranking field
	 *
	 * @since 1.0.0
	 */
	public function field_styles() { 

		// Setup field choice wrapper
		$wrapper = '.gfield_' . $this->type; ?>

		<style>
			/**
			 * Ranking list
			 */
			<?php echo $wrapper; ?> {
				margin: 6px 0;
				list-style: none;
				counter-reset: ranking-field-counter;
			}
			#gform_fields <?php echo $wrapper; ?> li {
				margin: 0 0 6px;
				padding: 0;
			}
			<?php echo $wrapper; ?> li:not(.ui-sortable-helper) {
				counter-increment: ranking-field-counter;
			}

			/**
			 * Ranking icons
			 */
			<?php echo $wrapper; ?> li i {
				color: #888;
				cursor: pointer;
			}
			<?php echo $wrapper; ?>.icon-arrow li i.ranking-up:before {
				content: "\f142";
			}
			<?php echo $wrapper; ?>.icon-arrow li i.ranking-down:before {
				content: "\f140";
			}
			<?php echo $wrapper; ?>.icon-arrow-alt li i.ranking-up:before {
				content: "\f342";
			}
			<?php echo $wrapper; ?>.icon-arrow-alt li i.ranking-down:before {
				content: "\f346";
			}
			<?php echo $wrapper; ?>.icon-arrow-alt2 li i.ranking-up:before {
				content: "\f343";
			}
			<?php echo $wrapper; ?>.icon-arrow-alt2 li i.ranking-down:before {
				content: "\f347";
			}
			<?php echo $wrapper; ?>.icon-sort li i {
				cursor: move;
			}
			<?php echo $wrapper; ?>.icon-sort li i.ranking-sort:before {
				content: "\f156";
			}
			<?php echo $wrapper; ?>.icon-sort li i.ranking-up,
			<?php echo $wrapper; ?>.icon-sort li i.ranking-down,
			<?php echo $wrapper; ?>:not(.icon-sort) li i.ranking-sort {
				display: none;
			}
			<?php echo $wrapper; ?>:not(.icon-sort) li:first-of-type i.ranking-up,
			<?php echo $wrapper; ?>:not(.icon-sort) li:last-of-type i.ranking-down,
			.wp-admin <?php echo $wrapper; ?> li i {
				color: #d5d5d5;
				cursor: inherit;
			}
				<?php echo $wrapper; ?>:not(.icon-sort) li i:hover {
					background-color: #eee;
				}

			/**
			 * Ranking label
			 */
			<?php echo $wrapper; ?> li .item-label {
				margin: 0 0 0 10px;
				cursor: move;
			}
			<?php echo $wrapper; ?> li .item-label:before {
				content: counter( ranking-field-counter ) ".";
				margin: 0 5px 0 0;
			}
			<?php echo $wrapper; ?> li.ui-sortable-helper .item-label:before {
				content: attr( data-counter ) ".";
			}
		</style>

		<?php
	}

	/**
	 * Manipulate the field's class names
	 *
	 * @since 1.0.0
	 * 
	 * @uses GravityForms_Ranking_Field::is_ranking_field()
	 * 
	 * @param string $classes Field class names
	 * @param array $field Field data
	 * @return string Field class names
	 */
	public function field_classes( $classes, $field ) {

		// For Ranking fields, manipulate class names
		if ( $this->is_ranking_field( $field ) ) {
			$classes .= " {$this->type}-field";
		}

		return $classes;
	}

	/**
	 * Sanitize user input value
	 *
	 * @since 1.0.0
	 * 
	 * @uses GravityForms_Ranking_Field::is_ranking_field()
	 * @uses apply_filters() Calls 'gravityforms_ranking_field_input_value'
	 * 
	 * @param mixed $value User input
	 * @param array $lead Lead data
	 * @param array $field Field data
	 * @param array $form Form data
	 * @param string $input_id Input name
	 * @return string|mixed Sanitized input value
	 */
	public function sanitize_input_value( $value, $lead, $field, $form, $input_id ) {

		// Bail when this is not a Ranking field's value
		if ( ! $this->is_ranking_field( $field ) )
			return $value;

		// Collect ranked choices
		$choice_values = wp_list_pluck( $field['choices'], 'value' );
		$choices = array_filter( $_POST, function( $input ) use ( $choice_values ) {

			// Get the current field's posted inputs
			return in_array( $input, $choice_values );
		});

		/**
		 * Since, at this moment, we're sanitizing the value for the given field's
		 * choice (input_id), we need to find the position of the associated choice's
		 * value. Note that in case of randomization, the inputs may be parsed with
		 * a different choice's input value.
		 * 
		 * So we need to find the original input's choice's value, and then find
		 * that value's position in the submitted form data.
		 */

		// Get the input's number
		$input_number = substr( $input_id, -1 );

		// Get the input's choice
		$choice = $field['choices'][ $input_number - 1 ];

		// Get the choice's value
		$value = $choice['value'];

		// Find posted choice's position
		$input = array_search( array_search( $value, $choices ), array_keys( $choices ) ) + 1;

		// Invert the choice order
		if ( isset( $field[ $this->invert_setting ] ) && $field[ $this->invert_setting ] ) {
			$input = count( $choice_values ) + 1 - $input; // 1 of 5 becomes 6 - 1 = 5
		}

		return apply_filters( 'gravityforms_ranking_field_input_value', $input, $lead, $choice, $field, $form );
	}

	/**
	 * Modify the rule's result for Ranking fields conditional logic
	 *
	 * @since 1.2.0
	 *
	 * @param bool $match The rule's result
	 * @param string $curr_value The rule's field current value
	 * @param string $target_value The rule's target value
	 * @param string $operation The operation type
	 * @param array $field The rule's field data
	 * @return bool The rule's result
	 */
	public function eval_conditional_logic_rule( $match, $curr_value, $target_value, $operation, $field ) {

		// Bail when not evaluating Ranking fields
		if ( ! $this->is_ranking_field( $field ) )
			return $match;

		// Current value is not defined
		if ( array() === array_filter( $curr_value ) ) {
			
			// Randomized
			if ( isset( $field[ $this->randomize_setting ] ) && $field[ $this->randomize_setting ] ) {
				$curr_value = array(); // @todo How to get the shuffled order of the yet-to-render field?
			} else {
				$curr_value = wp_list_pluck( $field['choices'], 'value' );
			}
		}

		// Check the target value for the expected position
		$match = ( (int) $operation ) === array_search( $target_value, $curr_value );

		return $match;
	}

	/**
	 * Register additional field settings
	 *
	 * @since 1.0.0
	 * 
	 * @uses GravityForms_Ranking_Field::display_randomize_setting()
	 * @uses GravityForms_Ranking_Field::display_invert_setting()
	 * @uses GravityForms_Ranking_Field::display_arrow_type_setting()
	 * 
	 * @param int $position Setting's position
	 * @param int $form_id Form ID
	 */
	public function register_field_settings( $position, $form_id ) {
		switch ( $position ) {

			// Immediately after Choices setting
			case 1368 :

				// Randomize setting
				$this->display_randomize_setting( $form_id );

				// Invert setting
				$this->display_invert_setting( $form_id );
				break;

			// Immediately after Description setting
			case 1430 :

				// Arrow Type setting
				$this->display_arrow_type_setting( $form_id );
				break;
		}
	}

	/**
	 * Display the settings field for the Randomize setting
	 *
	 * @since 1.0.0
	 * 
	 * @param int $form_id Form ID
	 */
	public function display_randomize_setting( $form_id ) { ?>

		<li class="ranking_randomize_setting field_setting">
			<input type="checkbox" id="ranking_randomize" name="ranking_randomize" value="1" onclick="SetFieldProperty( '<?php echo $this->randomize_setting; ?>', this.checked );" />
			<label for="ranking_randomize" class="inline"><?php _e( 'Randomize initial ranking', 'gravityforms-ranking-field' ); ?> <?php gform_tooltip( 'ranking_randomize_setting' ); ?></label>

			<script type="text/javascript">
				// Check setting when selecting new field
				jQuery(document).on( 'gform_load_field_settings', function( e, field, form ) {
					jQuery( '#ranking_randomize' ).attr( 'checked', typeof field.<?php echo $this->randomize_setting; ?> === 'undefined' ? false : field.<?php echo $this->randomize_setting; ?> );
				});
			</script>
		</li>

		<?php
	}

	/**
	 * Display the settings field for the Invert setting
	 *
	 * @since 1.1.0
	 * 
	 * @param int $form_id Form ID
	 */
	public function display_invert_setting( $form_id ) { ?>

		<li class="ranking_invert_setting field_setting">
			<input type="checkbox" id="ranking_invert" name="ranking_invert" value="1" onclick="SetFieldProperty( '<?php echo $this->invert_setting; ?>', this.checked );" />
			<label for="ranking_invert" class="inline"><?php _e( 'Invert ranking result', 'gravityforms-ranking-field' ); ?> <?php gform_tooltip( 'ranking_invert_setting' ); ?></label>

			<script type="text/javascript">
				// Check setting when selecting new field
				jQuery(document).on( 'gform_load_field_settings', function( e, field, form ) {
					jQuery( '#ranking_invert' ).attr( 'checked', typeof field.<?php echo $this->invert_setting; ?> === 'undefined' ? false : field.<?php echo $this->invert_setting; ?> );
				});
			</script>
		</li>

		<?php
	}

	/**
	 * Display the settings field for the Arrow Type setting
	 *
	 * @since 1.0.0
	 * 
	 * @param int $form_id Form ID
	 */
	public function display_arrow_type_setting( $form_id ) { ?>

		<li class="ranking_arrow_type_setting field_setting">
			<label for="ranking_arrow_type"><?php _e( 'Arrow Type', 'gravityforms-ranking-field' ); ?> <?php gform_tooltip( 'ranking_arrow_type_setting' ); ?></label>

			<ul>
				<li class="icon-sort">
					<input type="radio" id="ranking_arrow_type_sort" name="ranking_arrow_type" value="sort" onclick="SetFieldProperty( '<?php echo $this->arrow_type_setting; ?>', 'sort' );" />
					<label for="ranking_arrow_type_sort"><i class="dashicons dashicons-sort"></i></label>
				</li>
				<li class="icon-arrow">
					<input type="radio" id="ranking_arrow_type_arrow" name="ranking_arrow_type" value="arrow" onclick="SetFieldProperty( '<?php echo $this->arrow_type_setting; ?>', 'arrow' );" />
					<label for="ranking_arrow_type_arrow"><i class="dashicons dashicons-arrow-up"></i></label>
				</li>
				<li class="icon-arrow-alt">
					<input type="radio" id="ranking_arrow_type_arrow_alt" name="ranking_arrow_type" value="arrow-alt" onclick="SetFieldProperty( '<?php echo $this->arrow_type_setting; ?>', 'arrow-alt' );" />
					<label for="ranking_arrow_type_arrow_alt"><i class="dashicons dashicons-arrow-up-alt"></i></label>
				</li>
				<li class="icon-arrow-alt2">
					<input type="radio" id="ranking_arrow_type_arrow_alt2" name="ranking_arrow_type" value="arrow-alt2" onclick="SetFieldProperty( '<?php echo $this->arrow_type_setting; ?>', 'arrow-alt2' );" />
					<label for="ranking_arrow_type_arrow_alt2"><i class="dashicons dashicons-arrow-up-alt2"></i></label>
				</li>
			</ul>

			<script type="text/javascript">
				// Check setting when selecting new field
				jQuery(document).on( 'gform_load_field_settings', function( e, field, form ) {
					jQuery( 'input[name="ranking_arrow_type"]' ).each( function(){ 
						jQuery(this).attr( 'checked', typeof field.<?php echo $this->arrow_type_setting; ?> === 'undefined' ? false : field.<?php echo $this->arrow_type_setting; ?> === this.value );
					});
				});

				// Live-preview arrow type selection
				jQuery( 'input[name="ranking_arrow_type"]' ).on( 'change', function() {
					jQuery( '.field_selected .gfield_<?php echo $this->type; ?>' ).removeClass( function( index, css ) {
						return ( css.match( /(^|\s)icon-\S+/g ) || [] ).join( ' ' );
					}).addClass( 'icon-' + this.value );
				});
			</script>

			<style>
				#gform_fields .ranking_arrow_type_setting li {
					display: inline-block;
					padding: 0;
					margin: 0;
				}
				.ranking_arrow_type_setting li input[type="radio"] {
					display: none;
				}
				.ranking_arrow_type_setting li input + label i {
					color: #999;
					padding: 5px;
				}
				.ranking_arrow_type_setting li input + label i:hover {
					background-color: #f1f1f1;
				}
				.ranking_arrow_type_setting li input:checked + label i {
					background-color: #ddd;
					color: #333;
				}
			</style>
		</li>

		<?php
	}

	/**
	 * Append custom tooltips to GF's tooltip collection
	 *
	 * @since 1.0.0
	 *
	 * @param array $tips Tooltips
	 * @return array Tooltips
	 */
	public function add_tooltips( $tips ) {

		// Each tooltip consists of an <h6> header with a short description after it
		$format = '<h6>%s</h6>%s';

		// Append tooltips
		$tips = array_merge( $tips, array(
			'ranking_randomize_setting'  => sprintf( $format, __( 'Randomize',  'gravityforms-ranking-field' ), __( "When respondents submit the form without changing the field's ranking, the default ranking may be overrepresented in your form's results. Select this option to randomize the default ranking in order to mitigate this effect.", 'gravityforms-ranking-field' ) ),
			'ranking_invert_setting'     => sprintf( $format, __( 'Invert',     'gravityforms-ranking-field' ), __( "By default, ranked choices are saved as 1 for the first choice, 2 for the second, etc. Select this option to invert the choice order, saving the choices the other way around, meaning 1 for the last choice, 2 for the second-to-last, etc.", 'gravityforms-ranking-field' ) ),
			'ranking_arrow_type_setting' => sprintf( $format, __( 'Arrow Type', 'gravityforms-ranking-field' ), __( "Select the arrow type you'd like to use as ranking icons.", 'gravityforms-ranking-field' ) ),
		) );

		return $tips;
	}

	/**
	 * Return a valid display entry value for Ranking fields
	 *
	 * @since 1.2.1
	 *
	 * @uses GravityForms_Ranking_field::is_ranking_field()
	 * @uses GFFormsModel::get_lead_field_value()
	 * 
	 * @param mixed $value Display entry value for this field
	 * @param array $field Field data
	 * @param array $entry Entry data
	 * @param array $form  Form data
	 * @return string Entry field display value
	 */
	public function display_field_value( $value, $field, $entry = array(), $form = array() ) {

		// For Ranking fields only
		if ( $this->is_ranking_field( $field ) ) {

			// Treat the given value as raw
			$raw_value = $value;

			// Retry to get the raw entry value
			if ( null === $raw_value && ! empty( $entry ) ) {
				$raw_value = GFFormsModel::get_lead_field_value( $entry, $field );
			}

			// Only process when a value is found
			if ( is_array( $raw_value ) ) {

				// Sort choices by ranking
				asort( $raw_value );

				// Return choices as list
				$value = '<ul>';

				// Setup choice list with choice labels
				foreach ( (array) $raw_value as $input_id => $item ) {
					$input = array_values( wp_list_filter( $field['inputs'], array( 'id' => $input_id ) ) );
					$value .= '<li>' . $input[0]['label'] . '</li>';
				}

				// Close list
				$value .= '</ul>';
			}
		}

		return $value;
	}
}

/**
 * Return single instance of the main plugin class
 *
 * @since 1.0.0
 * 
 * @return GravityForms_Ranking_Field
 */
function gravityforms_ranking_field() {
	return GravityForms_Ranking_Field::instance();
}

// Initiate on plugins_loaded
add_action( 'plugins_loaded', 'gravityforms_ranking_field' );

endif; // class_exists
