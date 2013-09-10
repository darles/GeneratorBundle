<?php

namespace Estina\GeneratorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Console\Application;
use Estina\GeneratorBundle\Generator\DoctrineCrudGenerator;
use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator;
use Symfony\Component\Filesystem\Filesystem;

class EstinaGeneratorBundle extends Bundle
{
    public function registerCommands(Application $application)
    {
        $kernel = $this->container->get('kernel');

        $this->registerCrudCommand($application);
        $this->registerBundleCommand($application);

        parent::registerCommands($application);
    }

    /**
     * Load doctrine:generate:crud command and set new generator class instance created with 
     * custom skeleton directories
     *
     * @param Application $application
     *
     */ 
    public function registerCrudCommand(Application $application)
    {
        $crudCommand = $application->get('doctrine:generate:crud');

        $dirs = $this->getDirs('crud');

        $generator = new DoctrineCrudGenerator($this->container->get('filesystem'));
        $generator->setSkeletonDirs($dirs);
        $crudCommand->setGenerator($generator);
    }

    /**
     * Load generate:bundle command and set new generator class instance created with 
     * custom skeleton directories
     *
     * @param Application $application
     *
     */ 
    public function registerBundleCommand(Application $application)
    {
        $crudCommand = $application->get('generate:bundle');

        $dirs = $this->getDirs('bundle');

        $generator = new BundleGenerator($this->container->get('filesystem'));
        $generator->setSkeletonDirs($dirs);
        $crudCommand->setGenerator($generator);
    }

    /**
     * Directory list where to look for skeleton files for particular generatorName
     *
     * @param string $generatorName
     * @return array 
     *
     */
    public function getDirs($generatorName)
    {
        $kernel = $this->container->get('kernel');
        
        $return = array(
            $kernel->getRootDir() . '/Resources/' . $this->getName() . '/skeleton/' . $generatorName,
            $kernel->getRootDir() . '/Resources/' . $this->getName() . '/skeleton',
            $kernel->getRootDir() . '/Resources/SensioGeneratorBundle/skeleton/' . $generatorName,
            $kernel->getRootDir() . '/Resources/SensioGeneratorBundle/skeleton',
            $kernel->locateResource('@' . $this->getName()) . '/Resources/skeleton/' . $generatorName,
            $kernel->locateResource('@' . $this->getName()) . '/Resources/skeleton',
            $kernel->locateResource('@SensioGeneratorBundle') . '/Resources/skeleton/' . $generatorName ,
            $kernel->locateResource('@SensioGeneratorBundle') . '/Resources/skeleton',
        );

        $r = array();
        foreach ($return as $dir) {
            if (is_dir($dir)) {
                $r[] = $dir;
            }
        }

        return $r;
    }
}
