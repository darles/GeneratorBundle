<?php

namespace Estina\GeneratorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Console\Application;
use Sensio\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator;
use Symfony\Component\Filesystem\FileSystem;

class EstinaGeneratorBundle extends Bundle
{
    public function registerCommands(Application $application){
        $kernel = $this->container->get('kernel');
        $crudCommand = $application->get('doctrine:generate:crud');

        $dirs = array(
            $kernel->getRootDir() . '/Resources/' . $this->getName() . '/skeleton/crud',
            $kernel->locateResource('@' . $this->getName()) . '/Resources/skeleton/crud',
            $kernel->locateResource('@SensioGeneratorBundle') . '/Resources/skeleton/crud',
        );

        $generator = new DoctrineCrudGenerator(new FileSystem, $dirs);
        $crudCommand->setGenerator($generator);

        parent::registerCommands($application);
    }
}
