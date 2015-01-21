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

		// Field classes
		add_filter( 'gform_field_css_class', array( $this, 'field_classes' ), 10, 2 );
	}

	/** Public methods **************************************************/

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
		if ( $this->type === $type ) {
			$type = __( 'Ranking', 'gravityforms-field-ranking' );
		}

		return $type;
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
		if ( $this->type === $field['type'] ) {
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
