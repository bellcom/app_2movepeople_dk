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

### Initial point lightning version v 2.1.7
```
git reset --hard HEAD
git checkout 97a7f7a5a9737859f1c61984caa06eaff375ed26
composer install
drush cr
drush pm-uninstall scheduled_updates lightning_scheduled_updates simple_oauth admin_toolbar_tools media_entity_instagram media_entity_twitter -y
```

### Update acquia/lightning to v 2.2.8
```
git reset --hard HEAD
git checkout b9bd5abda90bd284fa346350b671c2baf0c41a39
composer install
// You can provbaly get an error with DrupalComposer\DrupalScaffold. Try to run composer install again.
composer install
drush cr
drush en content_moderation lightning_scheduler wbm2cm -y 
drush cr
drush updb -y
drush wbm2cm-migrate
// You can provbaly get an error with Drupal/Core/Render/Renderer in first try. Try to run wbm2cm-migrate again.
drush wbm2cm-migrate
drush pm-uninstall wbm2cm -y 
```

### Update acquia/lightning to v 3.1.6
```
git reset --hard HEAD
git checkout bb538d44ef8aeb168eb51183ce4975ad2e1c363c
composer install
drush php:eval "Drupal::keyValue('system.schema')->deleteMultiple(['openapi_redoc', 'openapi_swagger_ui']);"
drush cr
drush updb -y
```

### Update acquia/lightning to v 3.2.7
```
git reset --hard HEAD
git checkout 5fad1a45cac5e068f2e6ee96a116cac5370471f0
composer install
drush cr
drush updb -y
drush cim -y
```
