<?php

/**
 * Fired during plugin activation
 *
 * @link       https://https://github.com/mtuszynski
 * @since      1.0.0
 *
 * @package    Magazine_Subscription
 * @subpackage Magazine_Subscription/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Magazine_Subscription
 * @subpackage Magazine_Subscription/includes
 * @author     MirT <tuszynski.mir@gmail.com>
 */
class Magazine_Subscription_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		self::create_table('magazine_subscribe_settings', "
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) NOT NULL,
			delete_tables_on_deactivation tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id)
        ");

		self::create_table('magazine_subscribe_users', "
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            user_login varchar(60) NOT NULL,
            user_email varchar(100) NOT NULL,
            order_id mediumint(9) NOT NULL UNIQUE,
            product_name varchar(255) NOT NULL,
			category_subscription_id int(11) NOT NULL,
			subscription_length int(11) NOT NULL,
            subscription_start int(11) NOT NULL,
            subscription_end int(11) NOT NULL,
            attribute_selector varchar(255),
            subscribe_left int(11),
            PRIMARY KEY  (id)
        ");
	}

	private static function create_table($table_suffix, $sql)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . $table_suffix;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name ($sql) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}
