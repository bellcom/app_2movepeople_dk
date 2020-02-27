#!/bin/sh
ROOT_DIR=$(realpath "$(dirname $0)/../")
DRUSH_EXEC=$(which drush)

echo 'Exporting existing translations.'
mkdir -p $ROOT_DIR/translation-dumps/
$DRUSH_EXEC language-export --langcodes=da --file=$ROOT_DIR/translations-dumps/da-dump-$(date +'%Y%m%d-%H%M%S').po

echo "Importing translations from $ROOT_DIR/translations/da.po."
$DRUSH_EXEC language-import --langcode=da $ROOT_DIR/translations/da.po
