#!/bin/sh

wawa=$1;

pluginDir=$1;

mainDir=$2;

composerPhar=$3;

cd $mainDir;

cwd=$(pwd);


if [[ "$mainDir" = "$cwd" ]]; then

    $composerPhar update --with-dependencies;

    drush cr;

fi
