<?php

use Wicket\Client;

/*
Plugin Name: Wicket Update Password
Description: wicket.io plugin responsible for providing a widget with a form to update a persons wicket password
Author: Industrial
*/

// The widget class
// http://www.wpexplorer.com/create-widget-plugin-wordpress
class wicket_update_password extends WP_Widget {

	// Main constructor
	public function __construct() {
		parent::__construct(
			'wicket_update_password',
			__('Wicket Update Password', 'wicket'),
			array(
				'customize_selective_refresh' => true,
			)
		);
	}

	// The widget form (for the backend )
	public function form( $instance ) {

	}

	// Update widget settings
	public function update( $new_instance, $old_instance ) {

	}

	// Display the widget
	public function widget( $args, $instance ) {

	}

}

// Register the widget
function register_custom_widget_wicket_update_password() {
	register_widget('wicket_update_password');
}
add_action('widgets_init', 'register_custom_widget_wicket_update_password');
