<?php

namespace WebKings\GoComposer\Composer;

use DrupalFinder\DrupalFinder;
use WebKings\GoComposer\Utility\ComposerJsonManipulator;
use WebKings\GoComposer\Utility\DrupalInspector;

use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Symfony\Component\Console\Question\Question;

use Symfony\Component\Console\Question\ChoiceQuestion;

class GoComposerCommand extends BaseCommand
{

    /** @var InputInterface */
    protected $input;
    protected $baseDir;
    protected $composerConverterDir;
    protected $templateComposerJson;
    protected $rootComposerJsonPath;
    protected $drupalRoot;
    protected $drupalRootRelative;
    protected $drupalCoreVersion;

    protected $project_root_found;

    protected $DB;

    protected $settings_used;


    /** @var Filesystem */
    protected $fs;

    protected $output;

    protected $io;


    public function configure()
    {
        $this->setName('gocomposer');
        $this->setDescription("Updates Drupal 8 sites to the latest recommended template of a fully Composer managed Site");
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output= $output;
        $io = new SymfonyStyle($input, $output);
        $this->io= $io;
        $io->title('GoComposer is Initializing...');
        $this->fs = new Filesystem();
        $this->setDirectories($input);
        $this->drupalCoreVersion = $this->determineDrupalCoreVersion();

        $this->io->newLine();

        $this->io->note('Your Current Drupal Core Version ='.$this->drupalCoreVersion);

        $this->getCurrentEnvironment();
        $this->getLatestDrupalVersion();

        $this->removeAllComposerFiles();
        $this->createNewComposerJson();
        $this->addRequirementsToComposerJson();
        $this->mergeTemplateGitignore();

        $exit_code = 0;


        $this->composerInstallNew();

        if (!$exit_code) {
            $this->printPostScript();
        }


        return $exit_code;
    }


    /**
     * @return mixed
     */
    public function getCurrentEnvironment()
    {

        $this->io->newLine();

        $this->io->section('Current Step: Saving your Original Environment.');

        $MainDir = $this->baseDir;

        $drupal_root = $this->drupalRoot;

        $PluginDir = $this->composerConverterDir;

        $DB = $this->DB;


        if ($DB =="") {
            $settingsPath = $MainDir . "/CurrEnv.txt";

            if (!(file_exists($settingsPath))) {
                $message = "Current Environment file has not bean Saved...";

                goto skip;
            }


            $lines = file($settingsPath);


            $flag_prefix = false;


            $str = implode(" ", $lines);

            if (strpos($str, 'Array') !== false) {
                $flag_array = true;
            } else {
                $flag_array = false;
            }


            $current = "";

            $current .= "# Your Current Environment\r\n";

            $current .= "\r\n";

            $current .= "\r\n";


            $settings_orig = [];

            $settings_new = [];
            $settings_new['default'] = "\$databases[\'default\'][\'default\'] = array ( \r\n";


            foreach ($lines as $line_num => $line) {
                $trimmed = trim($line, "\n");

                if ($flag_array) {
                    $pattern = '=>';
                } else {
                    $pattern = ':';
                }

                if ($right_side = strstr($trimmed, $pattern)) {
                    $left_side = strstr($trimmed, $pattern, true);

                    if ($flag_array) {
                        $right_trimmed = trim($right_side, "\x3D, \x3E");
                        $left_trimmed = trim($left_side, "\x5B, \x5D");
                    } else {
                        $right_trimmed = trim($right_side, "\x3A, \x20");
                        $left_trimmed = $left_side;
                    }

                    switch ($left_trimmed) {
                        case 'database':
                            $settings_orig['database'] = $right_trimmed;
                            $settings_new['database'] = "  \'database\' => getenv(\'MYSQL_DATABASE\'),\r\n";
                            $db_line = 'MYSQL_DATABASE=' . $right_trimmed . "\r\n";
                            $current .= $db_line;
                            break;

                        case 'username':
                            $settings_orig['username'] = $right_trimmed;
                            $settings_new['username'] = "  \'username\' => \'$right_trimmed',\r\n";
                            $db_line = 'MYSQL_USER=' . $right_trimmed . "\r\n";
                            $current .= $db_line;
                            break;

                        case 'password':
                            $settings_orig['password'] = $right_trimmed;
                            $settings_new['password'] = "  \'password\' => getenv(\'MYSQL_PASSWORD\'),\r\n";
                            $db_line = 'MYSQL_PASSWORD=' . $right_trimmed . "\r\n";
                            $current .= $db_line;
                            break;

                        case 'prefix':
                            $settings_orig['prefix'] = $right_trimmed;
                            $settings_new['prefix'] = "  \'prefix\' => getenv(\'MYSQL_USER\'),\r\n";

                            $flag_prefix = true;
                            break;

                        case 'default':
                            if ($flag_prefix) {
                                $settings_orig['default'] = $right_trimmed;
                                $db_line = 'MYSQL_PREFIX_DEFAULT=' . $right_trimmed . "\r\n";
                                $current .= $db_line;
                                $flag_prefix = false;
                            }
                            break;

                        case 'host':
                            $settings_orig['host'] = $right_trimmed;
                            $settings_new['host'] = "  \'host\' => getenv(\'MYSQL_HOSTNAME\')\r\n";
                            $db_line = 'MYSQL_HOSTNAME=' . $right_trimmed . "\r\n";
                            $current .= $db_line;
                            break;

                        case 'port':
                            $settings_orig['port'] = $right_trimmed;
                            $settings_new['port'] = "  \'port\' => getenv(\'MYSQL_PORT\'),\r\n";
                            $db_line = 'MYSQL_PORT=' . $right_trimmed . "\r\n";
                            $current .= $db_line;
                            break;

                        case 'namespace':
                            $settings_orig['namespace'] = $right_trimmed;
                            $settings_new['namespace'] = "  \'namespace\' => \'$right_trimmed',\r\n";
                            $db_line = 'MYSQL_NAMESPACE=' . $right_trimmed . "\r\n";
                            $current .= $db_line;
                            break;

                        case 'driver':
                            $settings_orig['driver'] = $right_trimmed;
                            $settings_new['driver'] = "  \'driver\' => \'$right_trimmed',\r\n";
                            $db_line = 'MYSQL_DRIVER=' . $right_trimmed . "\r\n";
                            $current .= $db_line;
                            break;
                    }
                };
            }


            $settings_new['last'] = ");\r\n";

            $current .= "\r\n";

            $current .= "\r\n";

            $current .= "# Another common use case is to set Drush's --uri via environment.\r\n";

            $current .= "# DRUSH_OPTIONS_URI=http://example.com\r\n";


            unlink($settingsPath);
        } else {
            $current = "";

            $current .= "# Your Current Environment\r\n";

            $current .= "\r\n";

            $current .= "\r\n";

            $db_line = 'MYSQL_DATABASE=' . $DB['database']. "\r\n";
            $current .= $db_line;

            $db_line = 'MYSQL_USER=' .$DB['username'] . "\r\n";
            $current .= $db_line;

            $db_line = 'MYSQL_PASSWORD=' . $DB['password']. "\r\n";
            $current .= $db_line;

            $db_line = 'MYSQL_PREFIX_DEFAULT=' .$DB['prefix'] . "\r\n";
            $current .= $db_line;


            $db_line = 'MYSQL_HOSTNAME=' . $DB['host']  . "\r\n";
            $current .= $db_line;

            $db_line = 'MYSQL_PORT=' .$DB['port'] . "\r\n";
            $current .= $db_line;

            $db_line = 'MYSQL_NAMESPACE=' . $DB['namespace']. "\r\n";
            $current .= $db_line;

            $db_line = 'MYSQL_DRIVER=' . $DB['driver'] . "\r\n";
            $current .= $db_line;

            $current .= "\r\n";

            $current .= "\r\n";

            $current .= "# Another common use case is to set Drush's --uri via environment.\r\n";

            $current .= "# DRUSH_OPTIONS_URI=http://example.com\r\n";
        }

        $file=$MainDir.'/.env';


        file_put_contents($file, $current);


        $this->io->newLine();

        $this->io->success('Success: Environment Has been Saved to the .env file..');


        ///----------------------------- Settings Updator -----------------------------------------------//


        $id1 = '/(\$databases\[\'default\'\]\[\'default\'\])/';

        $id2 = '/(\)\;)/';

        $settings_used = $this->settings_used;

        if ($settings_used != "") {
            $settings_old = trim($settings_used, "\x03, \x0A");
        } else {
            $settings_old = $drupal_root."/sites/default/settings.php";
        }


        if (!(file_exists($settings_old))) {
            $message = "Settings.php path is unknown.. ";

            goto skip;
        }

        $handle = fopen($settings_old, 'r');


        $settings_new_file = $MainDir."/settings.tmp.php";

        $new_file = fopen($settings_new_file, 'w');

        $settings_orig= $MainDir."/settings_orig.php";

        $counter = 0;

        $valid = false;

        $needle = 0;

        $settings_new['database'] =  "  'database' => getenv('MYSQL_DATABASE'),";

        $settings_new['host'] ="   'host' => getenv('MYSQL_HOSTNAME'),";

        $settings_new['password']="   'password' => getenv('MYSQL_PASSWORD'),";

        $settings_new['port']="   'port' => getenv('MYSQL_PORT'),";

        $settings_new['username']="   'username' => getenv('MYSQL_USER'),";

        $db_string = 'database';

        $db_host = 'host';

        $db_pwd = 'password';

        $db_port = 'port';

        $db_user = 'username';


        while (($buffer = fgets($handle)) !== false) {
            $line_new = $buffer;

            $counter++;

            $buff_trimmed = trim($buffer, "\n");


            if (preg_match($id1, $buff_trimmed, $matches, PREG_OFFSET_CAPTURE) == 1) {
                $needle=1;

                if ($matches[0][1]==0) {
                    $valid =true;
                }
            }

            if ($valid) {
                if (preg_match($id2, $buff_trimmed, $matches2, PREG_OFFSET_CAPTURE) == 1) {
                    $valid = false;
                }

                if (($valid) && ($needle>1)) {
                    if (strstr($buff_trimmed, $db_string)) {
                        $line_new = $settings_new['database']."\n";
                    }

                    if (strstr($buff_trimmed, $db_host)) {
                        $line_new = $settings_new['host']."\n";
                    }

                    if (strstr($buff_trimmed, $db_pwd)) {
                        $line_new = $settings_new['password']."\n";
                    }

                    if (strstr($buff_trimmed, $db_port)) {
                        $line_new = $settings_new['port']."\n";
                    }

                    if (strstr($buff_trimmed, $db_user)) {
                        $line_new = $settings_new['username']."\n";
                    }
                }


                $needle++;
            }



            fputs($new_file, $line_new);
        }
        fclose($new_file);

        fclose($handle);

        copy($settings_old, $settings_orig);

        rename($settings_new_file, $settings_old);

        $settings_orig_web = $MainDir.'/settings_orig.php';

        $this->io->newLine();

        $this->io->success('Success: Your settings.php file has been modified to pull environment Configuration for the root .env file...');

        $this->io->newLine();

        $this->io->note('Your Original settings.php file can be found in: '.$settings_orig_web);

        return;

        skip:

        $this->io->newLine();

        $this->io->warning('Saving your Current Environment Variables has failed due to the reason :'.$message." Skipping this step...");

        $this->io->newLine();


        return;
    }
    /**
     * @return mixed
     */
    public function getLatestDrupalVersion()
    {

        $this->io->newLine();

        $this->io->section('Current Step: Getting Latest template for drupal-composer/drupal-project');

        $PluginDir = $this->composerConverterDir;

        $MainDir = $this->baseDir;

        $drupal_root = $this->drupalRoot;

        $composerPhar = $_SERVER['SCRIPT_FILENAME'];

        $output = shell_exec("bash ".$PluginDir.'/script.sh '.$PluginDir.' '. $MainDir.' '.$composerPhar.' '.$drupal_root);

        echo $output;


        return;
    }

    /**
     * @return mixed
     */
    public function composerInstallNew()
    {
        $this->io->newLine();

        $this->io->section('Current Step: Modifying your file structure to the Latest template from drupal-composer/drupal-project');

        $PluginDir = $this->composerConverterDir;

        $MainDir = $this->baseDir;

        $drupal_root = $this->drupalRoot;

        $composerPhar = $_SERVER['SCRIPT_FILENAME'];

        $output = shell_exec("bash ".$PluginDir.'/script2.sh '.$PluginDir.' '. $MainDir.' '.$composerPhar.' '.$drupal_root);

        echo $output;


        return;
    }

    /**
     * @return mixed
     */
    public function getTemplateComposerJson()
    {
        if (!isset($this->templateComposerJson)) {
            $this->templateComposerJson = $this->loadTemplateComposerJson();
        }

        return $this->templateComposerJson;
    }

    /**
     * @return mixed
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * @param mixed $baseDir
     */
    public function setBaseDir($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    /**
     * @return mixed
     */
    protected function loadTemplateComposerJson()
    {
        $template_composer_json = json_decode(file_get_contents($this->composerConverterDir . "/template.composer.json"));
        ComposerJsonManipulator::processPaths($template_composer_json, $this->drupalRootRelative);


        return $template_composer_json;
    }

    protected function loadRootComposerJson()
    {
        return json_decode(file_get_contents($this->rootComposerJsonPath));
    }

    protected function createNewComposerJson()
    {
        ComposerJsonManipulator::writeObjectToJsonFile(
            $this->getTemplateComposerJson(),
            $this->rootComposerJsonPath
        );
        $this->getIO()->write("<info>Created composer.json</info>");
    }

    protected function addRequirementsToComposerJson()
    {
        $root_composer_json = $this->loadRootComposerJson();
        $this->requireContribProjects($root_composer_json);
        $this->requireDrupalCore($root_composer_json);
        ComposerJsonManipulator::writeObjectToJsonFile(
            $root_composer_json,
            $this->rootComposerJsonPath
        );
    }

    /**
     * @param $matches
     *
     * @throws \Exception
     */
    protected function determineDrupalCoreVersion()
    {
        if (file_exists($this->drupalRoot . "/core/lib/Drupal.php")) {
            $bootstrap =  file_get_contents($this->drupalRoot . "/core/lib/Drupal.php");
            preg_match('|(const VERSION = \')(\d\.\d\.\d)\';|', $bootstrap, $matches);
            if (array_key_exists(2, $matches)) {
                return $matches[2];
            }
        }
        if (!isset($this->drupalCoreVersion)) {
            throw new \Exception("Unable to determine Drupal core version.");
        }
    }

    /**
     * @param $root_composer_json
     */
    protected function requireContribProjects($root_composer_json)
    {
        $modules = DrupalInspector::findContribProjects($this->drupalRoot, "modules/contrib");
        $themes = DrupalInspector::findContribProjects($this->drupalRoot, "themes/contrib");
        $profiles = DrupalInspector::findContribProjects($this->drupalRoot, "profiles/contrib");


        $projects = array_merge($modules, $themes, $profiles);


        foreach ($projects as $project => $version) {
            $package_name = "drupal/$project";

            if ($version != "@dev") {
                $version_constraint = $this->getVersionConstraint($version);
            } else {
                $version_constraint = "@dev";
            }



            $root_composer_json->require->{$package_name} = $version_constraint;
            $this->getIO()->write("<info>Added $package_name $version_constraint to requirements.</info>");
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     */
    protected function setDirectories(InputInterface $input)
    {
        $this->composerConverterDir = dirname(dirname(__DIR__));
        $drupalFinder = new DrupalFinder();
        $this->determineDrupalRoot($input, $drupalFinder);
        $this->determineComposerRoot($input, $drupalFinder);
        $this->drupalRootRelative = trim($this->fs->makePathRelative(
            $this->drupalRoot,
            $this->baseDir
        ), '/');
        $this->rootComposerJsonPath = $this->baseDir . "/composer.json";
    }


    /**
     *
     */
    protected function mergeTemplateGitignore()
    {
        $template_gitignore = file($this->composerConverterDir . "/template.gitignore");
        $gitignore_entries = [];


        $relative_modified = "web"; // Replaced $this->drupalRootRelative

        foreach ($template_gitignore as $key => $line) {
            $gitignore_entries[] = str_replace(
                '[drupal-root]',
                $relative_modified,
                $line
            );
        }
        $root_gitignore_path = $this->getBaseDir() . "/.gitignore";
        $verb = "modified";
        if (!file_exists($root_gitignore_path)) {
            $verb = "created";
            $this->fs->touch($root_gitignore_path);
        }
        $root_gitignore = file($root_gitignore_path);
        foreach ($root_gitignore as $key => $line) {
            if ($key_to_remove = array_search($line, $gitignore_entries)) {
                unset($gitignore_entries[$key_to_remove]);
            }
        }
        $merged_gitignore = $root_gitignore + $gitignore_entries;
        file_put_contents(
            $root_gitignore_path,
            implode('', $merged_gitignore)
        );

        $this->getIO()->write("<info>$verb .gitignore. Composer dependencies will NOT be committed.</info>");
    }

    /**
     * @param $root_composer_json
     */
    protected function requireDrupalCore($root_composer_json)
    {
        $version_constraint = $this->getVersionConstraint($this->drupalCoreVersion);
        $root_composer_json->require->{'drupal/core'} = $version_constraint;
        $this->getIO()
            ->write("<info>Added drupal/core $version_constraint to requirements.</info>");
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param DrupalFinder $drupalFinder
     *
     * @throws \Exception
     */
    protected function determineComposerRoot(
        InputInterface $input,
        DrupalFinder $drupalFinder
    ) {
        if (!($this->project_root_found)) {
            $this->baseDir = $drupalFinder->getComposerRoot();
        }

        if (!(isset($this->baseDir))) {
            throw new \Exception("Project Root is Not set.... Please change directories to a valid Drupal 8 application. Make sure you are in the correct directory...");
        }

        $output = $this->output;
        $outputStyle = new OutputFormatterStyle('cyan', 'black', array('bold'));
        $output->getFormatter()->setStyle('fire', $outputStyle);

        $this->io->newLine();

        $confirm = $this->io->confirm('Please Confirm: Composer.json file will be Created in <fire>'.$this->baseDir.' </fire>(Recommended)', true);


        if (!($confirm)) {
            $this->io->newLine(2);
            $this->io->warning('Aborting... Composer.json path is not specified');

            $this->io->newLine(2);

            throw new \Exception("Please try to Run the Command again in your Original Project Root..");
        }


        $old_composer = $this->baseDir.'/composer.json';

        $drush_installed = "no";

        $message ="";


        if (file_exists($old_composer)) {
            $file_handler = fopen($old_composer, 'r');

            while (($buffer = fgets($file_handler)) !== false) {
                $pattern = '/(\"name\": \"drupal-composer\/drupal-project\")/';

                $pattern2 = '/(\"drush\/drush\")/';

                $buff_trimmed = trim($buffer, "\n");

                if (preg_match($pattern, $buff_trimmed, $matches, PREG_OFFSET_CAPTURE) == 1) {
                    throw new \Exception("You current installation is already of type drupal-composer/drupal-project... Aborting...");
                }

                if (preg_match($pattern2, $buff_trimmed, $matches2, PREG_OFFSET_CAPTURE) == 1) {
                    $drush_installed = "yes";
                }
            }

            fclose($file_handler);

            $confirm = $this->io->confirm('Please Confirm: Your Original Site will be Backed up to the following directory:  <fire>'.$this->baseDir.'/backup </fire>(Recommended)', true);

            $backup_req = "yes";


            if (!($confirm)) {
                $backup_req = "no";
            }

            $MainDir = $this->baseDir;

            $PluginDir = $this->composerConverterDir;

            $drupal_root = $this->drupalRoot;

            $flag_settings_not_found = false;


            $setPath = shell_exec('find '.$drupal_root.'/sites -name "settings.php" > '.$MainDir.'/options_settings.txt');

            $optionspath = $MainDir.'/options_settings.txt';

            if (!(file_exists($optionspath))) {
                $message = "options txt file not found....";
                $flag_settings_not_found = true;

                goto finalize;
            }

            $lines = file($optionspath);

            $numb_lines = count($lines);


            $rec_version ="";

            $tmp1 = "";

            if ($numb_lines > 1) {
                foreach ($lines as $line_num => $line) {
                    $options[$line_num] = $line;


                    $tmp1 = substr($options[0], 0, -14);


                    $len1 = strlen($tmp1);

                    $len2 = $len1 - 7;

                    $tmp2 = substr($tmp1, $len2, 7);

                    if ($tmp2 == 'default') {
                        $rec_version = ($line_num - 1);
                    }
                };
            } else {
                $options[0] = $lines[0];

                $tmp1 = substr($options[0], 0, -14);


                $len1 = strlen($tmp1);

                $len2 = $len1 - 7;

                $tmp2 = substr($tmp1, $len2, 7);

                if ($tmp2 == 'default') {
                    $rec_version = 0;
                }
            }

            if (!(isset($tmp1))) {
                $message = "Error: Value for default folder not found.... Aborting...";
                $flag_settings_not_found = true;
                goto finalize;
            }

            if ($rec_version == "") {
                $rec_version = 0;
            }






            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                '<fire> Please select which Settings.php file you want to use for your Environment (Recommended Version is: [' . $rec_version . ']) </fire>',
                $options,
                0
            );
            $output = $this->output;
            $outputStyle = new OutputFormatterStyle('cyan', 'black', array('bold'));
            $output->getFormatter()->setStyle('fire', $outputStyle);




            $question->setErrorMessage('This option %s is invalid.');

            $selected = $helper->ask($input, $output, $question);

            $output->writeln('<fire> You have just selected: </fire>' . $selected);


            $new_set_path = substr($selected, 0, -14);


            $output = shell_exec("bash ".$PluginDir . '/initializer.sh ' . $PluginDir . ' ' . $MainDir . ' ' . $tmp1 . ' ' . $selected);

            echo $output;


            $app_root = $MainDir;


            $len_new_set_path = strlen($new_set_path);

            $len_app_root = strlen($app_root);

            $difference = $len_new_set_path - $len_app_root;


            if ($difference <= 0) {
                $message = "Error: Invalid values for app_root and site_path.. Aborting..";
                $flag_settings_not_found = true;
                goto finalize;
            }


            $s_path = substr($new_set_path, ($len_app_root + 1), $difference);

            $site_path = $s_path;

            try {
                $flag_local = false;

                $local_mod = false;

                $modified_lines = "";

                $new_file = $new_set_path . "/settings.php";

                $tmp_file = $new_set_path ."/settings.tmpy.php ";

                copy($new_file, $tmp_file);

                if (file_exists($new_set_path."/settings.locaL.php")) {
                    $lines = file($new_set_path."/settings.php");

                    $local_mod = true;

                    foreach ($lines as $line_num => $line) {
                        $pattern1 = '/(if \(file_exists\(__DIR__ . \'\/settings.local.php\'\)\))/';

                        $pattern2 =   '/(})/';


                        if (preg_match($pattern1, $line, $matches, PREG_OFFSET_CAPTURE == 1)) {
                            $flag_local = true;
                        }

                        if ($flag_local) {
                            $modified_lines .= "";
                        } else {
                            $modified_lines .= $line;
                        }

                        if (preg_match($pattern2, $line, $matches, PREG_OFFSET_CAPTURE == 1)) {
                            $flag_local = false;
                        }
                    }
                    file_put_contents($new_file, $modified_lines);
                }





                include $new_file;
            } catch (Exception $e) {
                $message = 'Caught exception: '. $e->getMessage(). "\n";
                $flag_settings_not_found = true;
                goto finalize;
            }


            if (!(isset($databases))) {
                $message = "Error: Unable to find Databases Credentials. Aborting..";
                $flag_settings_not_found = true;
                goto finalize;
            }

            finalize:

            if (!($flag_settings_not_found)) {
                $DB['host'] = $databases['default']['default']['host'];
                $DB['database'] = $databases['default']['default']['database'];
                $DB['username'] = $databases['default']['default']['username'];
                $DB['password'] = $databases['default']['default']['password'];
                $DB['prefix'] = $databases['default']['default']['prefix'];
                $DB['port'] = $databases['default']['default']['port'];
                $DB['namespace'] = $databases['default']['default']['namespace'];
                $DB['driver'] = $databases['default']['default']['driver'];

                $flag = "yes";

                $this->DB = $DB;

                $this->settings_used = $selected;
            } else {
                $flag = "no";

                $this->DB = "";

                $this->settings_used = "";

                $this->io->newLine();
                $this->io->warning('Saving Environment from settings.php directly didnt succeed. Reason : '.$message);
            }

            if ($local_mod) {
                $new_file = $new_set_path . "/settings.php";

                $tmp_file = $new_set_path ."/settings.tmpy.php ";

                copy($tmp_file, $new_file);
            }



            $install_drush = "no";

            if ((($flag == "no") && ($drush_installed == "no")) || (($flag == "yes") && ($backup_req == "yes") && ($drush_installed == "no"))) {
                $install_drush = "yes";
            }

            $composerPhar = $_SERVER['SCRIPT_FILENAME'];


            $output = shell_exec("bash ".$PluginDir.'/drushScript.sh '.$PluginDir.' '. $MainDir.' '.$drush_installed.' '.$backup_req.' '.$drupal_root.' '.$flag. ' '.$install_drush. ' '.$composerPhar);

            echo $output;


            if ($backup_req == "yes") {
                $this->io->newLine();
                $this->io->success('Success: Your Site Files and Database have been backedup to the /backup folder..');
            }
        }
    }


    /**
     * @param InputInterface $input
     * @param DrupalFinder $drupalFinder
     *
     * @throws \Exception
     */
    protected function determineDrupalRoot(InputInterface $input, DrupalFinder $drupalFinder)
    {

        $common_drupal_root_subdirs = [
            'docroot',
            'web',
            'htdocs',
        ];

        $project_found = false;
        $this->project_root_found = false;
        $root = getcwd();
        $temproot = $root;

        foreach ($common_drupal_root_subdirs as $candidate) {
            if (file_exists("$root/$candidate")) {
                $root = "$root/$candidate";

                break;
            }
        }

        if ($drupalFinder->locateRoot($root)) {
            $this->drupalRoot = $drupalFinder->getDrupalRoot();
            if (!$this->fs->isAbsolutePath($root)) {
                $this->drupalRoot = getcwd() . "/$root";
            }

            $project_found = true;
        }

        $candidate2 = 'core/includes/bootstrap.inc';
        if ((file_exists($temproot . '/' . $candidate2)) && (!($project_found))) {
            $this->drupalRoot = $temproot;
            $this->baseDir = $temproot;

            $this->project_root_found = true;
        }

        if (!(isset($this->drupalRoot))) {
            throw new \Exception("Drupal Root is Not set.... Please change directories to a valid Drupal 8 application. Make sure you are in the correct directory...");
        }
    }

    /**
     * Removes all composer.json and composer.lock files recursively.
     */
    protected function removeAllComposerFiles()
    {
        $finder = new Finder();
        $finder->in($this->baseDir)
            ->files()
            ->name('/^composer\.(lock|json)$/');
        $files = iterator_to_array($finder);
        $this->fs->remove($files);
    }

    /**
     * @param $version
     *
     * @return string
     */
    protected function getVersionConstraint($version)
    {

        $version_constraint = "^" . $version;

        return $version_constraint;
    }

    protected function finalScript()
    {
        $MainDir = $this->baseDir;

        $PluginDir = $this->composerConverterDir;

        $composerPhar = $_SERVER['SCRIPT_FILENAME'];

        $output = shell_exec("bash ".$PluginDir.'/autoFinalize.sh '.$PluginDir.' '. $MainDir.' '.$composerPhar);

        echo $output;

        $this->io->newLine();

        $this->io->section('Current Step: Using Drush to update the Database... Press Enter to Continue...');

        $finalOutput = shell_exec('cd '.$MainDir.'&&'.$MainDir.'/vendor/bin/drush updb');

        echo $finalOutput;
    }

    protected function printPostScript()
    {

        $this->io->newLine();

        $this->io->section('Current Step: Running autoFinalize Script...');


        $this->finalScript();

        $this->io->newLine();

        $this->io->success('Congrats!... You have Successfully updated your Site... The old site files and Database Sql dump are saved in the backup folder');

        $this->io->newLine();

        $this->io->note('Your New Docroot is in the newly created /web directory... Dont forget to update you vhosts file by adding /web to the site path...');
    }
}
