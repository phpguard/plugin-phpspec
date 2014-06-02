<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Bridge;

use PhpGuard\Application\Bridge\CodeCoverage\CodeCoverageSession;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Plugins\PhpSpec\Inspector;
use PhpSpec\Event\ExampleEvent;
use PhpSpec\Extension\ExtensionInterface;
use PhpSpec\Loader\Node\SpecificationNode;
use PhpSpec\ServiceContainer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PhpGuardExtension
 *
 */
class PhpGuardExtension implements ExtensionInterface,EventSubscriberInterface
{
    /**
     * @var ResultEvent[]
     */
    private $results = array();

    private $map = array();

    /**
     * @var \PhpGuard\Application\Bridge\CodeCoverage\CodeCoverageSession
     */
    private $coverage;

    /**
     * @param ServiceContainer $container
     */
    public function load(ServiceContainer $container)
    {
        /* @var EventDispatcherInterface $dispatcher */

        $file = Inspector::getCacheFileName();
        if (file_exists($file)) {
            unlink($file);// @codeCoverageIgnore
        }

        $this->map = array(
            ExampleEvent::FAILED => ResultEvent::FAILED,
            ExampleEvent::BROKEN => ResultEvent::BROKEN,
            ExampleEvent::PASSED => ResultEvent::SUCCEED,
            ExampleEvent::PENDING => ResultEvent::FAILED,
            ExampleEvent::SKIPPED => ResultEvent::BROKEN,
        );
    }

    public function setCoverageRunner(CodeCoverageSession $runner)
    {
        $this->coverage = $runner;
    }

    public function afterSuite()
    {
        Filesystem::create()->serialize(Inspector::getCacheFileName(),$this->results);
        if ($this->coverage) {
            $this->coverage->saveState();
        }
    }

    public function beforeExample(ExampleEvent $event)
    {
        $example = $event->getExample();

        $name = strtr('%spec% => %example%', array(
            '%spec%' => $example->getSpecification()->getClassReflection()->getName(),
            '%example%' => $example->getTitle(),
        ));

        if ($this->coverage) {
            $this->coverage->start($name);
        }
    }

    public function afterExample(ExampleEvent $event)
    {
        $type = $this->map[$event->getResult()];
        $this->addResult($type,$event->getSpecification(),$event->getTitle());
        if ($this->coverage) {
            $this->coverage->stop();
        }
    }

    public function getResults()
    {
        return $this->results;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'beforeExample' => array('beforeExample',-10),
            'afterExample'  => array('afterExample', -10),
            'afterSuite'    => array('afterSuite', -10),
        );
    }

    private function addResult($result,SpecificationNode $spec,$title=null)
    {
        $map = array(
            ResultEvent::SUCCEED => 'Succeed: %title%',
            ResultEvent::FAILED => 'Failed: %title%',
            ResultEvent::BROKEN => 'Broken: %title%',
            ResultEvent::ERROR => 'Error: %title%',
        );
        $r = $spec->getClassReflection();
        $arguments = array(
            'file' => $r->getFileName(),
        );
        $key = md5($r->getFileName().$title);

        $format = $map[$result];
        $title = $title == null ? $spec->getTitle():$spec->getTitle().'::'.$title;
        $message = strtr($format,array(
            '%title%' => '<highlight>'.$title.'</highlight>',
        ));
        $this->results[$key] = ResultEvent::create($result,$message,$arguments);
    }
}
