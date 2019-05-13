<?php

use Wicket\Client;

/*
Plugin Name: Wicket
Description: Base Wicket Plugin
Author: Industrial
*/

require_once('classes/class_wicket_settings.php');

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


// function custom_rewrite_onboarding() {
//   $onboardingPage = get_page_by_path('on-boarding');
//   add_rewrite_rule('^on-boarding/.+', sprintf('index.php?page_id=%s', $onboardingPage->ID), 'top');
//
//   $orderDetailsPage = get_page_by_path('order-details');
//   add_rewrite_rule('^order-details/(.+)', sprintf('index.php?page_id=%s&order_id=$matches[1]', $orderDetailsPage->ID), 'top');
// }
// add_action('init', 'custom_rewrite_onboarding');
