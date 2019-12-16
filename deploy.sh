#!/bin/sh
DRUSH="drush --root=$(pwd)/docroot"
DB_DUMP_FILE=/tmp/db_dump-$(date +%s).sql.gz
$DRUSH sql-dump | gzip -9 >  $DB_DUMP_FILE
echo "DB dumped to $DB_DUMP_FILE"
composer install
$DRUSH cim -y
$DRUSH updb -y
$DRUSH language-import da ../translations/da.po
