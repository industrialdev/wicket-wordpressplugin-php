<?php

use Wicket\Client;

/*
Plugin Name: Wicket CAS Role Sync
Description: wicket.io plugin that provides a custom action that listens for CAS login thus allowing role syncing capability on fire of that CAS login event - Requires WP-CASSIFY AND the "Base Wicket Plugin"
Author: Industrial
*/

/**------------------------------------------------------------------
 * Perform operations when the user is authed via CAS, but not yet in Wordpress
 ------------------------------------------------------------------*/
function custom_action_before_auth_user_wordpress($cas_user_data) {
  // store UUID in session to be used later for syncing roles, etc.
  $_SESSION['personUuid'] = $cas_user_data['personUuid'];
}
add_action('wp_cassify_before_auth_user_wordpress', 'custom_action_before_auth_user_wordpress', 1, 1);

/**------------------------------------------------------------------
 * Perform operations when the user is logging in to Wordpress
 ------------------------------------------------------------------*/
function sync_wicket_data() {
  // if they're logged in via CAS...
  if (isset($_SESSION['personUuid'])) {
    // connect to the wicket api and get the current person
    $wicket_settings = get_wicket_settings();
    $client = new Client($app_key = '', $wicket_settings['jwt'], $wicket_settings['api_endpoint']);
    $client->authorize($_SESSION['personUuid']);
    $person = $client->people->fetch($_SESSION['personUuid']);

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
