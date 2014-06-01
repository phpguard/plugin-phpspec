<?php

namespace spec\psr4\namespace2;

use PhpSpec\ObjectBehavior;

class FooSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('psr4\\namespace2\\Foo');
    }
}
