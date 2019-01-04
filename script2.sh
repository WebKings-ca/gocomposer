#!/bin/sh

wawa=$1;

pluginDir=$1;

mainDir=$2;

composerPhar=$3;

drupalRoot=$4;

cd $pluginDir;

cwd=$(pwd);



if [[ "$pluginDir" = "$cwd" ]]; then

  cd $mainDir;

  #cp $pluginDir/autoFinalize.sh $mainDir/autoFinalize.sh;

  if [[ "$drupalRoot" != "$mainDir/web" ]]; then

    rm -rf $drupalRoot/core;

   rm -rf $drupalRoot/libraries;

   rm -rf $drupalRoot/themes;

   #rm -rf $drupalRoot/modules/custom;

   rm -rf $drupalRoot/modules;

   rm -rf $mainDir/web/sites;

   rm -rf $drupalRoot/profiles;

   rm $drupalRoot/web.config;

   rm $mainDir/options_settings.txt;

  chmod 644 $drupalRoot/sites/default/settings.php;

    cp -a $drupalRoot/sites/. $mainDir/web/sites/;

      #rm -rf $mainDir/vendor;

      #rm $mainDir/composer.lock;

      rm -rf $drupalRoot/sites;

      rm $drupalRoot/update.php;

      rm $drupalRoot/index.php;

      rm $drupalRoot/autoload.php;

      rm $drupalRoot/.htaccess;

      rm $drupalRoot/robots.txt;

  else

    chmod 644 $drupalRoot/sites/default/settings.php;

  fi


fi

