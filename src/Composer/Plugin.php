<?php

namespace WebKings\GoComposer\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable
{
    protected $composer;
    protected $io;


    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function getCapabilities()
    {
        return array(
        'Composer\Plugin\Capability\CommandProvider' => 'WebKings\GoComposer\Composer\CommandProvider',
        );
    }
}
