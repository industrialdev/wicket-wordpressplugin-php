<?php

use Wicket\Client;

/*
Plugin Name: Wicket CAS Role Sync
Description: wicket.io plugin that provides a custom action that listens for CAS login thus allowing role syncing capability on fire of that CAS login event - Requires WP-CASSIFY AND the "Base Wicket Plugin"
Author: Industrial
*/

/**------------------------------------------------------------------
 * Perform operations when the user is authed via CAS, but not yet in Wordpress
 * For testing purposes since $cas_user_data contains data straight from CAS
 * NOTE: Do not put person UUID here or anything else in $_SESSION for any reason.
 * PHP Sessions can be unreliable and collisions can happen.
 ------------------------------------------------------------------*/
function custom_action_before_auth_user_wordpress($cas_user_data) {
  // perhaps log CAS payload, etc
}
add_action('wp_cassify_before_auth_user_wordpress', 'custom_action_before_auth_user_wordpress', 1, 1);

/**------------------------------------------------------------------
 * Perform operations when the user is logging in to Wordpress
 ------------------------------------------------------------------*/
function sync_wicket_data() {
  // if they're logged in via CAS...
  if (wp_get_current_user()->user_login) {
    $client = wicket_api_client_current_user();
  	$person = wicket_current_person();

    $user = wp_get_current_user();
    // first remove all existing roles
    $user->set_role('');

    global $wp_roles;
    if (!isset($wp_roles)){
      $wp_roles = new WP_Roles();
    }

    // update user with roles from Wicket
    foreach ($person->role_names as $role) {
      // check if the role exists in WP already
      $role_exists = wp_roles()->is_role($role);
      if ($role_exists) {
        // assign the role to the user
        $user->add_role($role);
      }else {
        // clone the subsciber capabilities into a new role
        $subscriber_role = $wp_roles->get_role('subscriber');
        $role_machine = str_replace(' ','_',$role);
        $role_human = ucwords($role);
        $wp_roles->add_role($role_machine, $role_human, $subscriber_role->capabilities);
        // add new role to user
        $user->add_role($role_machine);
      }
    }

    // update the user with the appropriate metadata
    $user->nickname = $person->full_name;
    $user->display_name = $person->full_name;
    $user->first_name = $person->given_name;
    $user->user_email = $person->user['email'];
    $user->last_name = $person->family_name;
    wp_update_user($user);
  }
    return;
}
add_action('wp_login', 'sync_wicket_data');
