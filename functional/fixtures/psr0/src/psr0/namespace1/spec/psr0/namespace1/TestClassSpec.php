<?php

namespace spec\psr0\namespace1;

use PhpSpec\ObjectBehavior;

class TestClassSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('psr0\\namespace1\\TestClass');
    }
}
