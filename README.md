
# GoComposer

_GoComposer_ is an all in one solution to update existing Drupal 8 sites to the latest Fully Composer Managed template. It takes the template found in [drupal-project](https://github.com/drupal-composer/drupal-project) and automagically applies it to ypur site.

Just add the _GoComposer_ Plug-in to your project, invoke one command and let this Plug-in do the rest...

The aim of this Plug-in is to morph all drupal 8 installation into a universal defualt template.

_Having your Drupal 8 installation modified to this template will simplify future Drupal 8 Core & Contrib Modules updates. It's highly recommended you switch to this template_ 

>## Intended Audience

> If you current Project is a Drupal 8 site with one of the following Scenarios, then _GoComposer_ is your one stop solution:

> * Scenario 1: You have installed your site initially through Composer using the `drupal/drupal` deprecated package.

> * Scenario 2: You have installed your site initially from a `tar.gz` or `zip` file.

> * Scenario 3: You have installed your site using `git clone` from the `Drupal.org` main repo.

## Pre-requisites

> * It's highly recommended to implement this update on your local environment then update your production site

> * You have to have access to the bash shell command line to run this Plug-in. On Mac just use `Terminal`. On Windows 10, it's recomended to install the [Ubuntu Bash shell](https://tutorials.ubuntu.com/tutorial/tutorial-ubuntu-on-windows#0)

> * You have to have Composer installed globally on your local environment, If you haven't already done so, follow the instructions [here to download the executbale](https://getcomposer.org/download/) and [here to add it to your path](https://getcomposer.org/doc/00-intro.md#globally)



## Features

Running the `gocomposer` Command will automate the process of Updating your existing custom site to the latest and greatest Drupal 8 version.

The 'gocomposer' Command will do the following:

* Backup Your Existing Site files and Database and place them in the newly created `backup` folder at your Project Root.

* Download the latest template from the [drupal-project](https://github.com/drupal-composer/drupal-project), Place it in a temporary folder

* Extract the `template.composer.json` which is then populated with your existing site dependencies and then save it as your new `composer.json` file in your project root

* Modify your whole sites folder structure to the new format. Moving the following directories to the new `/web` docroot:
    * `/core`
    * `/sites`
    * `/libraries`
    * `/profiles`
    * `/modules`
    * `/themes`
    
* Updates your `Drupal Core` & `Contrib Modules`  to the latest current version while preserving your existing project dependencies.

* Automatically save your Current environment variables to `.env` in the Project root outside the `/web` docroot for increased security and future compatibility.

* Automatically update your `settings.php` to pull in the Environment Variables from the `.env` file created above. The old `setting.php` file will be saved as `settings_orig.php` in your project root.

* Automatically updates `Drupal Scaffolding` files such as `index.php`, `update.php`, `robots.txt`, etc to the latest version. 

* Runs final clean up scripts that finalize the modifications including updating your database.  


## Installation

```
cd path/to/drupal/project/repo

composer require webkings-ca/gocomposer:dev-master
```

## Usage:
```
cd path/to/drupal/project/repo

composer require webkings-ca/gocomposer:dev-master

composer gocomposer
```

Make sure you are in the Drupal root directory of your project, where `.git` is located.


>Example: 
```
# Drupal Root is located in a `~/Sites/Drupal8project` subdirectory.

cd ~/Sites/Drupal8project

composer require webkings-ca/gocomposer:dev-master

composer gocomposer

```
## Demo

_You can watch a demo for using this Plug-in [here](https://www.youtube.com/watch?v=13tLIoSKr0s&feature=youtu.be)_

[![Drupal 8 GoComposer Demo](https://img.youtube.com/vi/13tLIoSKr0s/0.jpg)](https://vimeo.com/337208342)

## Troubleshooting

> If you are unable to require `webkings-ca/gocomposer` due to your current Configuration try the following:

```$xslt
# Drupal Root is located in a `~/Sites/Drupal8project` subdirectory.

cd ~/Sites/Drupal8project

rm -rf vendor

composer require webkings-ca/gocomposer:dev-master

composer gocomposer

```

> _Should you encounter any issues, Create an issue in the [issue queue](https://github.com/WebKings-ca/gocomposer/issues)_

### Credits:

This project's code base template is taken from [composerize-drupal plugin](https://github.com/grasmash/composerize-drupal). It has been heavily modified to implement it's current functionality.

### Enjoy!
