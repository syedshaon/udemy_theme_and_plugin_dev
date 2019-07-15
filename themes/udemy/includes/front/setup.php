<?php

// Setup


if ( ! function_exists( 'ju_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function ju_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on Wapik-K, use a find and replace
		 * to change 'Wapik-K' to the name of your theme in all the template files.
		 */


		// load_theme_textdomain( 'Wapik-K', get_template_directory() . '/languages' );

        // Add default posts and comments RSS feed links to head.
        

		 add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */


		 add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */


		 add_theme_support( 'post-thumbnails' );
		// set_post_thumbnail_size(476, 577, true);
        // add_image_size('small-thumb', 300, 150, true); 
        // add_image_size('sliderImage', 1600, 400, true);
        // add_image_size('people-thumb', 300, 420, true);
        // add_image_size('partner-thumb', 150, 210, true);

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'primary' => esc_html__( 'primary', 'udemy' ),
		) );
		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'secondary' => esc_html__( 'Secondary Menu', 'udemy' ),
		) );

		if (function_exists('quads_register_ad')){
            quads_register_ad( array('location' => 'udemy_header', 'description' => 'Udemy Header position') );
		}



		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */


		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
        ) );
        
        add_theme_support( 'post-formats', array( 
            'aside', 
            'gallery', 
            'link', 
            'image', 
            'quote', 
            'video', 
            'audio' 
        ) );

		// Set up the WordPress core custom background feature.
		// add_theme_support( 'custom-background', apply_filters( 'wapik_k_custom_background_args', array(
		// 	'default-color' => 'ffffff',
		// 	'default-image' => '',
		// ) ) );

		// Add theme support for selective refresh for widgets.
		// add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 *  https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			// Have related css setup in layout>header.scss
			'height'      => 96,
			'width'       => 160,
			'flex-width'  => true,
			'flex-height' => true,
		) );

		
		// Define and register starter content to showcase the theme on new sites.
		$starter_content                =   array(
			'widgets'                   =>  array(
				// Place three core-defined widgets in the sidebar area.
				'ju_sidebar'            =>  array(
					'text_business_info',
					'search',
					'text_about',
				)
			),

			// Create the custom image attachments used as post thumbnails for pages.
			'attachments'               =>  array(
				'image-about'           =>  array(
					'post_title'        =>  __( 'About', 'udemy' ),
					'file'              =>  'assets/images/about/1.jpg', // URL relative to the template directory.
				),
			),

			// Specify the core-defined pages to create and add custom thumbnails to some of them.
			'posts'                     => array(
				'home'                 =>  array(
					'thumbnail'         => '{{image-about}}',
				),
				'about'                 =>  array(
					'thumbnail'         => '{{image-about}}',
				),
				'contact'               => array(
					'thumbnail'         => '{{image-about}}',
				),
				'blog'                  => array(
					'thumbnail'         => '{{image-about}}',
				),
				'homepage-section'      => array(
					'thumbnail'         => '{{image-about}}',
				),
			),

			// Default to a static front page and assign the front and posts pages.
			'options'                   =>  array(
				'show_on_front'         => 'page',
				'page_on_front'         => '{{home}}',
				'page_for_posts'        => '{{blog}}',
			),

			// Set the front page section theme mods to the IDs of the core-registered pages.
			'theme_mods'                => array(
				'ju_facebook_handle'    =>  'udemy',
				'ju_twitter_handle'     =>  'udemy',
				'ju_instagram_handle'   =>  'udemy',
				'ju_email'              =>  'udemy',
				'ju_phone_number'       =>  'udemy',
				'ju_header_show_search' =>  'yes',
				'ju_header_show_cart'   =>  'yes',
			),

			// Set up nav menus for each of the two areas registered in the theme.
			'nav_menus'                 =>  array(
				// Assign a menu to the "top" location.
				'primary'               =>  array(
					'name'              =>  __( 'Primary Menu', 'udemy' ),
					'items'             =>  array(
						'link_home', // Note that the core "home" page is actually a link in case a static front page is not used.
						'page_about',
						'page_blog',
						'page_contact',
					),
				),

				// Assign a menu to the "social" location.
				'secondary'             =>  array(
					'name'              =>  __( 'Secondary Menu', 'udemy' ),
					'items'             =>  array(
						'link_home', // Note that the core "home" page is actually a link in case a static front page is not used.
						'page_about',
						'page_blog',
						'page_contact',
					),
				),
			),
		);

		add_theme_support( 'starter-content', $starter_content );
	}
endif;
add_action( 'after_setup_theme', 'ju_setup' );