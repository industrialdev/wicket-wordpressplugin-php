<?php

// https://www.smashingmagazine.com/2016/04/three-approaches-to-adding-configurable-fields-to-your-plugin/
class wicket_settings {

  public function __construct() {
    // Hook into the admin menu
    add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );

    // Add Settings and Fields
    add_action( 'admin_init', array( $this, 'setup_sections' ) );
    add_action( 'admin_init', array( $this, 'setup_fields' ) );
  }

  public function create_plugin_settings_page() {
  	// Add the menu item and page
  	$page_title = 'Wicket Settings';
  	$menu_title = 'Wicket';
  	$capability = 'manage_options';
  	$slug = 'wicket_settings';
  	$callback = array( $this, 'plugin_settings_page_content' );
  	$icon = 'dashicons-admin-plugins';
  	$position = 100;

  	add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
  }

  public function plugin_settings_page_content() {?>
  	<div class="wrap">
  		<?php
      if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
        $this->admin_notice();
      } ?>
  		<form method="POST" action="options.php">
        <?php
          settings_fields( 'wicket_fields' );
          do_settings_sections( 'wicket_fields' );
          submit_button();
        ?>
  		</form>
  	</div> <?php
  }

  public function admin_notice() { ?>
    <div class="notice notice-success is-dismissible">
      <p>Your settings have been updated!</p>
    </div><?php
  }

  public function setup_sections() {
    add_settings_section( 'wicket_configuration_environment', 'Wicket Environment', array( $this, 'section_callback' ), 'wicket_fields' );
    add_settings_section( 'wicket_configuration_prod', 'Wicket PROD', array( $this, 'section_callback' ), 'wicket_fields' );
    add_settings_section( 'wicket_configuration_stage', 'Wicket STAGE', array( $this, 'section_callback' ), 'wicket_fields' );
  }

  public function section_callback( $arguments ) {
    switch( $arguments['id'] ){
      case 'wicket_configuration_environment':
        echo 'Toggle which environment settings you want to use';
        break;
      case 'wicket_configuration_prod':
        echo 'Configure Wicket API settings, etc. for production';
        break;
      case 'wicket_configuration_stage':
        echo 'Configure Wicket API settings, etc. for staging';
        break;
    }
  }

  public function setup_fields() {
    $fields = array(
      array(
        'uid' => 'wicket_admin_settings_environment',
        'label' => 'Wicket Environment',
        'section' => 'wicket_configuration_environment',
        'type' => 'radio',
        'options' => array(
          'prod' => 'Prod',
          'stage' => 'Stage',
        ),
        'helper' => '',
    		'supplimental' => '',
        'default' => array('stage')
      ),
    	array(
    		'uid' => 'wicket_admin_settings_prod_api_endpoint',
    		'label' => 'API Endpoint',
    		'section' => 'wicket_configuration_prod',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'The address of the api endpoint. Ex: https://[client]-api.wicketcloud.com',
    	),
    	array(
    		'uid' => 'wicket_admin_settings_prod_secret_key',
    		'label' => 'JWT Secret Key',
    		'section' => 'wicket_configuration_prod',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'Secret key from wicket',
    	),
    	array(
    		'uid' => 'wicket_admin_settings_prod_person_id',
    		'label' => 'Person ID',
    		'section' => 'wicket_configuration_prod',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'Person ID from wicket',
    	),
    	array(
    		'uid' => 'wicket_admin_settings_prod_parent_org',
    		'label' => 'Parent Org',
    		'section' => 'wicket_configuration_prod',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'Top level organization used for creating new people on the create account form.
                          <br>This is the "alternate name" found in Wicket under "Organizations" for the top most organization.',
    	),
      array(
        'uid' => 'wicket_admin_settings_prod_wicket_admin',
        'label' => 'Wicket Admin',
        'section' => 'wicket_configuration_prod',
        'type' => 'text',
        'default' => '',
        'placeholder' => '',
        'helper' => '',
        'supplimental' => 'The address of the admin interface. Ex: https://[client]-admin.wicketcloud.com',
      ),
    	array(
    		'uid' => 'wicket_admin_settings_stage_api_endpoint',
    		'label' => 'API Endpoint',
    		'section' => 'wicket_configuration_stage',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'The address of the api endpoint. Ex: https://[client]-api.staging.wicketcloud.com',
    	),
    	array(
    		'uid' => 'wicket_admin_settings_stage_secret_key',
    		'label' => 'JWT Secret Key',
    		'section' => 'wicket_configuration_stage',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'Secret key from wicket',
    	),
    	array(
    		'uid' => 'wicket_admin_settings_stage_person_id',
    		'label' => 'Person ID',
    		'section' => 'wicket_configuration_stage',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'Person ID from wicket',
    	),
    	array(
    		'uid' => 'wicket_admin_settings_stage_parent_org',
    		'label' => 'Parent Org',
    		'section' => 'wicket_configuration_stage',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'Top level organization used for creating new people on the create account form.
                          <br>This is the "alternate name" found in Wicket under "Organizations" for the top most organization.',
    	),
      array(
        'uid' => 'wicket_admin_settings_stage_wicket_admin',
        'label' => 'Wicket Admin',
        'section' => 'wicket_configuration_stage',
        'type' => 'text',
        'default' => '',
        'placeholder' => '',
        'helper' => '',
        'supplimental' => 'The address of the admin interface. Ex: https://[client]-admin.staging.wicketcloud.com',
      ),

    );
  	foreach($fields as $field){
    	add_settings_field($field['uid'], $field['label'], array($this, 'field_callback'), 'wicket_fields', $field['section'], $field);
      register_setting('wicket_fields', $field['uid']);
  	}
  }

  public function field_callback($arguments) {
    $value = get_option($arguments['uid']);

    if(!$value) {
      $value = $arguments['default'];
    }

    switch($arguments['type']){
      case 'text':
      case 'password':
      case 'number':
        printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" size="60" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
        break;
      case 'textarea':
        printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
        break;
      case 'select':
      case 'multiselect':
        if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
          $attributes = '';
          $options_markup = '';
          foreach( $arguments['options'] as $key => $label ){
            $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value[ array_search( $key, $value, true ) ], $key, false ), $label );
          }
          if( $arguments['type'] === 'multiselect' ){
            $attributes = ' multiple="multiple" ';
          }
          printf( '<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup );
        }
        break;
      case 'radio':
      case 'checkbox':
        if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
          $options_markup = '';
          $iterator = 0;
          foreach( $arguments['options'] as $key => $label ){
            $iterator++;
            $options_markup .= sprintf( '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked( $value[ array_search( $key, $value, true ) ], $key, false ), $label, $iterator );
          }
          printf( '<fieldset>%s</fieldset>', $options_markup );
        }
        break;
    }

    if($helper = $arguments['helper']){
      printf('<span class="helper"> %s</span>', $helper);
    }

    if($supplimental = $arguments['supplimental']){
      printf('<p class="description">%s</p>', $supplimental);
    }

  }

}
new wicket_settings();
