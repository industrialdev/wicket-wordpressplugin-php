<?php

use Wicket\Client;

/*
Plugin Name: Wicket Contact Information
Description: wicket.io plugin responsible for providing a widget containing the contact information form from Wicket
Author: Industrial
*/

// The widget class
// http://www.wpexplorer.com/create-widget-plugin-wordpress
class wicket_contact_information extends WP_Widget {

	// Main constructor
	public function __construct()
	{
		parent::__construct(
			'wicket_contact_information',
			__('Wicket Contact Information', 'wicket'),
			array(
				'customize_selective_refresh' => true,
			)
		);
	}

	public function form( $instance ) {
		return $instance;
	}

	public function update( $new_instance, $old_instance ) {
		return $old_instance;
	}

	// Display the widget
	public function widget($args, $instance)
	{
		$output = '';
		$client = wicket_api_client_current_user();
		$wicket_settings = get_wicket_settings();
		$wicket_admin = $wicket_settings['wicket_admin'] ? $wicket_settings['wicket_admin'].'/dist/widgets.js' : null;
		$access_token = $client ? $client->getAccessToken() : '' ;
		$api_root = $client ? rtrim($client->getApiEndpoint(), '/') : '' ;
		$language = strtok(get_bloginfo("language"), '-');
		$person_id = wicket_current_person_uuid();

		echo "
		<script type='text/javascript'>
		window.Wicket = function(doc, tag, id, script) {
		  var w = window.Wicket || {};
		  if (doc.getElementById(id)) return w;
		  var ref = doc.getElementsByTagName(tag)[0];
		  var js = doc.createElement(tag);
		  js.id = id;
		  js.src = script;
		  ref.parentNode.insertBefore(js, ref);
		  w._q = [];
		  w.ready = function(f) {
		    w._q.push(f)
		  };
		  return w
		}(document, 'script', 'wicket-widgets', '$wicket_admin');
		</script>";

		echo "
		<div id='wicket-contact-information-container'></div>
		<script type='text/javascript'>
				Wicket.ready(function () {
					Wicket.widgets.createPersonProfile({
						'apiRoot':'$api_root',
						'accessToken':'$access_token',
						'lang':'$language',
						'personId':'$person_id',
						'rootEl':document.getElementById('wicket-contact-information-container')
					});
				});
		</script>";
	}

}



// Register the widget
function register_custom_widget_wicket_contact_information() {
	register_widget('wicket_contact_information');
}
add_action('widgets_init', 'register_custom_widget_wicket_contact_information');
