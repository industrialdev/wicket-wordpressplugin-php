<?php

function wicket_css(){
  wp_register_style('wicket_alerts', plugins_url( '../assets/css/alerts.css', __FILE__ ));
  wp_enqueue_style( 'wicket_alerts' );
}
add_action('wp_enqueue_scripts', 'wicket_css');
