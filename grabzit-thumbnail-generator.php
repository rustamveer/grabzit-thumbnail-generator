<?php
/*
Plugin Name: Grabz.it Thumbnail Generator
Plugin URI: https://lampp.io/
Description: A plugin to generate video thumbnails using Grabz.it and save them in a Formidable Forms field.
Version: 1.0
Author: Rustamveer Singh
Author URI: https://www.linkedin.com/in/rustamveer
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}


require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

require_once plugin_dir_path(__FILE__) . 'includes/class-grabzit-thumbnail-generator.php';

if (defined('WP_CLI') && WP_CLI) {
    require_once plugin_dir_path(__FILE__) . 'wp-cli-commands.php';
}

function run_grabzit_thumbnail_generator()
{
    $grabzit_thumbnail_generator = new Grabzit_Thumbnail_Generator();
    $grabzit_thumbnail_generator->init();
}
add_action('plugins_loaded', 'run_grabzit_thumbnail_generator');
