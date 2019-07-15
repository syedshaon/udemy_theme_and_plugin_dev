<?php

function ju_customize_preview_init(){
    wp_enqueue_script(
        'ju_theme_customizer',
        get_template_directory_uri().'/assets/js/theme-customize.js',
        array( 'jquery', 'customize-preview'),
        false,
        true
    );
}