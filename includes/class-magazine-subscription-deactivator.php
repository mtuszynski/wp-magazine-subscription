<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://https://github.com/mtuszynski
 * @since      1.0.0
 *
 * @package    Magazine_Subscription
 * @subpackage Magazine_Subscription/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Magazine_Subscription
 * @subpackage Magazine_Subscription/includes
 * @author     MirT <tuszynski.mir@gmail.com>
 */
class Magazine_Subscription_Deactivator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate()
	{
		if (self::should_delete_tables()) {
			self::drop_table('magazine_subscribe_settings');
			self::drop_table('magazine_subscribe_users');
		}
	}
	/**
	 * Check if the user wants to delete the tables.
	 *
	 * @since    1.0.0
	 */

	private static function should_delete_tables()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'magazine_subscribe_settings';
		$query = $wpdb->prepare("SELECT delete_tables_on_deactivation FROM $table_name WHERE id = %d", 1);
		$result = $wpdb->get_var($query);
		return (bool) $result;
	}

	/**
	 * Drop a table from the database.
	 *
	 * @since    1.0.0
	 */
	private static function drop_table($table_suffix)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . $table_suffix;

		$sql = "DROP TABLE IF EXISTS $table_name;";

		$wpdb->query($sql);
	}
}
