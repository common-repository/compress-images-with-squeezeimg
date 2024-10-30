<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    imagecompresssqueezeimg
 * @subpackage imagecompresssqueezeimg/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    imagecompresssqueezeimg
 * @subpackage imagecompresssqueezeimg/includes
 * @author     Your Name <email@example.com>
 */
class imagecompresssqueezeimg_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        wp_clear_scheduled_hook('imagecompress_squeezeimg_cron', [], false);
	}

}
