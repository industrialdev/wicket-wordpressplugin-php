<?php

use Wicket\Client;

/**------------------------------------------------------------------
* Get wicket environment info from Wicket settings page in the admin
------------------------------------------------------------------*/
function get_wicket_settings(){
  $settings = [];
  $environment = get_option('wicket_admin_settings_environment');
  switch ($environment[0]) {
    case 'prod':
      $settings['api_endpoint'] = get_option('wicket_admin_settings_prod_api_endpoint');
      $settings['jwt'] = get_option('wicket_admin_settings_prod_secret_key');
      $settings['person_id'] = get_option('wicket_admin_settings_prod_person_id');
      $settings['parent_org'] = get_option('wicket_admin_settings_prod_parent_org');
      $settings['wicket_admin'] = get_option('wicket_admin_settings_prod_wicket_admin');
      break;
    default:
      $settings['api_endpoint'] = get_option('wicket_admin_settings_stage_api_endpoint');
      $settings['jwt'] = get_option('wicket_admin_settings_stage_secret_key');
      $settings['person_id'] = get_option('wicket_admin_settings_stage_person_id');
      $settings['parent_org'] = get_option('wicket_admin_settings_stage_parent_org');
      $settings['wicket_admin'] = get_option('wicket_admin_settings_stage_wicket_admin');
      break;
  }
  return $settings;
}

/**------------------------------------------------------------------
* Loads the PHP SDK
------------------------------------------------------------------*/
function wicket_api_client() {
  static $client = null;

  if (is_null($client)) {
    try {
      if (!class_exists('\Wicket\Client')) {
        // No SDK available!
        return FALSE;
      }

      // connect to the wicket api and get the current person
      $wicket_settings = get_wicket_settings();
      $client = new Client($app_key = '', $wicket_settings['jwt'], $wicket_settings['api_endpoint']);
      $client->authorize($wicket_settings['person_id']);
      $person = $client->people->fetch($wicket_settings['person_id']);

      // test the endpoint before returning the client to ensure it's up
      $client->get($wicket_settings['api_endpoint']);
    }
    catch (Exception $e) {
      // don't return the $client unless the API is up.
      return false;
    }
  }
  return $client;
}

/**------------------------------------------------------------------
* Get wicket client, authorized as the current user.
* You'll want to use this most of the time, to give context to person
* operations as well as respect permissions on the Wicket side
------------------------------------------------------------------*/
function wicket_api_client_current_user() {
  $client = wicket_api_client();

  if ($client) {
    $person_id = wicket_current_person_uuid();

    if ($person_id) {
      $client->authorize($person_id);
    } else {
      $client = null;
    }
  }

  return $client;
}

/**------------------------------------------------------------------
* Get current person wicket personUuid
------------------------------------------------------------------*/
function wicket_current_person_uuid(){
  // get the SDK client from the wicket module.
  if (function_exists('wicket_api_client')) {
    $person_id = isset($_SESSION['personUuid']) ? $_SESSION['personUuid'] : '';
    return $person_id;
  }
}

/**------------------------------------------------------------------
* Get current person wicket
------------------------------------------------------------------*/
function wicket_current_person(){
  static $person = null;
  if(is_null($person)) {
    $person_id = wicket_current_person_uuid();
    if ($person_id) {
      $client = wicket_api_client();
      $person = $client->people->fetch($person_id);
      return $person;
    }
  }
  return $person;
}

/**------------------------------------------------------------------
* Gets all people from wicket
------------------------------------------------------------------*/
function wicket_get_all_people(){
  $client = wicket_api_client();
  $person = $client->people->all();
  return $person;
}

/**------------------------------------------------------------------
* Get person by UUID
------------------------------------------------------------------*/
function wicket_get_person_by_id($uuid){
  static $person = null;
  if(is_null($person)) {
    if ($uuid) {
      $client = wicket_api_client();
      $person = $client->people->fetch($uuid);
      return $person;
    }
  }
  return $person;
}

/**------------------------------------------------------------------
* Get email by id
------------------------------------------------------------------*/
function wicket_get_address($id){
  static $address = null;
  if(is_null($address)) {
    if ($id) {
      $client = wicket_api_client();
      $address = $client->addresses->fetch($id);
      return $address;
    }
  }
  return $address;
}

/**------------------------------------------------------------------
* Get Interval by id
------------------------------------------------------------------*/
function wicket_get_interval($id){
  static $interval = null;
  if(is_null($interval)) {
    if ($id) {
      $client = wicket_api_client();
      try {
        $interval = $client->intervals->fetch($id);
      } catch (Exception $e) {
        $interval = false;
      }
      return $interval;
    }
  }
  return $interval;
}

/**------------------------------------------------------------------
* Check if current logged in person has the 'member' role
------------------------------------------------------------------*/
function wicket_is_member(){
  static $has_membership = null;
  if(is_null($has_membership)) {
    $person = wicket_current_person();
    $roles = $person->role_names;
    $has_membership = in_array('member', $roles);
  }
  return $has_membership;
}

/**------------------------------------------------------------------
* Build firstname/lastname from person object of current user
------------------------------------------------------------------*/
function wicket_person_name(){
  $person = wicket_current_person();
  return $person->given_name.' '.$person->family_name;
}

/**------------------------------------------------------------------
* Get Wicket orders for person by person UUID
------------------------------------------------------------------*/
function wicket_get_order($uuid){
  $client = wicket_api_client();
  $order = $client->orders->fetch($uuid); // uuid of the order
  return $order;
}
