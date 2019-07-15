<?php 


function ju_social_customizer_section($wp_customize){
    $wp_customize->add_setting('ju_facebook_handle', array(
        'default'     => ''
    ));
    $wp_customize->add_setting('ju_twitter_handle', array(
        'default'     => ''
    ));
    $wp_customize->add_setting('ju_instagram_handle', array(
        'default'     => ''
    ));
    $wp_customize->add_setting('ju_telephone', array(
        'default'     => ''
    ));
    $wp_customize->add_setting('ju_email', array(
        'default'     => ''
    ));


    $wp_customize->add_section('ju_social_section', array(
        'title'             => __('Udemy Social Settings', 'udemy'),
        'priority'          => 30,
        'panel'             => 'udemy'
    ));

    $wp_customize-> add_control(
            new WP_Customize_Control(
                $wp_customize, 
                'ju_social_facebook_input',
                array(
                    'label'         => __('Facebook Handle', 'udemy'),
                    'section'       => 'ju_social_section',
                    'settings'      => 'ju_facebook_handle'
                )

            )
    );
    $wp_customize-> add_control(
            new WP_Customize_Control(
                $wp_customize, 
                'ju_social_twitter_input',
                array(
                    'label'         => __('Twitter Handle', 'udemy'),
                    'section'       => 'ju_social_section',
                    'settings'      => 'ju_twitter_handle'
                )

            )
    );
    $wp_customize-> add_control(
            new WP_Customize_Control(
                $wp_customize, 
                'ju_social_instagram_input',
                array(
                    'label'         => __('Instagram Handle', 'udemy'),
                    'section'       => 'ju_social_section',
                    'settings'      => 'ju_instagram_handle'
                )

            )
    );
    $wp_customize-> add_control(
            new WP_Customize_Control(
                $wp_customize, 
                'ju_telephone_input',
                array(
                    'label'         => __('Telephone Number ', 'udemy'),
                    'section'       => 'ju_social_section',
                    'settings'      => 'ju_telephone'
                )

            )
    );
    $wp_customize-> add_control(
            new WP_Customize_Control(
                $wp_customize, 
                'ju_email_input',
                array(
                    'label'         => __('Primary Email', 'udemy'),
                    'section'       => 'ju_social_section',
                    'settings'      => 'ju_email'
                )

            )
    );
}