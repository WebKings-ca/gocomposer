#!/bin/sh

wawa=$1;

pluginDir=$1;

mainDir=$2;

drushInstalled=$3;

backupS=$4;

drupalRoot=$5;

flag=$6;

installDrush=$7;

composerPhar=$8;

cd $mainDir;

cwd=$(pwd);


if [[ "$mainDir" = "$cwd" ]]; then

    cd $mainDir;

    if [[ "$installDrush" = yes ]]; then

        $composerPhar require drush/drush;

    fi


    if [[ "$flag" = no ]]; then

        touch $mainDir/CurrEnv.txt;

        chmod 777 $mainDir/CurrEnv.txt;

        drush sql-conf --show-passwords > CurrEnv.txt;

    fi

    if [[ "$backupS" = yes ]]; then

        mkdir $mainDir/backup;

        cd $mainDir;

        drush sql-dump --result-file $mainDir/backup/old_db.sql;

        tar -zcvf archive_original.tar.gz .;

        mv $mainDir/archive_original.tar.gz $mainDir/backup/archive_original.tar.gz;

    fi

    if [[ "$flag" = no ]]; then

        chmod 777 $drupalRoot/sites/default/settings.php;

        chmod -R 777 $drupalRoot/sites/default;

    fi

    touch $mainDir/settings.tmp.php;

    chmod 777 $mainDir/settings.tmp.php;

    touch $mainDir/settings_orig.php;

fi

