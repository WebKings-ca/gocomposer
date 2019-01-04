#!/bin/sh

wawa=$1;

pluginDir=$1;

mainDir=$2;

composerPhar=$3;

drupalRoot=$4;

cd $pluginDir;

cwd=$(pwd);



if [[ "$pluginDir" = "$cwd" ]]; then

  rm -rf d8LatestTemplate;

  $composerPhar create-project drupal-composer/drupal-project:8.x-dev d8LatestTemplate --stability dev --no-interaction;

  cd d8LatestTemplate;

  tempDir="d8LatestTemplate";

  tempPath="$pluginDir/$tempDir";

  cwd2=$(pwd);

  if [[ "$tempPath" = "$cwd2" ]]; then

      cp $tempPath/composer.json $pluginDir/template.composer.json;

      cp -a $tempPath/drush/. $mainDir/drush/;

      cp -a $tempPath/scripts/. $mainDir/scripts/;

      cp -a $tempPath/web/. $mainDir/web;

      cp $tempPath/.travis.yml $mainDir/.travis.yml;

      cp $tempPath/load.environment.php $mainDir/load.environment.php;

        if [[ "$drupalRoot" != "$mainDir/web" ]]; then

          rm -rf $mainDir/web/modules;

          rm -rf $mainDir/web/themes;

          rm -rf $mainDir/web/profiles;

          rm -rf $mainDir/web/libraries;

          rm -rf $mainDir/web/core;



          cp -a $drupalRoot/Libraries/. $mainDir/web/Libraries/;

          cp -a $drupalRoot/profiles/. $mainDir/web/profiles/;

          mkdir $mainDir/web/modules;

          cp -a $drupalRoot/modules/custom/. $mainDir/web/modules/custom/;

          cp -a $drupalRoot/themes/. $mainDir/web/themes/;

        else

            #cp  $tempPath/web/. $drupalRoot;

            rsync -r $tempPath/web/. $drupalRoot/. --exclude='*/'

        fi


      rm $mainDir/composer.lock;

      rm -rf $tempPath;


  fi


fi

