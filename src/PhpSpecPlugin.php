<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Application\Watcher;
use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Plugins\PhpSpec\Command\DescribeCommand;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PhpSpecPlugin extends Plugin
{
    protected $suites = array();

    public function __construct()
    {
        // set default options for phpspec plugin
        $this->setOptions(array());
    }

    public static function getSubscribedEvents()
    {
        return array(
            ApplicationEvents::started => 'started'
        );
    }

    public function started(GenericEvent $event)
    {
        if ($this->options['all_on_start']) {
            $this->logger->addCommon('Run all on start');
            $this->logger->addDebug('Begin executing all on start');
            $event->addProcessEvent($this->runAll());
            $this->logger->addDebug('End executing all on start');
        }
    }

    public function addWatcher(Watcher $watcher)
    {
        parent::addWatcher($watcher);

        if ($this->options['always_lint']) {
            $options = $watcher->getOptions();
            $linters = array_keys($options['lint']);
            if (!in_array('php',$linters)) {
                $linters[] = 'php';
                $options['lint'] = $linters;
                $watcher->setOptions($options);
            }
        }
    }

    public function configure()
    {
        $container = $this->container;

        // only load command when phpspec package exists
        /* @var \PhpGuard\Application\Console\Application $application */

        $application = $container->get('ui.application');
        $command = new DescribeCommand();
        $command->setContainer($this->container);
        $application->add($command);

        $logger = $this->logger;
        $options = $this->options;

        $container->setShared('phpspec.inspector',function ($c) use ($logger,$options) {
            $inspector = new Inspector();
            $inspector->setLogger($logger);
            $inspector->setContainer($c);

            return $inspector;
        });
    }

    public function getName()
    {
        return 'phpspec';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return 'PhpSpec';
    }

    public function runAll()
    {
        return $this->container->get('phpspec.inspector')->runAll();
    }

    public function run(array $paths = array())
    {
        $specFiles = array();
        foreach ($paths as $file) {
            $spl = PathUtil::createSplFileInfo(getcwd(),$file);
            $relative = $spl->getRelativePathname();
            if (!in_array($relative,$specFiles)) {
                $specFiles[] = $spl->getRelativePathname();
            }
        }
        $inspector = $this->container->get('phpspec.inspector');

        return $inspector->run($specFiles);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'cli' => null,
            'all_on_start' => false,
            'all_after_pass' => false,
            'keep_failed' => false,
            'import_suites' => false, // import suites as watcher
            'always_lint' => true,
            'run_all_cli' => null
        ));
    }
}
