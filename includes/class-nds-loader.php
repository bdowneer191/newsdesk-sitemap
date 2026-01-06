<?php
/**
 * Register all actions and filters for the plugin
 *
 * This class orchestrates the hooks between WordPress and the plugin
 * by maintaining a collection of all actions and filters.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-1 of Complete Technical Implementation Guide (ANSP_Loader logic)
 * IMPLEMENTATION: NDS_Loader implementation for hook orchestration.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Loader {

	/**
	 * Array of actions registered with WordPress
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $actions    Actions registered with WordPress
	 */
	protected $actions;

	/**
	 * Array of filters registered with WordPress
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $filters    Filters registered with WordPress
	 */
	protected $filters;

	/**
	 * Initialize collections to maintain the actions and filters
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Add a new action to the collection
	 *
	 * @since    1.0.0
	 * @param    string               $hook             Hook name
	 * @param    object               $component        Object containing callback
	 * @param    string               $callback         Method name
	 * @param    int                  $priority         Priority (default: 10)
	 * @param    int                  $accepted_args    Accepted arguments (default: 1)
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection
	 *
	 * @since    1.0.0
	 * @param    string               $hook             Hook name
	 * @param    object               $component        Object containing callback
	 * @param    string               $callback         Method name
	 * @param    int                  $priority         Priority (default: 10)
	 * @param    int                  $accepted_args    Accepted arguments (default: 1)
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Utility function to add hooks to collection
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array                $hooks            Existing hooks array
	 * @param    string               $hook             Hook name
	 * @param    object               $component        Object containing callback
	 * @param    string               $callback         Method name
	 * @param    int                  $priority         Priority
	 * @param    int                  $accepted_args    Accepted arguments
	 * @return   array                                  Modified hooks array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args
		);

		return $hooks;
	}

	/**
	 * Register all filters and actions with WordPress
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// Register filters
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		// Register actions
		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}