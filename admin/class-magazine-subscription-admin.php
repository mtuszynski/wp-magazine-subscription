<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://github.com/mtuszynski
 * @since      1.0.0
 *
 * @package    Magazine_Subscription
 * @subpackage Magazine_Subscription/admin
 */
require_once plugin_dir_path(__FILE__) . 'partials/class-magazine-subscription-menu.php';
require_once plugin_dir_path(__FILE__) . 'partials/class-magazine-subscription-helpers.php';
require_once plugin_dir_path(__FILE__) . 'partials/class-magazine-subscription-product-meta.php';
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Magazine_Subscription
 * @subpackage Magazine_Subscription/admin
 * @author     MirT <tuszynski.mir@gmail.com>
 */
class Magazine_Subscription_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		new Magazine_Subscription_Menu();
		new Magazine_Subscription_Helpers();
		new Magazine_Subscription_Product_Meta();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Magazine_Subscription_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Magazine_Subscription_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/magazine-subscription-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Magazine_Subscription_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Magazine_Subscription_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/magazine-subscription-admin.js', array('jquery'), $this->version, false);
	}
}
