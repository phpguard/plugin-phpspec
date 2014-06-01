<?php

namespace spec\PhpGuard\Plugins\PhpSpec\Bridge\Loader;

use PhpSpec\Locator\ResourceManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ResourceLoaderSpec extends ObjectBehavior
{
    function let(ResourceManager $manager)
    {
        $this->beConstructedWith($manager);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\Bridge\Loader\ResourceLoader');
    }
}
