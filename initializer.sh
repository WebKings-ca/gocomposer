#!/bin/sh

wawa=$1;

pluginDir=$1;

mainDir=$2;

defaultFolder=$3;

settingsPath=$4;

cd $mainDir;

cwd=$(pwd);


if [[ "$mainDir" = "$cwd" ]]; then


    chmod 777 $settingsPath;

    chmod -R 777 $defaultFolder;

fi