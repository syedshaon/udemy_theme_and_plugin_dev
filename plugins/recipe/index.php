<?php
/**
 * Plugin Name:       recipe
 * Plugin URI:        
 * Description:       Recipe is meant to used for submitting recipe by user of the site
 * Version:           1.0
 * Author:            Mashiur Rahman
 * Author URI:        
 * Text Domain:       recipe
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

 if(!function_exists('add_action')){
     die("Hi there! I'm just a plugin, not much I can do when called directly.");
 }

//  Setup


// Includes



// Hooks
register_activation_hook( __FILE__, 'r_activate_plugin' );


// ShortCodes