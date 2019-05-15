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
        // No library available!
        return FALSE;
      }
      if (!isset($_SESSION['personUuid'])) {
        return FALSE;
      }

      // connect to the wicket api and get the current person
      $wicket_settings = get_wicket_settings();
      $client = new Client($app_key = '', $wicket_settings['jwt'], $wicket_settings['api_endpoint']);
      $client->authorize($_SESSION['personUuid']);
      $person = $client->people->fetch($_SESSION['personUuid']);

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

function get_person_id(){
  // get the SDK client from the wicket module.
  if (function_exists('wicket_api_client')) {
    $person_id = isset($_SESSION['personUuid']) ? $_SESSION['personUuid'] : '';
    return $person_id;
  }
}

function get_person(){
  static $person = null;
  if(is_null($person)) {
    $person_id = get_person_id();
    if ($person_id) {
      $client = wicket_api_client();
      $person = $client->people->fetch($person_id);
      return $person;
    }
  }
  return $person;
}

/**
 * Gets all people from wicket
 */
function get_people(){
  $client = wicket_api_client();
  $person = $client->people->all();
  return $person;
}

function get_person_by_id($uuid){
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

function get_address($id){
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

function get_interval($id){
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

function is_member(){
  static $has_membership = null;
  if(is_null($has_membership)) {
    $person = get_person();
    $roles = $person->role_names;
    $has_membership = in_array('member', $roles);
  }
  return $has_membership;
}

function person_name(){
  $person = get_person();
  return $person->given_name.' '.$person->family_name;
}

function get_order($uuid){
  $client = wicket_api_client();
  $order = $client->orders->fetch($uuid); // uuid of the order
  return $order;
}
