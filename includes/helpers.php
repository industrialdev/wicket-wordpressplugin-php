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
  try {
    if (!class_exists('\Wicket\Client')) {
      // No SDK available!
      return FALSE;
    }

    // connect to the wicket api and get the current person
    $wicket_settings = get_wicket_settings();
    $client = new Client($app_key = '', $wicket_settings['jwt'], $wicket_settings['api_endpoint']);
    $client->authorize($wicket_settings['person_id']); 
  }catch (Exception $e) {
    // don't return the $client unless the API is up.
    return false;
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
* Get wicket client, authorized as the current user.
* Taken from the wicket SDK (it's used as a protected method there)
------------------------------------------------------------------*/
function wicket_access_token_for_person($person_id, $expiresIn = 60 * 60 * 8) {
  $settings = get_wicket_settings();
  $iat = time();

  $token = [
    'sub' => $person_id,
    'iat' => $iat,
    'exp' => $iat + $expiresIn,
  ];

  return Firebase\JWT\JWT::encode($token, $settings['jwt'], 'HS256');
}

/**------------------------------------------------------------------
* Get current person wicket personUuid
------------------------------------------------------------------*/
function wicket_current_person_uuid(){
  // get the SDK client from the wicket module.
  if (function_exists('wicket_api_client')) {
    $person_id = wp_get_current_user()->user_login;
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
      $client = wicket_api_client_current_user();
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
  if ($uuid) {
    $client = wicket_api_client();
    $person = $client->people->fetch($uuid);
    return $person;
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

/**------------------------------------------------------------------
* Get all organizations from Wicket
------------------------------------------------------------------*/
function wicket_get_organizations(){
  $client = wicket_api_client();
  static $organizations = null;
  // prepare and memoize all organizations from Wicket
  if (is_null($organizations)) {
    $organizations = $client->get('organizations');
  }
  if ($organizations) {
    return $organizations;
  }
}

/**------------------------------------------------------------------
* Get organization by UUID from Wicket
------------------------------------------------------------------*/
function wicket_get_organization($uuid){
  $client = wicket_api_client();
  $organization = $client->get('organizations/'.$uuid);
  if ($organization) {
    return $organization;
  }
}

/**------------------------------------------------------------------
* Get all "connections" (relationships) of a Wicket person
------------------------------------------------------------------*/
function wicket_get_person_connections(){
  $client = wicket_api_client();
  $person_id = wicket_current_person_uuid();
  if ($person_id) {
    $client = wicket_api_client();
    $person = $client->people->fetch($person_id);
  }
  static $connections = null;
  // prepare and memoize all connections from Wicket
  if (is_null($connections)) {
    $connections = $client->get('people/'.$person->id.'/connections?filter%5Bconnection_type_eq%5D=all&sort=-created_at');
  }
  if ($connections) {
    return $connections;
  }
}

/**------------------------------------------------------------------
* Get all "connections" (relationships) of a Wicket person by UUID
------------------------------------------------------------------*/
function wicket_get_person_connections_by_id($uuid){
  $client = wicket_api_client();
  static $connections = null;
  // prepare and memoize all connections from Wicket
  if (is_null($connections)) {
    $connections = $client->get('people/'.$uuid.'/connections?filter%5Bconnection_type_eq%5D=all&sort=-created_at');
  }
  if ($connections) {
    return $connections;
  }
}

/**------------------------------------------------------------------
* Get all "connections" (relationships) of a Wicket org by UUID
------------------------------------------------------------------*/
function wicket_get_org_connections_by_id($uuid){
  $client = wicket_api_client();
  static $connections = null;
  // prepare and memoize all connections from Wicket
  if (is_null($connections)) {
    $connections = $client->get('organizations/'.$uuid.'/connections?filter%5Bconnection_type_eq%5D=all&sort=-created_at');
  }
  if ($connections) {
    return $connections;
  }
}

/**------------------------------------------------------------------
* Get all JSON Schemas from Wicket
------------------------------------------------------------------*/
function wicket_get_schemas(){
  $client = wicket_api_client();
  static $schemas = null;
  // prepare and memoize all schemas from Wicket
  if (is_null($schemas)) {
    $schemas = $client->get('json_schemas');
  }
  if ($schemas) {
    return $schemas;
  }
}

/**------------------------------------------------------------------
* Load options from a schema based
* on a schema entry found using wicket_get_schemas()
------------------------------------------------------------------*/
function wicket_get_schemas_options($schema, $field, $sub_field){
  $language = strtok(get_bloginfo("language"), '-');
  $return = [];

  // -----------------------------
  // GET VALUES
  // -----------------------------

  // single value
  if (isset($schema['attributes']['schema']['properties'][$field]['enum'])) {
    $counter = 0;
    foreach ($schema['attributes']['schema']['properties'][$field]['enum'] as $key => $value) {
      $return[$counter]['key'] = $value;
      $counter++;
    }
  }
  // multi-value
  if (isset($schema['attributes']['schema']['properties'][$field]['items']['enum'])) {
    $counter = 0;
    foreach ($schema['attributes']['schema']['properties'][$field]['items']['enum'] as $key => $value) {
      $return[$counter]['key'] = $value;
      $counter++;
    }
  }
  // if field is using ui_schema, get keys
  if (isset($schema['attributes']['schema']['oneOf'][0]['properties'][$field]['items']['enum'])) {
    $counter = 0;
    foreach ($schema['attributes']['schema']['oneOf'][0]['properties'][$field]['items']['enum'] as $key => $value) {
      $return[$counter]['key'] = $value;
      $counter++;
    }
  }
  // if field is using a repeater type field with 'move up/down and remove rows', get keys
  if (isset($schema['attributes']['schema']['properties'][$field]['items']['properties'][$sub_field]['enum'])) {
    $counter = 0;
    foreach ($schema['attributes']['schema']['properties'][$field]['items']['properties'][$sub_field]['enum'] as $key => $value) {
      $return[$counter]['key'] = $value;
      $counter++;
    }
  }
  // if field is using an object type field, get keys
  if (isset($schema['attributes']['schema']['properties'][$field]['oneOf'][0]['properties'][$sub_field]['enum'])) {
    $counter = 0;
    foreach ($schema['attributes']['schema']['properties'][$field]['oneOf'] as $key => $value) {
      $return[$counter]['key'] = $value['properties'][$sub_field]['enum'][0];
      $counter++;
    }
  }
  // if field is using an object type field with values depending on another, get keys (these are buried deeper)
  if (isset($schema['attributes']['schema']['properties'][$field]['items']['oneOf'][0])) {
    $counter = 0;
    foreach ($schema['attributes']['schema']['properties'][$field]['items']['oneOf'] as $key => $value) {
      if (array_key_exists($sub_field, $value['properties'])) {
        foreach ($value['properties'][$sub_field]['items']['enum'] as $sub_value) {
          $return[$counter]['key'] = $sub_value;
          $counter++;
        }
      }
    }
  }

  // -----------------------------
  // GET LABELS
  // -----------------------------

  // get label values from ui_schema
  if (isset($schema['attributes']['ui_schema'][$field]['ui:i18n']['enumNames'][$language])) {
    $counter = 0;
    foreach ($schema['attributes']['ui_schema'][$field]['ui:i18n']['enumNames'][$language] as $key => $value) {
      $return[$counter]['value'] = $value;
      $counter++;
    }
  }
  // get label values from ui_schema
  if (isset($schema['attributes']['ui_schema'][$field]['items'][$sub_field]['ui:i18n']['enumNames'][$language])) {
    $counter = 0;
    foreach ($schema['attributes']['ui_schema'][$field]['items'][$sub_field]['ui:i18n']['enumNames'][$language] as $key => $value) {
      $return[$counter]['value'] = $value;
      $counter++;
    }
  }
  // if field is using a repeater type field with 'move up/down and remove rows', get labels
  if (isset($schema['attributes']['schema']['properties'][$field]['items']['properties'][$sub_field]['enumNames'])) {
    $counter = 0;
    foreach ($schema['attributes']['schema']['properties'][$field]['items']['properties'][$sub_field]['enumNames'] as $key => $value) {
      $return[$counter]['value'] = $value;
      $counter++;
    }
  }
  return $return;
}

/**------------------------------------------------------------------
 * Gets all the options for a field within a json schema
 * Parent field is the accordion in wicket in additional info
 * Field is a field within the accordion
 * Sub Field is optional. Would be needed if using repeater fields with objects as values
 ------------------------------------------------------------------*/
function wicket_get_schema_field_values($parent_field, $field, $sub_field = ''){
	$schemas = wicket_get_schemas();
	if ($schemas) {
		foreach ($schemas['data'] as $key => $schema) {
			if ($schema['attributes']['key'] == $parent_field) {
				$schema = $schemas['data'][$key];
				break;
			}
		}
		$options = wicket_get_schemas_options($schema, $field, $sub_field);
		if ($options) {
			return $options;
		}
	}
}

/**------------------------------------------------------------------
 * Used to build data_fields array during form submission (common for additional_info)
 * Uses passed in $data_fields as reference to build on to
 * $data_fields = the array we build to pass to the api
 * $field = The field within each schema (under an accordion in wicket)
 * $schema = The ID for the accordion field (group of fields)
 * $type = string, array, int, boolean, object or readonly
 * $entity = usually the preloaded org or person object from the API
 ------------------------------------------------------------------*/
function wicket_add_data_field(&$data_fields, $field, $schema, $type, $entity = ''){
  if (isset($_POST[$field])) {
    $value = $_POST[$field];

		// remove empty arrays (likely select fields with the "choose option" set)
		if ($type == 'array' && empty(array_filter($value))) {
			return false;
		}

		// remove empty strings (likely select fields with the "choose option" set)
		if ($type == 'string' && $value == '') {
			return false;
		}

    // add conversion for booleans
    if ($type == 'boolean' && $_POST[$field] == '1') {
      $value = true;
    }
    if ($type == 'boolean' && $_POST[$field] == '0') {
      $value = false;
    }
    // if boolean is posted but no value, ignore it
    if ($type == 'boolean' && $_POST[$field] == '') {
      return false;
    }
		// cast ints for the API (like year values)
    if ($type == 'int' && $value) {
      $value = (int)$value;
    }elseif($type == 'int' && !$value) {
      // dont include int fields if we want to blank them out
      return false;
    }

		// convert object to arrays, replacing passed-in values looping over by reference
    if ($type == 'object' && $value) {
			foreach ($value as $key => &$index) {
				$index = (array)json_decode(stripslashes($index));
			}
    }

    // keep the fields for each schema together by keying the data_fields array by the schema id
    // It still seems to work through the API this way, even though the wicket admin uses zero based array indexes
    $data_fields[$schema]['value'][$field] = $value;
    $data_fields[$schema]['$schema'] = $schema;
  }else {
    // pass empty array for multi-value fields to clear them out if no options are present
    if ($type == 'array' || $type == 'object') {
      $value = [];
    }
    // unset empty string if no value set. Sometimes happens to radio buttons with no value
    if ($type == 'string') {
      return false;
    }

    // unset empty boolean if no value set. Sometimes happens to radio buttons with no value
    if ($type == 'boolean') {
      return false;
    }

    // don't return a field if array is being used using "oneOf" to clear them out if no options are present
		// these are typically used in Wicket for initial yes/no radios followed by a field if choose "yes"
    if ($type == 'array_oneof') {
			return false;
    }

    // if this field is being used as a "readonly" value on the edit form page,
    // pass on the original value(s) within the schema otherwise they'll be emptied if not passed on PATCH
    if ($type == 'readonly') {
      // make sure, usually on new accounts, that there is even AI fields to read from
      // data_fields will likely be completely empty on new accounts
      if (!empty((array)$entity->data_fields) && array_search($schema, array_column((array)$entity->data_fields, '$schema'))) {
        foreach ($entity->data_fields as $df) {
          if ($df['$schema'] == $schema) {
            // look for existing value, if there is one, else ignore this field
            if (isset($df['value'][$field])) {
              $value = $df['value'][$field];
            }else {
              return false;
            }
          }
        }
      }else {
        return false;
      }
    }

    $data_fields[$schema]['value'][$field] = $value ?? '';
    $data_fields[$schema]['$schema'] = $schema;
  }
}

/**------------------------------------------------------------------
* Assign a person to a membership on an org
------------------------------------------------------------------*/
function wicket_assign_person_to_org_membership($person_id, $membership_id, $org_membership_id, $org_membership){
	$client = wicket_api_client();
	// build payload to assign person to the membership on the org

	$payload = [
		'data' => [
			'type' => 'person_memberships',
			'attributes' => [
				'starts_at' => $org_membership['data']['attributes']['starts_at'],
				"ends_at" => $org_membership['data']['attributes']['ends_at'],
				"status" => 'Active'
			],
			'relationships' => [
				'person' => [
					'data' => [
						'id' => $person_id,
						'type' => 'people'
					]
				],
				'membership' => [
					'data' => [
						'id' => $membership_id,
						'type' => 'memberships'
					]
				],
				'organization_membership' => [
					'data' => [
						'id' => $org_membership_id,
						'type' => 'organization_memberships'
					]
				]
			]
		]
	];

	try {
		$client->post('person_memberships', ['json' => $payload]);
		return true;
	} catch (Exception $e) {
		$errors = json_decode($e->getResponse()->getBody())->errors;
		// echo "<pre>";
		// print_r($errors);
		// echo "</pre>";
		// die;
	}
}

/**------------------------------------------------------------------
* Unassign a person from a membership on an org
------------------------------------------------------------------*/
function wicket_unassign_person_from_org_membership($person_membership_id){
	$client = wicket_api_client();
	try {
		$client->delete("person_memberships/$person_membership_id");
		return true;
	} catch (Exception $e) {
		$errors = json_decode($e->getResponse()->getBody())->errors;
		// echo "<pre>";
		// print_r($errors);
		// echo "</pre>";
	}
}

/**------------------------------------------------------------------
 * Send email to user letting them know of a team assignment
 * for their account by an organization manager
 ------------------------------------------------------------------*/
function send_person_to_team_assignment_email($user, $org_id){
  $org = wicket_get_organization($org_id);
  $lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';
  $person = wicket_get_person_by_id($user->data->user_login);

	if ($org) {
	  $organization_name = $org['data']['attributes']['legal_name_'.$lang];
	}

	$to = $person->primary_email_address;
	$first_name = $person->given_name;
  $last_name = $person->family_name;
	$subject = "Welcome to NJBIA!";
	$body = "Hi $first_name, <br><br>
	You have been assigned a membership as part of $organization_name.
	<br>
	<br>
	Visit njbia.org and login to complete your profile and explore your member benefits.
	<br>
	<br>
	Thank you,
	<br>
	<br>
	New Jersey Business & Industry Association";
	$headers = array('Content-Type: text/html; charset=UTF-8');
	$headers[] = 'From: New Jersey Business & Industry Association <info@njbia.org>';
	wp_mail($to,$subject,$body,$headers);
}

/**------------------------------------------------------------------
 * Send email to NEW user letting them know of a team assignment
 * for their account by an organization manager
 ------------------------------------------------------------------*/
function send_new_person_to_team_assignment_email($first_name, $last_name, $email, $org_id){
  $org = wicket_get_organization($org_id);
  $lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

	if ($org) {
	  $organization_name = $org['data']['attributes']['legal_name_'.$lang];
	}

	$to = $email;
	$subject = "Welcome to NJBIA!";
	$body = "Hi $first_name, <br><br>
	You have been assigned a membership as part of $organization_name.
	<br>
	<br>
	You will soon receive an Account Confirmation email with instructions on how to finalize your login account.
	Once you have confirmed your account, visit njbia.org and login to complete your profile and explore your member benefits.
	<br>
	<br>
	Thank you,
	<br>
	<br>
	New Jersey Business & Industry Association";
	$headers = array('Content-Type: text/html; charset=UTF-8');
	$headers[] = 'From: New Jersey Business & Industry Association <info@njbia.org>';
	wp_mail($to,$subject,$body,$headers);
}

/**------------------------------------------------------------------
 * Create basic person record, no password
 ------------------------------------------------------------------*/
 function wicket_create_person($given_name, $family_name, $address = '', $password = '', $password_confirmation = '', $job_title = '', $gender = '', $additional_info = []){
  $client = wicket_api_client();

  $wicket_settings = get_wicket_settings();
  $parent_org = $wicket_settings['parent_org'];
  $args = [
    'query' => [
      'filter' => [
        'alternate_name_en_eq' => $parent_org
      ],
      'page' => [
        'number' => 1,
        'size' => 1,
      ]
    ]
  ];
  $parent_org = $client->get('organizations', $args);
  if ($parent_org) {
    $parent_org = $parent_org['data'][0]['id'];
  }

  // build person payload
  $payload = [
    'data' => [
      'type' => 'people',
      'attributes' => [
        'given_name' => $given_name,
        'family_name' => $family_name
      ]
    ]
  ];

  // add optional email ('address')
  if (isset($address)) {
    $payload['data']['relationships']['emails']['data'][] = [
      'type' => 'emails',
      'attributes' => ['address' => $address]
    ];
  }
  // add optional password
  if (isset($password) && isset($password_confirmation) && $password != '' && $password_confirmation != '') {
    $payload['data']['attributes']['user']['password'] = $password;
    $payload['data']['attributes']['user']['password_confirmation'] = $password_confirmation;
  }
  // add optional job title
  if (isset($job_title)) {
    $payload['data']['attributes']['job_title'] = $job_title;
  }
  // add optional gender
  if (isset($job_title)) {
    $payload['data']['attributes']['gender'] = $gender;
  }
  // add optional additional info
  if (!empty($additional_info)) {
    $payload['data']['attributes']['data_fields'] = $additional_info;
  }

  try {
    $person = $client->post("organizations/$parent_org/people", ['json' => $payload]);
    return $person;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
  }
  return ['errors' => $errors];
}

/**------------------------------------------------------------------
 * Assign role to person
 * $role_name is the text name of the role
 * The lookup is case sensitive so "prospective AO" and "prospective ao" would be considered different roles
 * Will create the role with matching name if it doesnt exist yet.
 * $org_uuid is for adding a relationship to this role
 ------------------------------------------------------------------*/
function wicket_assign_role($person_uuid, $role_name, $org_uuid = ''){
  $client = wicket_api_client();

  // build role payload
  $payload = [
    'data' => [
      'type' => 'roles',
      'attributes' => [
        'name' => $role_name,
      ]
    ]
  ];

  if ($org_uuid != '') {
    $payload['data']['relationships']['resource']['data']['id'] = $org_uuid;
    $payload['data']['relationships']['resource']['data']['type'] = 'organizations';
  }

  try {
    $client->post("people/$person_uuid/roles", ['json' => $payload]);
    return true;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Removes role from person
 * $role_name is the text name of the role
 * The lookup is case sensitive so "prospective AO" and "prospective ao" would be considered different roles
 ------------------------------------------------------------------*/
function wicket_remove_role($person_uuid, $role_name){
  $client = wicket_api_client();
  $person = wicket_get_person_by_id($person_uuid);

  $role_id = '';
  if ($person) {
    foreach ($person->included() as $included) {
      if ($included['type'] == 'roles' && $included['attributes']['name'] == $role_name ) {
        // get the role id for use in the payload below
        $role_id = $included['id'];
        break;
      }
    }
  }

  if ($role_id) {
    // build role payload
    $payload = [
      'data' => [
        [
          'type' => 'roles',
          'id' => $role_id
        ]
      ]
    ];

    try {
      $client->delete("people/$person_uuid/relationships/roles", ['json' => $payload]);
      return true;
    } catch (Exception $e) {
      $errors = json_decode($e->getResponse()->getBody())->errors;
      // echo "<pre>";
      // print_r($e->getMessage());
      // echo "</pre>";
      //
      // echo "<pre>";
      // print_r($errors);
      // echo "</pre>";
      // die;
    }
  }

  return false;
}

/**------------------------------------------------------------------
 * Assign organization membership to person
 ------------------------------------------------------------------*/
function wicket_assign_organization_membership($person_uuid, $org_id, $membership_id){
  $client = wicket_api_client();

  // build membership payload
  $payload = [
		'data' => [
			'type' => 'organization_memberships',
			'attributes' => [
				'starts_at' => date('c', time()),
				"ends_at" => date('c', strtotime('+1 year'))
			],
			'relationships' => [
				'owner' => [
					'data' => [
						'id' => $person_uuid,
						'type' => 'people'
					]
				],
				'membership' => [
					'data' => [
						'id' => $membership_id,
						'type' => 'memberships'
					]
				],
				'organization' => [
					'data' => [
						'id' => $org_id,
						'type' => 'organizations'
					]
				]
			]
		]
	];

  try {
    $person = $client->post("organization_memberships", ['json' => $payload]);
    return true;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    echo "<pre>";
    print_r($e->getMessage());
    echo "</pre>";

    echo "<pre>";
    print_r($errors);
    echo "</pre>";
    die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Assign individual membership to person
 ------------------------------------------------------------------*/
function wicket_assign_individual_membership($person_uuid, $membership_id){
  $client = wicket_api_client();

  // build membership payload
  $payload = [
		'data' => [
			'type' => 'person_memberships',
			'attributes' => [
				'starts_at' => date('c', time()),
				// "ends_at" => date('c', strtotime('+1 year'))
			],
			'relationships' => [
				'person' => [
					'data' => [
						'id' => $person_uuid,
						'type' => 'people'
					]
				],
				'membership' => [
					'data' => [
						'id' => $membership_id,
						'type' => 'memberships'
					]
				]
			]
		]
	];

  try {
    $person = $client->post("person_memberships", ['json' => $payload]);
    return true;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Gets the current person memberships
 * using the person membership entries endpoint
 ------------------------------------------------------------------*/
function wicket_get_current_person_memberships(){
	$client = wicket_api_client();
	$uuid = wicket_current_person_uuid();
	static $memberships = null;
	// prepare and memoize all connections from Wicket
	if (is_null($memberships)) {
	  try{
			$memberships = $client->get('people/'.$uuid.'/membership_entries?include=membership,organization_membership.organization,fusebill_subscription');
    }catch (Exception $e){
    }
	}
	if ($memberships) {
		return $memberships;
	}
}

/**------------------------------------------------------------------
 * Create organization
 * $additional_info is data_fields. An array of arrays to get the number based indexing needed
 * $org_type will be the machine name of the different org types available for the wicket instance
 ------------------------------------------------------------------*/
function wicket_create_organization($org_name, $org_type, $additional_info = []){
  $client = wicket_api_client();

  // build org payload
  $payload = [
		'data' => [
			'type' => 'organizations',
			'attributes' => [
				'type' => $org_type,
				'legal_name' => $org_name,
			]
		]
	];

  if (!empty($additional_info)) {
    $payload['data']['attributes']['data_fields'] = $additional_info;
  }

  try {
    $org = $client->post("organizations", ['json' => $payload]);
    return $org;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Create organization address
 * $payload is an array of attributes. See how wicket does this via the API/network tab in chrome
 * an example might be the following:
 $payload = [
   'data' => [
     'type' => 'addresses',
     'attributes' => [
       'type' => 'work',
       'address1' => '123 fake st',
       'city' => 'ottawa',
       'country_code' => 'CA',
       'state_name' => 'ON',
       'zip_code' => 'k1z6x6'
     ]
   ]
 ];
 ------------------------------------------------------------------*/
function wicket_create_organization_address($org_id, $payload){
  $client = wicket_api_client();

  try {
    $org = $client->post("organizations/$org_id/addresses", ['json' => $payload]);
    return true;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Create person address
 * $payload is an array of attributes. See how wicket does this via the API/network tab in chrome
 * an example might be the following:
 $payload = [
   'data' => [
     'type' => 'addresses',
     'attributes' => [
       'type' => 'work',
       'address1' => '123 fake st',
       'city' => 'ottawa',
       'country_code' => 'CA',
       'state_name' => 'ON',
       'zip_code' => 'k1z6x6'
     ]
   ]
 ];
 ------------------------------------------------------------------*/
function wicket_create_person_address($person_uuid, $payload){
  $client = wicket_api_client();

  try {
    $org = $client->post("people/$person_uuid/addresses", ['json' => $payload]);
    return true;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Create organization email
 * $payload is an array of attributes. See how wicket does this via the API/network tab in chrome
 ------------------------------------------------------------------*/
function wicket_create_organization_email($org_id, $payload){
  $client = wicket_api_client();

  try {
    $org = $client->post("organizations/$org_id/emails", ['json' => $payload]);
    return true;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Create organization phone
 * $payload is an array of attributes. See how wicket does this via the API/network tab in chrome
 ------------------------------------------------------------------*/
function wicket_create_organization_phone($org_id, $payload){
  $client = wicket_api_client();

  try {
    $org = $client->post("organizations/$org_id/phones", ['json' => $payload]);
    return true;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Create person phone
 * $payload is an array of attributes. See how wicket does this via the API/network tab in chrome
 ------------------------------------------------------------------*/
function wicket_create_person_phone($person_uuid, $payload){
  $client = wicket_api_client();

  try {
    $org = $client->post("people/$person_uuid/phones", ['json' => $payload]);
    return true;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Create organization website
 * $payload is an array of attributes. See how wicket does this via the API/network tab in chrome
 ------------------------------------------------------------------*/
function wicket_create_organization_web_address($org_id, $payload){
  $client = wicket_api_client();

  try {
    $org = $client->post("organizations/$org_id/web_addresses", ['json' => $payload]);
    return true;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Create connection
 * $payload is an array of attributes. See how wicket does this via the API/network tab in chrome
 * an example might be the following:
$relationship_payload = [
    'data' => [
      'type' => 'connections',
      'attributes' => [
        'type' => 'employee',
        'starts_at' => date("Y-m-d"),
        'description' => 'my description goes here'
      ],
      'relationships' => [
        'organization' => [
          'data' => [
            'id' => $org_id, //org id
            'type' => 'organizations'
          ]
        ],
        'person' => [
          'data' => [
            'id' => $person['data']['id'],
            'type' => 'people'
          ]
        ],
        'from' => [
          'data' => [
            'id' => $person['data']['id'],
            'type' => 'people'
          ]
        ],
        'to' => [
          'data' => [
            'id' => $org_id, //org id
            'type' => 'organizations'
          ]
        ],
      ],
    ]
  ];
 ------------------------------------------------------------------*/
function wicket_create_connection($payload){
  $client = wicket_api_client();

  try {
    $client->post('connections',['json' => $payload]);
  } catch (\Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    echo "<pre>";
    print_r($e->getMessage());
    echo "</pre>";

    echo "<pre>";
    print_r($errors);
    echo "</pre>";
    die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Get Touchpoints
 ------------------------------------------------------------------*/
function wicket_get_current_user_touchpoints($service_id){
  $client = wicket_api_client();
  $person_id = wicket_current_person_uuid();

  try {
    $touchpoints = $client->get("people/$person_id/touchpoints?page[size]=100&filter[service_id]=$service_id", ['json']);
    return $touchpoints;
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

// ----------------------------------------------------------------
// USAGE:
// $params = [
//   'person_id' => '[uuid from wicket]',
//   'action' => 'test action',
//   'details' => 'these are some details',
//   'data' => ['test' => 'thing']
// ];
// write_touchpoint($params);
// ----------------------------------------------------------------
function write_touchpoint($params, $wicket_service_id){
  $client = wicket_api_client();
  $payload = build_touchpoint_payload($params, $wicket_service_id);

  if ($payload) {
    try {
      $res = $client->post('touchpoints',['json' => $payload]);
    } catch (\Exception $e) {
      // echo "<pre>";
      // print_r($e->getMessage());
      // echo "</pre>";
    }
    if (isset($res)) {
      return true;
    }
  }
}

function build_touchpoint_payload($params, $wicket_service_id){

  $payload = [
    'data' => [
      'type' => 'touchpoints',
      'attributes' => [
        'action' => $params['action'],
        'details' => html_entity_decode($params['details']),
        'code' => str_replace(' ','_',strtolower($params['action'])),
      ],
      'relationships' => [
        'person' => [
          'data' => [
            'id' => $params['person_id'],
            'type' => 'people'
          ]
        ],
        'service' => [
          'data' => [
            'id' => $wicket_service_id, //service id in wicket
            'type' => 'services'
          ]
        ]
      ],
    ]
  ];
  if (isset($params['data'])) {
    $payload['data']['attributes']['data'] = $params['data'];
  }
  return $payload;
}

// ----------------------------------------------------------------
// use this to get/create a service id to then pass into the write_touchpoint() function
// example:
// $service_id = get_create_touchpoint_service_id('Events Calendar', 'Events from the website');
// write_touchpoint($params, $service_id);
// ----------------------------------------------------------------
function get_create_touchpoint_service_id($service_name, $service_description = 'Custom from WP') {
  $client = wicket_api_client();

  // check for existing service, return service ID
  $existing_services = $client->get('services')['data'];
  $existing_service = reset(array_filter($existing_services, function($service) use ($service_name) {
    return isset($service['attributes']['name']) && $service['attributes']['name'] === $service_name;
  }));
  if ($existing_service) {
    return $existing_service['id'];
  }

  // if no existing service, create one and return service ID
  $payload['data']['attributes'] = [
    'name' => $service_name,
    'description' => $service_description,
    'status' => 'active',
    'integration_type' => "custom",
  ];
  try {
    $service = $client->post("/services", ['json' => $payload]);
    return $service['data']['id'];
  } catch (Exception $e) {
    $errors = json_decode($e->getResponse()->getBody())->errors;
    // echo "<pre>";
    // print_r($e->getMessage());
    // echo "</pre>";
    //
    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    // die;
  }
  return false;
}

/**------------------------------------------------------------------
 * Get active org memberships current user owns
 ------------------------------------------------------------------*/
function wicket_get_active_org_memberships(){
  $client = wicket_api_client();
  $person_id = wicket_current_person_uuid();
  if ($person_id) {
    $organization_memberships = $client->get("/organization_memberships?filter[owner_uuid_eq]=$person_id&filter[m]=or");
    $active_memberships = [];
    if (isset($organization_memberships['data'][0])) {
      foreach ($organization_memberships['data'] as $org_membership) {
        if ($org_membership['attributes']['active'] == 1) {
          $active_memberships[] = $org_membership;
        }
      }
    }
    return $active_memberships;
  }else {
    return [];
  }
}

/**------------------------------------------------------------------
 * Get org memberships
 ------------------------------------------------------------------*/
function wicket_get_org_memberships($org_id){
  $client = wicket_api_client();
  if ($org_id) {
    $organization_memberships = $client->get("/organizations/$org_id/membership_entries?sort=-ends_at&include=membership");
    $memberships = [];
    if (isset($organization_memberships['data'][0])) {
      foreach ($organization_memberships['data'] as $org_membership) {
        $memberships[$org_membership['id']]['membership'] = $org_membership;
        // add included attributes as well
        foreach ($organization_memberships['included'] as $included) {
          if ($included['id'] == $org_membership['relationships']['membership']['data']['id']) {
            $memberships[$org_membership['id']]['included'] = $included;
          }
        }
      }
    }
    return $memberships;
  }else {
    return [];
  }
}

/**------------------------------------------------------------------
* Gets spoken languages resource list (used in account center comm. prefs)
------------------------------------------------------------------*/
function get_spoken_languages_list(){
  $client = wicket_api_client();
  $resource_types = $client->resource_types->all()->toArray();
  $resource_types = collect($resource_types);
  $found = $resource_types->filter(function ($item) {
              return $item->resource_type == 'shared_written_spoken_languages';
          });

  return $found;
}

/**------------------------------------------------------------------
* Gets org types resource list
------------------------------------------------------------------*/
function get_org_types_list(){
  $client = wicket_api_client();
  $resource_types = $client->resource_types->all()->toArray();
  $resource_types = collect($resource_types);
  $found = $resource_types->filter(function ($item) {
              return $item->resource_type == 'organizations';
          });

  return $found;
}

/**------------------------------------------------------------------
* Gets org connection types resource list
------------------------------------------------------------------*/
function get_person_to_organizations_connection_types_list(){
  $client = wicket_api_client();
  $resource_types = $client->resource_types->all()->toArray();
  $resource_types = collect($resource_types);
  $found = $resource_types->filter(function ($item) {
              return $item->resource_type == 'connection_person_to_organizations';
          });

  return $found;
}
