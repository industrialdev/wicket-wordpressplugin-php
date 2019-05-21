# wicket-wordpressplugin-php

Download this repo in to the plugins directory of a Wordpress website.

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

# Enable plugin(s)

## Base Wicket Plugin
Enable the "Wicket" plugin in the wordpress admin. This is required for any of the sub-plugins below. Enter the relevant API credentials on the provided settings form in the backend. Beyond containing the settings form, this plugin provides helper functions as well.

If needed, you can also enable the other plugins below to extend functionality.

## Wicket CAS Role Sync

Requires WP-CASSIFY plugin *AND* the "Base Wicket Plugin"

This will work on user login. Deletes existing user roles then re-adds based on what's set on the user in Wicket. If the roles don't exist in
Wordpress, they will be created on the fly

## Wicket Update Password

Requires the "Base Wicket Plugin". Provides a widget with a form to update the persons password. This is a widget in Wordpress. It's suggested to install wicket context plugin to be able to restrict which pages it can go on.

## Wicket Contact Information

Requires the "Base Wicket Plugin". Provides the React widget form from Wicket admin to update person contact information. This is a widget in Wordpress. It's suggested to install wicket context plugin to be able to restrict which pages it can go on.

## Wicket Create Account Form

Requires the "Base Wicket Plugin". Provides a widget with a form to create a new person. This is a widget in Wordpress. It's suggested to install wicket context plugin to be able to restrict which pages it can go on. 

To create a modified version of this form, it is advisable to disable this plugin, copy the plugin file "wicket_create_account.php" outside of the wicket plugin folder and adjust the include path at the top of the file within your new copy to continue to pull in the settings form. It might be a good idea while renaming the file to also rename the functions and class within as well. This isn't stricly required but might be a good idea to visually separate the plugin as being custom/your own. 

Also, if needing to run both the core form plugin and your custom one, the functions will need to be renamed as well.

## Login link for theme
`<?php echo get_option('wp_cassify_base_url').'login?service='.home_url($wp->request).'/' ?>`
