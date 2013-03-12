<?php
namespace Estina\GeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator as BaseGenerator;

class DoctrineCrudGenerator extends BaseGenerator
{
    public function generate(BundleInterface $bundle, $entity, ClassMetadataInfo $metadata, $format, $routePrefix, $needWriteActions, $forceOverwrite)
    {
        parent::generate($bundle, $entity, $metadata, $format, $routePrefix, $needWriteActions, $forceOverwrite);

        $this->generateServiceClass();
    }

    /**
     * Generates the service configuration.
     *
     */
    protected function generateConfiguration()
    {
        parent::generateConfiguration();

        $configName = strtolower(str_replace('\\', '_', $this->entity));
        $target = sprintf(
            '%s/Resources/config/services/%s.%s',
            $this->bundle->getPath(),
            $configName,
            $this->format
        );

        $this->renderFile($this->skeletonDir, 'config/services.'.$this->format.'.twig', $target, array(
            'actions'           => $this->actions,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'bundle'            => $this->bundle->getName(),
            'entity'            => $this->entity,
            'namespace'         => $this->bundle->getNamespace(),
            'service'           => $this->getServiceId(),
        ));

        $services = $this->bundle->getPath() . '/Resources/config/services.' . $this->format;
        $content = "\n    " . '- { resource: services/' . $configName . '.' . $this->format . ' }';
        if (!strstr(file_get_contents($services), $content)) {
            file_put_contents($services, $content, FILE_APPEND);
        }

    }

    /**
     * Generates the controller class only.
     *
     */
    protected function generateControllerClass($forceOverwrite)
    {
        $dir = $this->bundle->getPath();

        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $entityNamespace = implode('\\', $parts);

        $target = sprintf(
            '%s/Controller/%s/%sController.php',
            $dir,
            str_replace('\\', '/', $entityNamespace),
            $entityClass
        );

        if (!$forceOverwrite && file_exists($target)) {
            throw new \RuntimeException('Unable to generate the controller as it already exists.');
        }

        $this->renderFile($this->skeletonDir, 'controller.php.twig', $target, array(
            'actions'           => $this->actions,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'dir'               => $this->skeletonDir,
            'bundle'            => $this->bundle->getName(),
            'entity'            => $this->entity,
            'entity_class'      => $entityClass,
            'namespace'         => $this->bundle->getNamespace(),
            'entity_namespace'  => $entityNamespace,
            'format'            => $this->format,
            'service'           => $this->getServiceId(),
        ));
    }


    /**
     * Generates the service class only.
     *
     */
    protected function generateServiceClass()
    {
        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $entityNamespace = implode('\\', $parts);

        $dir    = $this->bundle->getPath() .'/Service';
        $target = $dir .'/'. str_replace('\\', '/', $entityNamespace).'/'. $entityClass .'.php';

        $methods = $this->getServiceMethods($this->actions);

        $this->renderFile($this->skeletonDir, 'services/service.php.twig', $target, array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'entity_class'      => $entityClass,
            'namespace'         => $this->bundle->getNamespace(),
            'entity_namespace'  => $entityNamespace,
            'actions'           => $this->actions,
            'methods'           => $methods,
            'form_type_name'    => strtolower(str_replace('\\', '_', $this->bundle->getNamespace()).($parts ? '_' : '').implode('_', $parts).'_'.$entityClass.'Type'),
            'dir'               => $this->skeletonDir,
            'bundle'            => $this->bundle->getName(),
            'service'           => $this->getServiceId(),
        ));
    }

    protected function getServiceId()
    {
        return strtolower( str_replace('Bundle', '', $this->bundle->getName()) . '.service.' .  $this->entity );
    }

    /**
     * Ger service methods, filter by crud actions
     *
     */
    protected function getServiceMethods($actions)
    {
        $methods = array(
            '__construct',
            'get',
            'getAll',
            'create',
            'update',
            'delete',
            'getRepository',
        );

        foreach ($actions as $action) {

        }

        return $methods;
    }
}