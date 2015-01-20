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
		// Setup actions
	}

	/** Public methods **************************************************/

	// Do stuff
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
