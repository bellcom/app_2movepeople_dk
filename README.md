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
git pull
sh scripts/deploy.sh
```
Script includes following steps:
- dumping current db to `./tmp` directory
- composer install
- drush cr
- drush updb
- drush cim


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
## Update translations

```
git pull
sh scripts/translations-update.sh
```
Script includes following steps:
- dumping current translations to `./translation-dumps` directory
- import current version of translation file


After export all changes should be reviewed and commited.

## Update path details

See update recommendations on original profile repository release notes
https://github.com/acquia/lightning/releases
