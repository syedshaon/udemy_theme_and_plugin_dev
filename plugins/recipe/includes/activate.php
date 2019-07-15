<?php
function r_activate_plugin(){
    if(version_compare(get_bloginfo('version'), '4.5', '<')){
        wp_die(__('Your must update WordPress to use this pluging', 'recipe'));
    }
}