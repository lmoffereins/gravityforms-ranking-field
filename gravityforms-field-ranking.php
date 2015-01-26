<?php

/**
 * The Gravity Forms Field Ranking Plugin
 * 
 * @package Gravity Forms Field Ranking
 * @subpackage Main
 */

/**
 * Plugin Name:       Gravity Forms Field Ranking
 * Description:       Adds a field to Gravity Forms to rank options
 * Plugin URI:        https://github.com/lmoffereins/gravityforms-field-ranking/
 * Version:           1.0.0
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins/
 * Text Domain:       gravityforms-field-ranking
 * Domain Path:       /languages/
 * GitHub Plugin URI: lmoffereins/gravityforms-field-ranking
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GravityForms_Field_Ranking' ) ) :
/**
 * The main plugin class
 *
 * @since 1.0.0
 */
final class GravityForms_Field_Ranking {

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
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses GravityForms_Field_Ranking::setup_globals()
	 * @uses GravityForms_Field_Ranking::setup_actions()
	 * @return The single GravityForms_Field_Ranking
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new GravityForms_Field_Ranking;
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
		
		$this->version      = '1.0.0';
		
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
		$this->domain       = 'gravityforms-field-ranking';
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {
		
		// Add field button
		add_filter( 'gform_add_field_buttons', array( $this, 'add_field_button' ) );

		// Set field title
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

		// Add field settings
		add_action( 'gform_field_standard_settings', array( $this, 'register_field_settings' ), 10, 2 );

		// Add tooltips
		add_filter( 'gform_tooltips', array( $this, 'add_tooltips' ) );
	}

	/** Public methods **************************************************/

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
			'value'   => __( 'Ranking', 'gravityforms-field-ranking' ),
			'onclick' => "StartAddField( '{$this->type}' );" // Default GF onclick function
		);

		return $field_groups;
	}

	/**
	 * Return the proper field title for the plugin's field
	 *
	 * @since 1.0.0
	 * 
	 * @param string $type Field type
	 * @return string Field title or field type
	 */
	public function set_field_title( $type ) {

		// For Ranking fields, name accordingly
		if ( $this->is_ranking_field( $type ) ) {
			$title = __( 'Ranking', 'gravityforms-field-ranking' );
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

		// Setup input name. See GFFormsModel::save_input(). Does not care for multiple forms on a page(?)
		$name    = 'input_' . $field['id']; 

		// Define field classes
		$class   = isset( $field['cssClass'] ) ? array_map( 'esc_attr', explode( ' ', $field['cssClass'] ) ) : array();
		$class[] = 'gfield_' . $this->type;
		$class[] = ! isset( $field['arrowType'] ) || ! in_array( $field['arrowType'], array( 'arrow', 'arrow-alt', 'arrow-alt2', 'sort' ) ) ? 'icon-sort' : 'icon-' . $field['arrowType'];

		// Start output buffer
		ob_start(); ?>

		<div class="ginput_container">
			<ol class="<?php echo implode( ' ', $class ); ?>">
				<?php foreach ( $this->get_field_choices( $field, $value ) as $choice ) : ?>
					<?php echo $this->get_choice_template( $choice, $name ); ?>
				<?php endforeach; ?>
			</ol>
		</div>

		<?php

		// End output buffer
		$input = ob_get_clean();

		return apply_filters( 'gravityforms_field_ranking_field', $input, $field, $value, $lead_id, $form_id );
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
					__( 'First Choice',  'gravityforms-field-ranking' ),
					__( 'Second Choice', 'gravityforms-field-ranking' ),
					__( 'Third Choice',  'gravityforms-field-ranking' )
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

			// Randomize default choices
			} elseif ( isset( $field[ $this->randomize_setting ] ) && $field[ $this->randomize_setting ] ) {

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
		 * @uses GravityForms_Field_Ranking::get_tabindex()
		 * @uses apply_filters() Calls 'gravityforms_field_ranking_choice_template'
		 * 
		 * @param array $choice Optional. Choice data to parse
		 * @param string $name Optional. Input name to parse
		 * @return string A single choice's template markup
		 */
		public function get_choice_template( $choice = array(), $name = '' ) {

			// Start output buffer
			ob_start(); 

			// Output the choice template
			?><li><i class="dashicons-before"<?php echo $this->get_tabindex(); ?>></i><i class="dashicons-before"<?php echo $this->get_tabindex(); ?>></i><span class="item-label">{{text}}</span><input type="hidden" name="{{name}}[]" value="{{value}}"/></li><?php 

			// Return output buffer content
			$tmpl = ob_get_clean();

			// Parse choice data in template while we're at it
			if ( ! empty( $choice ) && ! empty( $name ) ) {
				$tmpl = str_replace( '{{text}}',  isset( $choice['text']  ) ? esc_attr( $choice['text']  ) : '', $tmpl );
				$tmpl = str_replace( '{{value}}', isset( $choice['value'] ) ? esc_attr( $choice['value'] ) : '', $tmpl );
				$tmpl = str_replace( '{{name}}', esc_attr( $name ), $tmpl );
			}

			return apply_filters( 'gravityforms_field_ranking_choice_template', $tmpl, $choice, $name );
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
	 * Enqueue scripts for the form settings editor
	 *
	 * @since 1.0.0
	 */
	public function admin_scripts() { 

		// Register and enqueue form editor script
		wp_enqueue_script( 'gravityforms-field-ranking-editor', $this->includes_url . 'js/form-editor.js', array( 'gform_form_editor' ), $this->version, true );

		// Localize script
		$this->localize_script( 'gravityforms-field-ranking-editor' );

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
	 * @param array $form Form data
	 * @param bool $ajax Whether we're doing an AJAX form
	 */
	public function form_scripts( $form, $ajax ) {

		// Bail when this form does not contain a Ranking Field
		if ( ! $this->has_form_ranking_fields( $form ) )
			return;

		// Enqueue Ranking script
		wp_enqueue_script( 'gravityforms-field-ranking', $this->includes_url . 'js/ranking.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version );

		// Localize script
		$this->localize_script( 'gravityforms-field-ranking' );

		// Output Ranking styles
		add_action( 'wp_footer', array( $this, 'field_styles' ) );
	}

	/**
	 * Call wp_localize_script for the given script handle
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'gravityforms_field_ranking_editor_localize'
	 * @uses apply_filters() Calls 'gravityforms_field_ranking_editor_settings'
	 * @uses wp_localize_script()
	 * 
	 * @param string $handle Script handle
	 */
	private function localize_script( $handle ) {

		// Define js strings
		$localize = apply_filters( 'gravityforms_field_ranking_localize_script', array(
			'labelUntitled' => __( 'Untitled', 'gravityforms-field-ranking' ),
		) );

		// Define js settings
		$settings = apply_filters( 'gravityforms_field_ranking_script_settings', array(
			'defaultChoices' => $this->get_field_choices(),
			'choiceTemplate' => $this->get_choice_template(),
		) );

		// Merge strings and settings
		$settings['type'] = $this->type;
		$localize['settings'] = $settings;

		// Localize script
		wp_localize_script( $handle, '_gfFieldRankingL10n', $localize );
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
				counter-reset: gfield-ranking-counter;
			}
			#gform_fields <?php echo $wrapper; ?> li {
				margin: 0 0 6px;
				padding: 0;
			}
			<?php echo $wrapper; ?> li:not(.ui-sortable-placeholder) {
				counter-increment: gfield-ranking-counter;
			}

			/**
			 * Ranking icons
			 */
			<?php echo $wrapper; ?> li i {
				color: #888;
				cursor: pointer;
			}
			<?php echo $wrapper; ?>.icon-arrow li i:nth-child(1):before {
				content: "\f142";
			}
			<?php echo $wrapper; ?>.icon-arrow li i:nth-child(2):before {
				content: "\f140";
			}
			<?php echo $wrapper; ?>.icon-arrow-alt li i:nth-child(1):before {
				content: "\f342";
			}
			<?php echo $wrapper; ?>.icon-arrow-alt li i:nth-child(2):before {
				content: "\f346";
			}
			<?php echo $wrapper; ?>.icon-arrow-alt2 li i:nth-child(1):before {
				content: "\f343";
			}
			<?php echo $wrapper; ?>.icon-arrow-alt2 li i:nth-child(2):before {
				content: "\f347";
			}
			<?php echo $wrapper; ?>.icon-sort li i {
				cursor: move;
			}
			<?php echo $wrapper; ?>.icon-sort li i:before {
				content: "\f156";
			}
			<?php echo $wrapper; ?>.icon-sort li i:nth-child(2) {
				display: none;
			}
			<?php echo $wrapper; ?>:not(.icon-sort) li:first-of-type i:first-of-type,
			<?php echo $wrapper; ?>:not(.icon-sort) li:last-of-type i:last-of-type,
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
				content: counter(gfield-ranking-counter) ".";
				margin: 0 5px 0 0;
			}
		</style>

		<?php
	}

	/**
	 * Manipulate the field's class names
	 *
	 * @since 1.0.0
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

		// Sanitize value for field's choices
		$value = array_intersect( $value, wp_list_pluck( $field['choices'], 'value' ) );

		// Concat array values. Don't serialize
		$value = implode( ',', array_values( $value ) );

		return $value;
	}

	/**
	 * Register additional field settings
	 *
	 * @since 1.0.0
	 * 
	 * @param int $position Setting's position
	 * @param int $form_id Form ID
	 */
	public function register_field_settings( $position, $form_id ) {
		switch ( $position ) {

			// Immediately after Description setting
			case 1368 :

				// Randomize setting
				$this->display_randomize_setting( $form_id );
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
			<label for="ranking_randomize" class="inline"><?php _e( 'Randomize default ranking', 'gravityforms-field-ranking' ); ?> <?php gform_tooltip( 'ranking_randomize_setting' ); ?></label>

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
			'ranking_randomize_setting' => sprintf( $format, __( 'Randomize', 'gravityforms-field-ranking' ), __( "When respondents submit the form without changing the field's ranking, the default ranking may be overrepresented in your form's results. Select this option to randomize the default ranking in order to mitigate this effect.",  'gravityforms-field-ranking' ) ),
		) );

		return $tips;
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.0.0
 * 
 * @return GravityForms_Field_Ranking
 */
function gravityforms_field_ranking() {

	// Bail when GF is not active
	if ( ! class_exists( 'GFForms' ) )
		return;

	return GravityForms_Field_Ranking::instance();
}

// Initiate on plugins_loaded
add_action( 'plugins_loaded', 'gravityforms_field_ranking' );

endif; // class_exists
