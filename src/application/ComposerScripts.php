<?php

namespace Crm\ApplicationModule;

use Composer\Composer;
use Composer\Factory;
use Composer\Script\Event;
use Composer\SelfUpdate\Versions;
use Nette\Database\DriverException;
use Nette\InvalidArgumentException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;

class ComposerScripts
{
    /**
     * Handle the post-autoload-dump Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postAutoloadDump(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        // This is initialized before calling require_once. Autoloader would otherwise pick up some different (internal)
        // version of Symfony dependencies and cause compatibility issues.
        $installAssets = new ArrayInput(['command' => 'application:install_assets']);

        require_once $vendorDir . '/autoload.php';

        if (file_exists($vendorDir . '/../.env')) {
            try {
                self::runCommand($event, $installAssets);
            } catch (DriverException | InvalidArgumentException $exception) {
                $event->getIO()->write("<warning> CRM </warning> Unable to run <comment>application:install_assets</comment> command, please run <comment>php bin/command.php phinx:migrate</comment> command first.");
            }
        }

        // Running ComposerScripts via Composer (e.g. via post-dump-autoload hook) may pass
        // different parameters to Nette container builder than when it runs in a regular PHP script.
        // Nette container builder may not discover all presenters correctly and container may not be initialized properly (see 'scanComposer' and 'scanDirs' in ApplicationExtension for details).
        // This can cause problems in commands working with presenters - for example, when registering user sources, some presenters could be skipped.
        // Solution: touch a random file (here we've chosen config.neon), so container is forced to reload in a subsequent command/script.
        touch($vendorDir . '/../app/config/config.neon');
    }

    private static function runCommand(Event $event, InputInterface $input)
    {
        $core = new \Crm\ApplicationModule\Core(
            realpath($event->getComposer()->getConfig()->get('vendor-dir') . '/../')
        );
        $container = $core->bootstrap();
        $application = new Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        /** @var ApplicationManager $applicationManager */
        $applicationManager = $container->getByType(\Crm\ApplicationModule\ApplicationManager::class);
        $commands = $applicationManager->getCommands();
        foreach ($commands as $command) {
            $application->add($command);
        }

        $application->run($input);
    }

    public static function checkVersion(Event $event)
    {
        $currentVersion = Composer::getVersion();

        if (version_compare($currentVersion, '2.0.0', '<')) {
            $event->getIO()->write(sprintf(
                'You are using old Composer version (%s), 2.x is required. Please run <comment>composer self-update --2</comment> first.',
                $currentVersion,
            ));
            exit(1);
        }

        $versionsUtil = new Versions(
            $event->getComposer()->getConfig(),
            Factory::createHttpDownloader($event->getIO(), $event->getComposer()->getConfig())
        );
        $latestVersion = $versionsUtil->getLatest()['version'];

        if (version_compare($currentVersion, $latestVersion, '<')) {
            $event->getIO()->write(sprintf(
                'Your Composer version (%s) is too old, %s is required. Please run <comment>composer self-update</comment> first.',
                $currentVersion,
                $latestVersion
            ));
            exit(1);
        }
    }
}
