<?php
/**
 * Plugin Name: DLSYNC
 * Plugin URI:
 * Description: A simple plugin to sync users and posts via WP REST API
 * Version: 1.0.0
 * Author: D. Lev. http://e-cv.dograshvili.com/
 * Author URI:
 * Text Domain:
 *
 *
 */


 /**
  * Load modules
  */
include_once sprintf("%sDlUser.php", plugin_dir_path(__FILE__));


/*
 * Base hook action for api
 */
add_action('rest_api_init', function() {
		register_rest_route('dlsync/', 'user/create/', [
			'methods'  => 'POST',
			'callback' => ['DlUser', 'Create']
		]);

		register_rest_route('dlsync/', 'user/update/', [
			'methods'  => 'POST',
			'callback' => ['DlUser', 'Update']
		]);
});
