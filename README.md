# Drupal 8 CRCC Installation

## Establish the local configs 
Ensure database configuration are in settings.local.php

## With PHP Composer run:
* composer install

## Then with Drush clear the registry, reset the configuration and clear the registry again:
* drush cr
* drush cim -y
* drush cr
