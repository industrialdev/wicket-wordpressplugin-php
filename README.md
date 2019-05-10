# wicket-wordpressplugin-php

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

## Baseline plugin
Enable the "Wicket" plugin at least in the wordpress admin. Enter the relevant API credentials.

If needed, you can also enable the other plugins to extend functionality.

## Wicket CAS Role Sync

Requires WP-CASSIFY plugin *AND* the "Base Wicket Plugin"

This will work on user login. Deletes existing user roles then re-adds based on what's set on the user in Wicket. If the roles don't exist in
Wordpress, they will be created on the fly
