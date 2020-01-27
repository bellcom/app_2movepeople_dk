#!/bin/sh
ROOT_DIR=$(realpath "$(dirname $0)/../")
DRUSH_EXEC=$(which drush)
DB_DUMP_FILE=$ROOT_DIR/tmp/db_dump-$(date +'%Y%m%d-%H%M%S').sql.gz

$DRUSH_EXEC sql-dump | gzip -9 >  $DB_DUMP_FILE
echo "DB dumped to $DB_DUMP_FILE"
composer install
$DRUSH_EXEC cr
$DRUSH_EXEC updb -y
$DRUSH_EXEC cim -y

