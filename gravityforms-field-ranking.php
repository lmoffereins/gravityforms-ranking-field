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

		// Render field input
		add_filter( 'gform_field_input', array( $this, 'render_field_input' ), 10, 5 );

		// Field classes
		add_filter( 'gform_field_css_class', array( $this, 'field_classes' ), 10, 2 );
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
	public function render_field_input( $input, $field, $value, $lead_id, $form_id ) {

		// Bail when not rendering a Ranking field
		if ( ! $this->is_ranking_field( $field ) )
			return $input;

		// Define input attributes
		$name     = 'input_' . $form_id . '_' . $field['id'] . '[]';
		$class    = isset( $field['cssClass'] ) ? esc_attr( $field['cssClass'] ) : '';
		$tabindex = GFCommon::get_tabindex();
		$arrow    = ! isset( $field['arrowType'] ) || ! in_array( $field['arrowType'], array( 'default', 'alt', 'alt2' ) ) ? 'default' : $field['arrowType'];
		$arrow    = ( 'default' != $arrow ) ? '-' . $arrow : '';

		// Start output buffer
		ob_start(); ?>

		<div class="ginput_container">
			<ol class="gfield_ranking sortable">
				<?php foreach ( $this->get_field_options( $field, $value ) as $option_key => $option_label ) : ?>
				<li class="sortable-item" data-value="<?php echo esc_attr( $option_key ); ?>">
					<i class="dashicons dashicons-arrow-up<?php echo $arrow; ?>"></i><i class="dashicons dashicons-arrow-down<?php echo $arrow; ?>"></i>
					<span class="item-label"><?php echo $option_label; ?></span>
					<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $option_key; ?>"/>
				</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<style>
			.gfield_ranking {
				margin: 6px 0;
				list-style: none;
				counter-reset: gfield-ranking-counter;
			}
			#gform_fields .gfield_ranking li {
				margin: 0 0 6px;
				padding: 0;
				counter-increment: gfield-ranking-counter;
			}
			.gfield_ranking li i {
				padding: 2px 0;
				color: #888;
				cursor: pointer;
			}
			.gfield_ranking li:first-of-type i:first-of-type,
			.gfield_ranking li:last-of-type i:last-of-type {
				color: #d5d5d5;
				cursor: inherit;
			}
				.gfield_ranking i:hover {
					background-color: #eee;
				}
			.gfield_ranking li .item-label {
				cursor: move;
				margin: 0 0 0 10px;
			}
			.gfield_ranking li .item-label:before {
				content: counter(gfield-ranking-counter) ".";
				margin: 0 5px 0 0;
			}
		</style>

		<?php

		// End output buffer
		$input = ob_get_clean();

		return apply_filters( 'gravityforms_field_ranking_field_input', $input, $field, $value, $lead_id, $form_id );
	}

		/**
		 * Return the input options for the given field
		 *
		 * @since 1.0.0
		 * 
		 * @param array $field Field data
		 * @param string $value Field input value
		 * @return array Field options
		 */
		public function get_field_options( $field, $value ) {

			// Field has no options
			if ( ! isset( $field['options'] ) ) {
				$field['options'] = array( 
					1 => __( 'First Option',  'gravityforms-field-ranking' ),
					2 => __( 'Second Option', 'gravityforms-field-ranking' ),
					3 => __( 'Third Option',  'gravityforms-field-ranking' )
				);
			}

			// Align options to ranked value
			if ( ! empty( $value ) ) {
				if ( ! is_array( $value ) ) {
					$value = explode( ',', $value );
				}

				// Sort options array by value order. http://stackoverflow.com/a/348418/3601434
				$ordered = array();
				foreach ( $value as $key ) {
					if ( array_key_exists( $key, $field['options'] ) ) {
						$ordered[ $key ] = $field['options'][ $key ];
						unset( $field['options'][ $key ] );
					}
				}
				$field['options'] = $ordered + $field['options'];
			}

			return $field['options'];
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
			$classes .= ' rank-field-options';
		}

		return $classes;
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
