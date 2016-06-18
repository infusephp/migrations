<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace App\Migrations\Console;

use Infuse\HasApp;
use JAQB\Session as DatabaseSession;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    use HasApp;

    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Run app migrations')
            ->addArgument(
                'module',
                InputArgument::OPTIONAL,
                'Specific module to run migrations for'
            )
            ->addArgument(
                'args',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Optional arguments to pass to phinx'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrateArgs = implode(' ', $input->getArgument('args'));
        $result = $this->migrate($input->getArgument('module'), $migrateArgs, $output);

        return $result ? 0 : 1;
    }

    /**
     * Runs migrations for all app modules or a specified module.
     * Also, will setup database sessions if enabled.
     *
     * @param string          $module      optional module
     * @param string          $migrateArgs optional arguments to pass to phinx
     * @param OutputInterface $output
     *
     * @return bool success
     */
    private function migrate($module = '', $migrateArgs, OutputInterface $output)
    {
        $success = true;

        if (empty($migrateArgs)) {
            $migrateArgs = 'migrate';
        }

        if ($migrateArgs == 'migrate') {
            $output->writeln('Running migrations for '.$this->app['environment'].' environment');
        }

        // database sessions
        if (empty($module) && $this->app['config']->get('sessions.adapter') == 'database') {
            $output->writeln('-- Migrating Database Sessions');

            $session = new DatabaseSession($this->app);
            $result = $session->install();

            if ($result) {
                $output->writeln(' == Database Sessions Installed');
            } else {
                $output->writeln('== Error installing Database Sessions');
            }

            $success = $result && $success;
        }

        // module migrations
        $modules = (empty($module)) ? $this->app['config']->get('modules.migrations') : [$module];

        foreach ((array) $modules as $mod) {
            $path = $this->getMigrationsDir($mod);

            if (!$path) {
                continue;
            }

            if ($migrateArgs == 'migrate') {
                $output->writeln("-- Migrating $mod");
            }

            $success = $this->migrateWithPath($path, $migrateArgs, $output) && $success;
        }

        if ($migrateArgs == 'migrate') {
            if ($success) {
                $output->writeln('-- Success!');
            } else {
                $output->writeln('-- Error running migrations');
            }
        }

        return $success;
    }

    /**
     * Gets the migrations directory for a module.
     *
     * @param string $module
     *
     * @return string|false
     */
    private function getMigrationsDir($module)
    {
        // first check if there is a hard-coded migration path
        // in the configuration
        $path = $this->getDirFromConfig($module);

        // next check for the migrations directory in the
        // main app folder
        if (!$path) {
            $path = $this->getDirFromApp($module);
        }

        // finally, attempt to determine the location of the
        // migrations path using reflection
        if (!$path) {
            $path = $this->getDirWithReflection($module);
        }

        return $path;
    }

    private function getDirFromConfig($module)
    {
        $migrationPaths = (array) $this->app['config']->get('modules.migrationPaths');

        $path = array_value($migrationPaths, $module);
        if ($path) {
            return INFUSE_BASE_DIR.'/'.$path;
        }

        return false;
    }

    private function getDirFromApp($module)
    {
        $appDir = $this->app['config']->get('dirs.app');
        $path = "$appDir/$module/migrations";

        if (is_dir($path)) {
            return $path;
        }

        return false;
    }

    private function getDirWithReflection($module)
    {
        $controller = 'App\\'.$module.'\Controller';

        if (class_exists($controller)) {
            $reflection = new ReflectionClass($controller);
            $path = dirname($reflection->getFileName()).'/migrations';

            if (is_dir($path)) {
                return $path;
            }
        }

        return false;
    }

    private function migrateWithPath($path, $migrateArgs, OutputInterface $output)
    {
        $result = 1;
        $command = "PHINX_MIGRATION_PATH=$path php ".INFUSE_BASE_DIR."/vendor/bin/phinx $migrateArgs -c ".INFUSE_BASE_DIR.'/phinx.php';

        ob_start();
        system($command, $result);
        $phinxOutput = ob_get_contents();
        ob_end_clean();

        $lines = explode("\n", $phinxOutput);

        // clean up the output
        foreach ($lines as $line) {
            // when migrating, only output lines starting
            // with ' =='
            if ($migrateArgs != 'migrate' ||
                !empty($line) && substr($line, 0, 3) == ' ==') {
                $output->writeln($line);
            }
        }

        return $result == 0;
    }
}
