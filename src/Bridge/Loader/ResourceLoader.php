<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Bridge\Loader;

use PhpGuard\Application\Util\Locator;
use PhpSpec\Locator\ResourceInterface;
use ReflectionClass;
use ReflectionMethod;
use PhpSpec\Loader\Node\ExampleNode;
use PhpSpec\Loader\Node\SpecificationNode;
use PhpSpec\Loader\Suite;
use PhpSpec\Locator\ResourceManager;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ResourceLoader
 *
 */
class ResourceLoader
{
    protected $manager;

    public function __construct(ResourceManager $manager)
    {
        $this->manager = $manager;
    }

    public function loadSpecFiles(Suite $suite,array $files)
    {
        $loadedFiles = array();
        foreach ($files as $file) {
            $relative = str_replace(getcwd(),'',$file);
            $relative = ltrim($relative,'\\/');
            if (is_dir($file)) {
                //$dirFiles = $this->getSpecFiles($file);
                $this->load($suite,$relative);
            } else {
                if(!in_array($file,$loadedFiles)){
                    $this->load($suite,$file);
                    if ($suite->count()===0) {
                        $this->loadSpec($suite,$file);
                    }
                    $loadedFiles[] = $file;
                }
            }
        }
    }

    private function loadSpec(Suite $suite,$specFile)
    {
        $configFile = null;
        if (is_file($file=getcwd().'/phpspec.yml')) {
            $configFile = $file;
        } elseif (is_file($file=getcwd().'/phpspec.yml.dist')) {
            $configFile = $file;
        }
        if (!is_file($configFile)) {
            return;
        }

        $config = Yaml::parse($configFile);
        if (!isset($config['suites'])) {
            return;
        }

        $absSpecFile = realpath($specFile);
        $manager = $this->manager;
        foreach ($config['suites'] as $name => $definition) {
            $specPath = isset($definition['spec_path']) ? $definition['spec_path']:'spec';
            $absSpecPath = realpath($specPath);
            if (false!==strpos($absSpecFile,$absSpecPath)) {
                $len = strlen($absSpecPath);
                $dir = substr($absSpecFile,0,$len);
                foreach ($manager->locateResources($dir) as $resource) {
                    if (false!==strpos($resource->getSpecFilename(),$absSpecFile)) {
                        $this->importResource($suite,$resource);
                        break;
                    }
                }
                break;
            }
        }
    }

    /**
     * @param $line
     * @param  ReflectionMethod $method
     * @return bool
     */
    protected function lineIsInsideMethod($line, ReflectionMethod $method)
    {
        $line = intval($line);

        return $line >= $method->getStartLine() && $line <= $method->getEndLine();
    }
    /**
     * @param  \ReflectionMethod $method
     * @return bool
     */
    private function methodIsEmpty(\ReflectionMethod $method)
    {
        $filename = $method->getFileName();
        $lines    = explode("\n", file_get_contents($filename));
        $function = trim(implode("\n",
            array_slice($lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine()
            )
        ));

        $function = trim(preg_replace(
            array('|^[^}]*{|', '|}$|', '|//[^\n]*|s', '|/\*.*\*/|s'), '', $function
        ));

        return '' === $function;
    }

    /**
     * @param string       $locator
     * @param integer|null $line
     *
     * @return Suite
     */
    public function load(Suite $suite,$locator, $line = null)
    {
        foreach ($this->manager->locateResources($locator) as $resource) {
            $this->importResource($suite,$resource,$line);
        }
    }

    private function importResource(Suite $suite,ResourceInterface $resource,$line=null)
    {
        if (!class_exists($resource->getSpecClassname()) && is_file($resource->getSpecFilename())) {
            require_once $resource->getSpecFilename();
        }

        if (!class_exists($resource->getSpecClassname())) {
            return;
        }

        $reflection = new ReflectionClass($resource->getSpecClassname());

        if ($reflection->isAbstract()) {
            return;
        }
        if (!$reflection->implementsInterface('PhpSpec\SpecificationInterface')) {
            return;
        }

        $spec = new SpecificationNode($resource->getSrcClassname(), $reflection, $resource);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

            if (!preg_match('/^(it|its)[^a-zA-Z]/', $method->getName())) {
                continue;
            }
            if (null !== $line && !$this->lineIsInsideMethod($line, $method)) {
                continue;
            }

            $example = new ExampleNode(str_replace('_', ' ', $method->getName()), $method);

            if ($this->methodIsEmpty($method)) {
                $example->markAsPending();
            }
            $spec->addExample($example);
        }

        $suite->addSpecification($spec);
    }
}
