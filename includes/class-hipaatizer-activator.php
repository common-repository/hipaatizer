<?php

/**
 * Fired during plugin activation
 *
 *
 * @package    HIPAAtizer
 * @subpackage HIPAAtizer/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    HIPAAtizer
 * @subpackage HIPAAtizer/includes
 * @author     Cappers
 */
class HIPAAtizer_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 */
	public static function activate()
	{

		global $wpdb;
        if ( is_multisite() ) {
			$create_table_query = "
            CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}hipaatizer` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `hipaatizer_id` varchar(120) NOT NULL,
			  `site_id` varchar(120) NOT NULL,
			  PRIMARY KEY (id)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
    ";
		} else {
			$create_table_query = "
            CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}hipaatizer` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `hipaatizer_id` varchar(120) NOT NULL,
			  PRIMARY KEY (id)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
    ";
		}
		

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($create_table_query);
	}
}
