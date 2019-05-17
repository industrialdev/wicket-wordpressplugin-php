<?php

// https://www.smashingmagazine.com/2016/04/three-approaches-to-adding-configurable-fields-to-your-plugin/
class wicket_create_account_settings {

  public function __construct() {
    // Hook into the admin menu
    add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );

    // Add Settings and Fields
    add_action( 'admin_init', array( $this, 'setup_sections' ) );
    add_action( 'admin_init', array( $this, 'setup_fields' ) );
  }

  public function create_plugin_settings_page() {
  	// Add the menu item and page
  	$page_title = 'Wicket Create Account Settings';
  	$menu_title = 'Wicket Create Account Settings';
  	$capability = 'manage_options';
  	$slug = 'wicket_create_account_settings';
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
          settings_fields( 'wicket_create_account_settings_fields' );
          do_settings_sections( 'wicket_create_account_settings_fields' );
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
    add_settings_section( 'wicket_create_account_configuration', 'Wicket Create Account Settings', array( $this, 'section_callback' ), 'wicket_create_account_settings_fields' );
  }

  public function section_callback( $arguments ) {
    switch( $arguments['id'] ){
      case 'wicket_create_account_configuration':
        echo 'Configure settings pertinent to the creation of a Wicket account';
        break;
    }
  }

  public function setup_fields() {
    $fields = array(
    	array(
    		'uid' => 'wicket_create_account_settings_person_creation_redirect',
    		'label' => 'Person Creation Redirect',
    		'section' => 'wicket_create_account_configuration',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'Where the user is taken once the create account form completes. Typically it defaults to /verify-account.
        <br>To translate this value, go to WPML String Translation section and filter domain by "admin_texts_wicket_create_account_settings_person_creation_redirect"',
    	),
    	array(
    		'uid' => 'wicket_create_account_settings_google_captcha_key',
    		'label' => 'Google Captcha Key',
    		'section' => 'wicket_create_account_configuration',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'The key used to display google recaptcha. Obtain a key here <a href="https://www.google.com/recaptcha" target="_blank">https://www.google.com/recaptcha</a>',
    	),
    	array(
    		'uid' => 'wicket_create_account_settings_google_captcha_secret_key',
    		'label' => 'Google Captcha Secret Key',
    		'section' => 'wicket_create_account_configuration',
    		'type' => 'text',
        'default' => '',
        'placeholder' => '',
    		'helper' => '',
    		'supplimental' => 'The secret key used to display google recaptcha. Obtain a key here <a href="https://www.google.com/recaptcha" target="_blank">https://www.google.com/recaptcha</a>',
    	),
    );
  	foreach($fields as $field){
    	add_settings_field($field['uid'], $field['label'], array($this, 'field_callback'), 'wicket_create_account_settings_fields', $field['section'], $field);
      register_setting('wicket_create_account_settings_fields', $field['uid']);
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
new wicket_create_account_settings();
