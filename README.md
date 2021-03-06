# wicket-wordpressplugin-php

Download (NOT CLONE) this repo in to the plugins directory of a Wordpress website.

Make sure the plugin folder is called "wicket-wordpressplugin-php"

Add this to the root composer.json file (in a bedrock configured wordpress site):

under require:
` "industrialdev/wicket-sdk-php": "dev-master",`

under repositories, add this:
```
{
  "type": "git",
  "url": "https://github.com/industrialdev/wicket-sdk-php.git"
}
```

then run composer update to get the wicket sdk

## Important Note
This plugin is common to all installs of Wordpress using Wicket. There is usually, for now, a lib/wicket.php within the theme with logic specific to each client, but any code changes to this module should be able to be made to all clients that use this module.

# Enable plugin(s)

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

## CAS SETUP
Within the CAS settings, /wp/wp-admin/options-general.php?page=wp-cassify.php

Make sure the "CAS Server base url" option has a training slash!

https://\<tenant>-login.staging.wicketcloud.com/

--------------------------------------

Check "Create user if not exist	" and "Enable SLO (Single Log Out)"

--------------------------------------

Set "Name of the service validate servlet (Default : serviceValidate)" to be "p3/serviceValidate"

--------------------------------------
Set "Xpath query used to extract cas user id during parsing" to this:

```
//cas:serviceResponse/cas:authenticationSuccess/cas:attributes/cas:personUuid
```


--------------------------------------
Set "White List URL(s)" to this (include staging and prod domains as well for those paths):

```
http://172.16.231.130/wp/wp-login.php;http://172.16.231.130/wp/wp-admin
```

Alternatively, you can use this to bypass CAS (use correct domain depending on environment):

http://172.16.231.130/wp/wp-login.php?wp_cassify_bypass=bypass


## NGINX FOR CAS

change/check server_name value in nginx as the wp cassify module uses that when logging in

MAKE SURE IN /ETC/NGINX/NGINX.CONF YOU HAVE YOUR LOCAL SERVER_NAME SET TO 172.16.231.130 (or correct domain in use) OTHERWISE CAS LOGIN WON'T WORK

## CAS Login link for theme

Example of a header utility menu containing a language switcher and links for CAS:

```php
<ul class="menu">
  <?php $locale = ICL_LANGUAGE_CODE == 'fr' ? '&locale=fr' : '&locale=en'; ?>
  <?php if(is_user_logged_in()): ?>
    <li class="menu-item">
      <a href="<?php _e('/account-centre', 'industrial') ?>"> <?php _e('My Account', 'industrial') ?></a>
    </li>
    <li class="menu-item">
      <a href="<?php echo wp_logout_url() ?>"><?php _e('Logout', 'industrial') ?></a>
    </li>
  <?php else: ?>
    <li class="menu-item">
      <a href="<?php echo get_option('wp_cassify_base_url').'login?service='.home_url($wp->request).'/'.$locale ?>"><?php _e('Login', 'industrial') ?></a>
    </li>
  <?php endif; ?>
  <li class="menu-item">
    <?php do_action('wpml_add_language_selector'); ?>
  </li>
</ul>
```

