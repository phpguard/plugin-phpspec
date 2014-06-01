<?php

/*
 * This file is part of the phpguard-phpspec project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\functional\Bridge;

use PhpGuard\Application\Bridge\CodeCoverage\CodeCoverageSession;
use PhpGuard\Plugins\PhpSpec\Bridge\PhpGuardExtension;
use PhpGuard\Plugins\PhpSpec\Functional\TestCase;
use PhpGuard\Plugins\PhpSpec\Inspector;
use PhpSpec\ServiceContainer;

class PhpGuardExtensionTest extends TestCase
{
    /**
     * @return PhpGuardExtension
     */
    private function getExtension()
    {
        $runner = new \PhpGuard\Application\Bridge\CodeCoverage\CodeCoverageSession();
        $container = new ServiceContainer();
        $container->set('coverage.session',$runner);

        $extension = new PhpGuardExtension();
        $extension->load($container);

        return $extension;
    }

    public function testShouldLoadProperly()
    {
        touch(Inspector::getCacheFileName());
        $this->getExtension();
        $this->assertFalse(file_exists(Inspector::getCacheFileName()));
    }
}
