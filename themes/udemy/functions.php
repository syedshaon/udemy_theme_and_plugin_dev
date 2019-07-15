<?php



//  Includes


include( get_template_directory() .'/includes/front/enqueue.php');
include( get_template_directory() .'/includes/front/setup.php' );
include( get_template_directory() .'/includes/front/widgets.php' );
include( get_template_directory() .'/includes/theme-customizer.php');
include( get_template_directory() .'/includes/customizer/social.php');
include( get_template_directory() .'/includes/customizer/misc.php');
include( get_template_directory() .'/includes/customizer/enque.php');

//  Hooks
add_action('wp_enqueue_scripts', 'ju_enqueue');
add_action( 'after_setup_theme', 'ju_setup' );
add_action( 'widgets_init', 'udemy_widgets_init' );
add_action('customize_register', 'ju_customize_register');
add_action( 'customize_preview_init', 'ju_customize_preview_init');





// ShortCodes