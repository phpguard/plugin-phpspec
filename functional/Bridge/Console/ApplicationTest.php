<?php

/*
 * This file is part of the phpguard-phpspec project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\functional\Bridge\Console;

use PhpGuard\Application\PhpGuard;
use PhpGuard\Plugins\PhpSpec\Functional\TestCase;

class ApplicationTest extends TestCase
{
    public function testShouldRunProperly()
    {
        static::buildFixtures();
        static::$container->get('coverage.runner')->preCoverage();
        $tester = $this->getSpecTester();
        $tester->run('run -vvv');
        $this->assertContains('3 passed',$tester->getDisplay());

        $tester->run('run src/psr0 -vvv');
        $this->assertContains('3 passed',$tester->getDisplay());
    }

    public function testShouldRunWithSpecFiles()
    {
        $tester = $this->getSpecTester();
        $tester->run('run --spec-files=src/psr0/namespace1');
        $this->assertContains('1 passed',$tester->getDisplay());

        $tester->run('run --spec-files=src/psr0/namespace1,src/psr0/namespace2');
        $this->assertContains('2 passed',$tester->getDisplay());

        $tester->run('run --spec-files=src/psr0/namespace1/TestClass.php,src/psr0/namespace2/TestClass.php');
        $this->assertContains('2 passed',$tester->getDisplay());

        $tester->run('run --spec-files=src/psr0/namespace1/spec/psr0/namespace1/TestClassSpec.php');
        $this->assertContains('1 passed',$tester->getDisplay());
    }
}
