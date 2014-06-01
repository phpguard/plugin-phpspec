<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Bridge\Console;

use Monolog\ErrorHandler;
use PhpGuard\Application\Bridge\CodeCoverageRunner;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Plugins\PhpSpec\Bridge\PhpGuardExtension;
use PhpGuard\Plugins\PhpSpec\Inspector;
use PhpSpec\Console\Application as BaseApplication;
use PhpSpec\ServiceContainer;

/**
 * Class Application
 *
 */
class Application extends BaseApplication
{
    /**
     * @var Inspector
     */
    protected $inspector;

    /**
     * @var ErrorHandler
     */
    protected $errorHandler;

    protected $errorFile;

    public function __construct()
    {
        parent::__construct('PhpGuard-Spec');
        $this->configureErrorHandler();
    }

    protected function loadConfigurationFile(ServiceContainer $container)
    {
        $container->setShared('event_dispatcher.listeners.phpguard',function ($c) {
            $ext = new PhpGuardExtension();
            if ($runner = CodeCoverageRunner::getCached()) {
                $ext->setCoverageRunner($runner);
            }
            $ext->load($c);

            return $ext;
        });
        parent::loadConfigurationFile($container);
    }

    /**
     * Override setup commands
     *
     * Add Bridge\RunCommand
     * @param ServiceContainer $container
     */
    protected function setupCommands(ServiceContainer $container)
    {
        BaseApplication::setupCommands($container);
        $container->setShared('console.commands.run',function ($c) {
            return new RunCommand();
        });
    }

    private function configureErrorHandler()
    {
        $errorFile = PhpGuard::getPluginCache('phpspec').'/error.log';
        // @codeCoverageIgnoreStart
        if (file_exists($errorFile)) {
            unlink($errorFile);
        }
        // @codeCoverageIgnoreEnd
        touch($errorFile);
        ini_set('display_errors', 1);
        ini_set('error_log',$errorFile);
        $this->errorFile = $errorFile;
        register_shutdown_function(array($this,'handleShutdown'));
    }

    /**
     * @codeCoverageIgnore
     */
    public function handleShutdown()
    {
        $fatalErrors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        $lastError = error_get_last();

        if ($lastError && in_array($lastError['type'],$fatalErrors)) {
            $message = 'Fatal Error '.$lastError['message'];
            $error = $lastError;
            $trace = file($this->errorFile);
            $traces = array();
            for ( $i=0,$count=count($trace);$i < $count; $i++ ) {
                $text = trim($trace[$i]);
                if (false!==($pos=strpos($text,'PHP '))) {
                    $text = substr($text,$pos+4);
                }
                $traces[] = $text;
            }
            $event = ResultEvent::createError(
                $message,
                $error,
                null,
                $traces
            );

            Filesystem::serialize(Inspector::getCacheFileName(),array($event));
        }

        return false;
    }
}
