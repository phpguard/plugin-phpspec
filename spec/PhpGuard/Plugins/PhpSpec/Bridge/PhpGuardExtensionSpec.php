<?php

namespace spec\PhpGuard\Plugins\PhpSpec\Bridge;

use PhpGuard\Application\Bridge\CodeCoverage\CodeCoverageSession;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpSpec\Event\ExampleEvent;
use PhpSpec\Loader\Node\ExampleNode;
use PhpSpec\Loader\Node\SpecificationNode;
use Prophecy\Argument;
use PhpSpec\ServiceContainer;

class PhpGuardExtensionSpec extends ObjectBehavior
{
    protected $cwd;

    function let(
        SpecificationNode $specificationNode,
        ExampleEvent $exampleEvent,
        ServiceContainer $container,
        CodeCoverageSession $coverageSession
    )
    {
        $r = new \ReflectionClass(__CLASS__);
        $specificationNode->getClassReflection()->willReturn($r);
        $specificationNode->getTitle()->willReturn('Specification');

        $exampleEvent->getSpecification()
            ->willReturn($specificationNode);
        $exampleEvent->getTitle()
            ->willReturn('it should do something')
        ;
        $this->cwd = getcwd();
        chdir(sys_get_temp_dir());
        $container->get('coverage.session')
            ->willReturn($coverageSession);
        $this->setCoverageRunner($coverageSession);
        $this->load($container);
    }

    function letgo()
    {
        chdir($this->cwd);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\Bridge\PhpGuardExtension');
    }

    function it_should_subscribe_events()
    {
        $events = $this->getSubscribedEvents();
        $events->shouldHaveKey('beforeExample');
        $events->shouldHaveKey('afterExample');
        $events->shouldHaveKey('afterSuite');
    }

    function it_should_be_the_PhpSpec_Extension()
    {
        $this->shouldImplement('PhpSpec\\Extension\\ExtensionInterface');
    }

    function it_should_start_coverage(
        CodeCoverageSession $coverageSession,
        ExampleEvent $event,
        ExampleNode $example,
        SpecificationNode $specificationNode
    )
    {
        $reflection = new \ReflectionClass($this);

        $event->getExample()->willReturn($example);
        $example->getSpecification()->willReturn($specificationNode);
        $example->getTitle()->willReturn('title');
        $specificationNode->getClassReflection()->willReturn($reflection);

        $coverageSession->start(__CLASS__.' => title')
            ->shouldBeCalled();

        $this->beforeExample($event);
    }

    function it_should_creates_result_event(
        ExampleEvent $exampleEvent,
        SpecificationNode $specificationNode,
        CodeCoverageSession $coverageSession
    )
    {
        $exampleEvent->getResult()
            ->shouldBeCalled()
            ->willReturn(ExampleEvent::PASSED)
        ;
        $specificationNode
            ->getTitle()
            ->shouldBeCalled()
            ->willReturn('SomeSpesification')
        ;

        $coverageSession->stop()
            ->shouldBeCalled();

        $this->afterExample($exampleEvent);
        $this->getResults()->shouldHaveCount(1);
    }

    function it_should_save_coverage_sessions(
        \PhpGuard\Application\Bridge\CodeCoverage\CodeCoverageSession $coverageSession
    )
    {
        $coverageSession->saveState()->shouldBeCalled();
        $this->afterSuite();
    }
}