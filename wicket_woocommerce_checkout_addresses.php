<?php

use Wicket\Client;

/*
Plugin Name: Wicket WooCommerce Checkout Addresses
Description: wicket.io plugin that provides the ability for users to prepopulate addresses from their person record for the checkout billing and shipping fields. It also allows for saving addresses to the person record on checkout completion based on the info in either the shipping or billing fields
Author: Industrial
*/

// ---------------------------------------------------------------------------------
// ADD FIELDS ABOVE CHECKOUT FORM TO ALLOW USER TO CHOOSE FROM EXISTING ADDRESSES IN WICKET
// https://www.businessbloomer.com/woocommerce-visual-hook-guide-checkout-page/
// ---------------------------------------------------------------------------------
add_action('woocommerce_before_checkout_form', 'wicket_prepopulate_addresses', 10);

function wicket_prepopulate_addresses() {
  $client = wicket_api_client();

  // we only want to run this for logged in users
  if (!wp_get_current_user()->user_login || !$client) {
    return;
  }

  $locale = defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE == 'fr' ? 'fr' : 'en';

  // Get existing person addresses
  $person_id = wicket_current_person_uuid();
  if ($person_id) {
  	$wicket_current_person = $client->people->fetch($person_id);
  }

  $addresses = [];
  if ($wicket_current_person->relationship('addresses')) {
    $resource_types = $client->get('resource_types');
  	foreach ($wicket_current_person->relationship('addresses') as $relationship) {
  		foreach ($wicket_current_person->included() as $included) {
  			if ($relationship->id == $included['id']) {
  				// get 'type' label from resource_types
  				$type = array_filter($resource_types['data'], function($val) use ($included) {
  					return $val['attributes']['slug'] == $included['attributes']['type'] ? true : false;
  				});
  				$included['attributes']['type_label'] = reset($type)['attributes']['name_'.$locale];
  				$addresses[] = $included;
  			}
  		}
  	}
  }

  if ($addresses) {
    echo "<h3>".__('Pre-populate Addresses', 'woocommerce')."</h3>";
    echo "<div class='row'>";
    echo '<form action="" method="post" class="wicket__address-form col-lg-6">';
    echo "<label class='form__label'>".__('Billing Address', 'woocommerce')."</label>";
    echo "<select class='form__select' name='prepopulate_billing_address'>";
    echo "<option value=''>".__('-- Choose Address --', 'woocommerce')."</option>";
    foreach ($addresses as $address) {
      echo "<option value='".$address['id']."'>".$address['attributes']['formatted_address_label']."</option>";
    }
    echo "</select>";
    echo "<p><input type='submit' class='button alt' value='".__('Select', 'woocommerce')."'></p>";
    echo "</form>";


    // only show shipping field if the cart has shipping fields displayed
    if (WC()->cart->needs_shipping()) {
      echo '<form action="" method="post" class="wicket__address-form col-lg-6">';

      echo "<label class='form__label'>".__('Shipping Address', 'woocommerce')."</label>";
      echo "<select class='form__select' name='prepopulate_shipping_address'>";
      echo "<option value=''>".__('-- Choose Address --', 'woocommerce')."</option>";
      foreach ($addresses as $address) {
        echo "<option value='".$address['id']."'>".$address['attributes']['formatted_address_label']."</option>";
      }
      echo "</select>";
      echo "<p><input type='submit' class='button alt' value='".__('Select', 'woocommerce')."'></p>";
      echo "</form>";
    }
    echo "</div>";
  }
}


// --------------------------------------------------------------------------------------------------------------
// PRE-POPULATE WOOCOMMERCE CHECKOUT FIELDS
// --------------------------------------------------------------------------------------------------------------
add_filter('woocommerce_checkout_get_value', function($input, $key) {
  $client = wicket_api_client();

  // we only want to run this for logged in users
  if (!wp_get_current_user()->user_login || !$client) {
    return;
  }

  // Get existing wicket person that's logged in
  $person = wicket_current_person();

  if (isset($_POST['prepopulate_billing_address'])) {
    $address = wicket_get_address($_POST['prepopulate_billing_address']);
    switch ($key) :
      case 'billing_first_name':
  			return $person->given_name;
  		break;

  		case 'billing_last_name':
  			return $person->family_name;
  		break;

  		case 'billing_email':
  			return $person->primary_email_address;
  		break;

      case 'billing_company':
        return $address->company_name;
      break;

      case 'billing_address_1':
        return $address->address1;
      break;

      case 'billing_address_2':
        return $address->address2;
      break;

      case 'billing_city':
        return $address->city;
      break;

      case 'billing_postcode':
        return $address->zip_code;
      break;

      case 'billing_state':
        return $address->state_name;
      break;

      case 'billing_country':
        return $address->country_code;
      break;

      // always clear out the rest of fields
      // default:
      //   return '';
      // break;

    endswitch;
  }

  if (isset($_POST['prepopulate_shipping_address'])) {
    // if they are trying to populate the shipping address, open that section
    // https://stackoverflow.com/questions/39710591/displaying-by-default-shipping-fields-on-checkout-page
    add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true');

    $address = wicket_get_address($_POST['prepopulate_shipping_address']);
    switch ($key) :
      case 'shipping_first_name':
  			return $person->given_name;
  		break;

  		case 'shipping_last_name':
  			return $person->family_name;
  		break;

  		case 'shipping_email':
  			return $person->primary_email_address;
  		break;

      case 'shipping_company':
        return $address->company_name;
      break;

      case 'shipping_address_1':
        return $address->address1;
      break;

      case 'shipping_address_2':
        return $address->address2;
      break;

      case 'shipping_city':
        return $address->city;
      break;

      case 'shipping_postcode':
        return $address->zip_code;
      break;

      case 'shipping_state':
        return $address->state_name;
      break;

      case 'shipping_country':
        return $address->country_code;
      break;

      // always clear out the rest of fields
      // default:
      //   return '';
      // break;

    endswitch;
  }

  // by default always clear out "save new address" fields and prepopulate name from wicket
	switch ($key) :
		case 'billing_first_name':
			return $person->given_name;
		break;

		case 'billing_last_name':
			return $person->family_name;
		break;

		case 'billing_email':
			return $person->primary_email_address;
		break;

		case 'billing_save_address_to_wicket':
		case 'shipping_save_address_to_wicket':
			return '';
		break;
	endswitch;

}, 10, 2);


// --------------------------------------------------------------------------------------------------------------
// MAKE THE BILLING PHONE FIELD OPTIONAL, OTHERWISE WE'D PROBABLY HAVE TO PREPOPULATE IT AS WELL
// https://www.tychesoftwares.com/how-to-make-fields-mandatory-or-optional-on-the-woocommerce-checkout-page/
// --------------------------------------------------------------------------------------------------------------
add_filter( 'woocommerce_billing_fields', 'make_billing_phone_not_required');
function make_billing_phone_not_required($fields) {
  $fields['billing_phone']['required'] = false;
  return $fields;
}


// --------------------------------------------------------------------------------------------------------------
// ADD NEW FIELDS TO BILLING AND SHIPPING SECTIONS TO ALLOW THE USER TO SAVE EITHER OF THOSE ADDRESSES BACK TO WICKET
// https://woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/#section-6
// --------------------------------------------------------------------------------------------------------------
add_filter('woocommerce_checkout_fields' , 'add_checkout_fields');
function add_checkout_fields($fields) {
  $client = wicket_api_client();

  // we only want to run this for logged in users
  if (!wp_get_current_user()->user_login || !$client) {
    return $fields;
  }

  // get available address types from wicket
  $resource_types = $client->get('resource_types');
  $address_types = array_filter($resource_types['data'], function($val){
  	return $val['attributes']['available_for_entity'] == 'all_entities' && $val['attributes']['resource_type'] == 'addresses' ? true : false;
  });

  $address_type_options = ['' => __('Choose an Option', 'woocommerce')];
  $locale = defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE == 'fr' ? 'fr' : 'en';
  foreach ($address_types as $address_type) {
    $address_type_options[$address_type['attributes']['slug']] = $address_type['attributes']['name_'.$locale];
  }

  // add address type so we can store in wicket
  $fields['billing']['billing_address_address_type'] = array(
    'label'     => __('Address Type', 'woocommerce'),
    'required'  => false,
    'type' => 'select',
    'class'     => array('form-row-wide'),
    'clear'     => true,
    'options' => $address_type_options
  );
  // add field to billing address asking to save this to wicket
  $fields['billing']['billing_save_address_to_wicket'] = array(
    'label'     => __('Save New Address', 'woocommerce'),
    'required'  => false,
    'type' => 'checkbox',
    'class'     => array('form-row-wide'),
    'clear'     => true
  );

  // add address type so we can store in wicket
  $fields['shipping']['shipping_address_address_type'] = array(
    'label'     => __('Address Type', 'woocommerce'),
    'required'  => false,
    'type' => 'select',
    'class'     => array('form-row-wide'),
    'clear'     => true,
    'options' => $address_type_options
  );
  // add field to shipping address asking to save this to wicket
  $fields['shipping']['shipping_save_address_to_wicket'] = array(
    'label'     => __('Save New Address', 'woocommerce'),
    'required'  => false,
    'type' => 'checkbox',
    'class'     => array('form-row-wide'),
    'clear'     => true
  );

  return $fields;
}


// --------------------------------------------------------------------------------------------------------------
// ON CHECKOUT, AFTER VALIDATION, BEFORE ORDER IS CREATED, SAVE ADDRESSES TO WICKET IF THE USER CHECKED THAT OFF
// https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-checkout.html#source-view.388
// this fires late enough to safely store the addresses (after form validates)
// --------------------------------------------------------------------------------------------------------------
add_action( 'woocommerce_checkout_create_order', 'woocommerce_payment_complete_store_addresses' );
function woocommerce_payment_complete_store_addresses() {
  $client = wicket_api_client();

  // we only want to run this for logged in users
  if (!wp_get_current_user()->user_login || !$client) {
    return;
  }

  $save_billing_address = $_POST['billing_save_address_to_wicket'] ?? '';
  $billing_address_type = $_POST['billing_address_address_type'];

  $save_shipping_address = $_POST['shipping_save_address_to_wicket'] ?? '';
  $shipping_address_type = $_POST['shipping_address_address_type'];

  if ($save_billing_address) {
    $billing_payload = [
      'data' => [
        'type' => 'addresses',
        'attributes' => [
          'type' => $billing_address_type ?? 'work',
          'company_name' => $_POST['billing_company'],
          'address1' => $_POST['billing_address_1'],
          'address2' => $_POST['billing_address_2'],
          'city' => $_POST['billing_city'],
          'country_code' => $_POST['billing_country'],
          'state_name' => $_POST['billing_state'],
          'zip_code' => $_POST['billing_postcode']
        ]
      ]
    ];
    wicket_create_person_address(wicket_current_person_uuid(), $billing_payload);
  }

  if ($save_shipping_address) {
    $shipping_payload = [
      'data' => [
        'type' => 'addresses',
        'attributes' => [
          'type' => $shipping_address_type ?? 'work',
          'company_name' => $_POST['shipping_company'],
          'address1' => $_POST['shipping_address_1'],
          'address2' => $_POST['shipping_address_2'],
          'city' => $_POST['shipping_city'],
          'country_code' => $_POST['shipping_country'],
          'state_name' => $_POST['shipping_state'],
          'zip_code' => $_POST['shipping_postcode']
        ]
      ]
    ];
    wicket_create_person_address(wicket_current_person_uuid(), $shipping_payload);
  }
}
