# wicket-wordpressplugin-php

Download (NOT CLONE) this repo in to the plugins directory of a Wordpress website.

Make sure the plugin folder is called "wicket-wordpressplugin-php"

Add this to the root composer.json file (in a bedrock configured wordpress site):

under require:
` "
aldev/wicket-sdk-php": "dev-master",`

under repositories, add this:
```
{
  "type": "git",
  "url": "https://github.com/industrialdev/wicket-sdk-php.git"
}
```

then run composer update to get the wicket sdk

*__If not using a composer-ized wordpress installation__*, edit wicket.php at the root of the plugin and add this line above the existing require statements:

require_once('vendor/autoload.php'); // add this here because this site doesn't use a global composer setup

Then add this bit to the composer.json in the plugin

```
"repositories": [
  {
    "type": "git",
    "url": "https://github.com/industrialdev/wicket-sdk-php.git"
  }
]
```

Then run composer install from the plugin directory

## Initial Setup


### WP-Cassify
Install WP-Cassify module: **composer require wpackagist-plugin/wp-cassify**

Enable plugin from the admin -> plugins screen

Within the CAS settings page, /wp/wp-admin/options-general.php?page=wp-cassify.php
 - Make sure the "CAS Server base url" option has a training slash! This would be the usual format, replacing <tenant> with the client you're working on.
  ```https://<tenant>-login.staging.wicketcloud.com/```

 - Check "Create user if not exist" and "Enable SLO (Single Log Out)"
 - Set "Name of the service validate servlet (Default : serviceValidate)" to be "p3/serviceValidate"
 - Set "Xpath query used to extract cas user id during parsing" to this:
   ```//cas:serviceResponse/cas:authenticationSuccess/cas:attributes/cas:personUuid```

 - Set "White List URL(s)" to this or whatever you use to address localhost (include staging and prod domains as well for those paths separated by a semicolon):
   ```http://172.16.231.130/wp/wp-login.php;http://172.16.231.130/wp/wp-admin```
   Alternatively, you can use this to bypass CAS (use correct domain depending on environment):
   ```http://172.16.231.130/wp/wp-login.php?wp_cassify_bypass=bypass```

  ### NGINX FOR CAS
  change/check the server_name value in nginx as the wp-cassify module uses that when logging in
  
  MAKE SURE IN /ETC/NGINX/NGINX.CONF YOU HAVE YOUR LOCAL SERVER_NAME SET TO 172.16.231.130 (or correct domain in use) OTHERWISE CAS LOGIN WON'T WORK

  
  
## Wicket Plugin Configuration

Fill out these fields here: **wp/wp-admin/admin.php?page=wicket_settings** or by clicking on "Wicket" on the left in the backend
 - First, toggle which environment settings you want to use (stage or prod). The rest of the settings would be similar but different values for each environment.
  
 - API Endpoint, usually is **https://[client-name]-api.staging.wicketcloud.com** OR **https://[client-name]-api.wicketcloud.com** for production
 
 - JWT Secret Key - provided by wicket devs
 
 - Person ID - the admin user UUID the code can use to make admin-type calls. Otherwise the current logged in user is typically used for most operations. Provided by wicket devs
 
 - Parent ORG - Top level organization used for creating new people on the create account form. This is the "alternate name" found in Wicket under "Organizations" for the top most organization. 
 
 - Wicket Admin - The address of the admin interface. Ex: **https://[client-name]-admin.staging.wicketcloud.com** OR **https://[client-name]-admin.wicketcloud.com** for production


# Available sub-plugin(s)

## Base Wicket Plugin
Enable the "Wicket" plugin in the wordpress admin. This is required for any of the sub-plugins below. Enter the relevant API credentials on the provided settings form in the backend. Beyond containing the settings form, this plugin provides helper functions as well.

If needed, you can also enable the other plugins below to extend functionality.

## Wicket CAS Role Sync

Requires WP-CASSIFY plugin *AND* the "Base Wicket Plugin"

This will work on user login. Deletes existing user roles then re-adds based on what's set on the user in Wicket. If the roles don't exist in
Wordpress, they will be created on the fly

## Wicket Update Password

Requires the "Base Wicket Plugin". Provides a widget with a form to update the persons password. This is a widget in Wordpress. It's suggested to install widget context plugin to be able to restrict which pages it can go on.

## Wicket Contact Information

Requires the "Base Wicket Plugin". Provides the React widget form from Wicket admin to update person contact information. This is a widget in Wordpress. It's suggested to install widget context plugin to be able to restrict which pages it can go on.

## Wicket Create Account Form

Requires the "Base Wicket Plugin". Provides a widget with a form to create a new person. This is a widget in Wordpress. It's suggested to install widget context plugin to be able to restrict which pages it can go on. 

To create a modified version of this form, it is advisable to disable this plugin, copy the plugin file "wicket_create_account.php" outside of the wicket plugin folder, rename it and adjust the include path at the top of the file within your new copy to continue to pull in the settings form. It might be a good idea while renaming the file to also rename the functions and class within as well. This isn't stricly required but might be a good idea to visually separate the plugin as being custom/your own. 

Also, if needing to run both the core form plugin and your custom one, the functions will need to be renamed as well.

## Wicket Manage Preferences Form

Requires the "Base Wicket Plugin". Provides a widget with a form to update person preferences. This is a widget in Wordpress. It's suggested to install wicket context plugin to be able to restrict which pages it can go on. 

To create a modified version of this form, it is advisable to disable this plugin, copy the plugin file "wicket_manage_preferences.php" outside of the wicket plugin folder and rename it. It might be a good idea while renaming the file to also rename the functions and class within as well. This isn't stricly required but might be a good idea to visually separate the plugin as being custom/your own. 

Also, if needing to run both the core form plugin and your custom one, the functions will need to be renamed as well.


## CAS Login link for theme

Example of a header utility menu containing a language switcher and links for CAS:

```php
<ul class="menu">
  <?php $locale = ICL_LANGUAGE_CODE == 'fr' ? '&locale=fr' : '&locale=en'; ?>
  <?php if(is_user_logged_in()): ?>
    <li class="menu-item">
      <a href="<?php _e('/account-centre', 'wicket') ?>"> <?php _e('My Account', 'wicket') ?></a>
    </li>
    <li class="menu-item">
      <a href="<?php echo wp_logout_url() ?>"><?php _e('Logout', 'wicket') ?></a>
    </li>
  <?php else: ?>
   <li class="menu-item">
	   <?php $referrer = isset($_GET['referrer']) ? WP_HOME.$_GET['referrer'].$locale : home_url($wp->request, 'https').'/'.$locale; ?>
	   <a class="" href="<?php echo get_option('wp_cassify_base_url').'login?service='.$referrer ?>"><?php _e('Login', 'wicket') ?></a>
  </li>
  <?php endif; ?>
  <li class="menu-item">
    <?php do_action('wpml_add_language_selector'); ?>
  </li>
</ul>
```

