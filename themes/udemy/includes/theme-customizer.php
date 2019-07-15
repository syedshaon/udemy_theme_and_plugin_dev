<?php



function ju_customize_register($wp_customize){

    $wp_customize-> get_section('title_tagline')->title = 'General';

    $wp_customize->add_panel('udemy', array(
        'title'         => __('Theme Options ', 'udemy'),
        'description'   => '<p>Extra Options </p>',
        'priority'      => 160
    ));

    ju_social_customizer_section($wp_customize);
    ju_misc_customizer_section($wp_customize);

    // echo '<pre>';
    // var_dump($wp_customize);
    // echo '</pre>';

}