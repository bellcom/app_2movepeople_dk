This Drupal 8 installation based in  [Lightning](https://www.drupal.org/project/lightning).

See more information in original repo: https://github.com/acquia/lightning-project

## Get Started
```
$ composer install
# configure your settings.php
# get db dump from preprod/test environment
$ zcat < db-dump.sql.gz | drush sqlc
```

## Deployment steps
```
# Update codebase.
$ git pull
$ composer install
# Update db and translation.
$ cd docroot
$ drush cim
$ drush updb
$ drush language-import da ../translations/da.po
```

## Export translations
Get latest db dump from production environment.
Make sure that you have set up correct translation folder.

Check in your `settings.php`
```
$settings['custom_translations_directory'] = '../translations';
```
Run export translation command:
```
drush language-export --langcodes=da --file=da.po
```

After export all changes should be reviewed and commited.

## Update path details

See update recommendations on original profile repository release notes
https://github.com/acquia/lightning/releases
