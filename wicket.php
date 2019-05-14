<?php

use Wicket\Client;

/*
Plugin Name: Wicket
Description: Base Wicket Plugin
Author: Industrial
*/

require_once('classes/class_wicket_settings.php');
require_once('includes/helpers.php');
require_once('includes/assets.php');


// function custom_rewrite_onboarding() {
//   $onboardingPage = get_page_by_path('on-boarding');
//   add_rewrite_rule('^on-boarding/.+', sprintf('index.php?page_id=%s', $onboardingPage->ID), 'top');
//
//   $orderDetailsPage = get_page_by_path('order-details');
//   add_rewrite_rule('^order-details/(.+)', sprintf('index.php?page_id=%s&order_id=$matches[1]', $orderDetailsPage->ID), 'top');
// }
// add_action('init', 'custom_rewrite_onboarding');
