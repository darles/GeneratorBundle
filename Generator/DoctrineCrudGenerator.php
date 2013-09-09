<?php
namespace Estina\GeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator as BaseGenerator;

class DoctrineCrudGenerator extends BaseGenerator
{
    protected $viewFormat = 'php';

    public function generate(BundleInterface $bundle, $entity, ClassMetadataInfo $metadata, $format, $routePrefix, $needWriteActions, $forceOverwrite)
    {
        parent::generate($bundle, $entity, $metadata, $format, $routePrefix, $needWriteActions, $forceOverwrite);

        $this->generateServiceConfiguration();
        $this->generateServiceClass();
    }

    /**
     * Generates the service configuration.
     *
     */
    protected function generateServiceConfiguration()
    {
        $format = $this->format;
        if ($format != 'yml') {
            $format = 'yml';
        }

        $configName = strtolower(str_replace('\\', '_', $this->entity));
        $target = sprintf(
            '%s/Resources/config/services/%s.%s',
            $this->bundle->getPath(),
            $configName,
            $format
        );

        $this->renderFile('config/services.'.$format.'.twig', $target, array(
            'actions'           => $this->actions,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'bundle'            => $this->bundle->getName(),
            'entity'            => $this->entity,
            'namespace'         => $this->bundle->getNamespace(),
            'service'           => $this->getServiceId(),
            'repository'           => $this->getRepositoryId(),
            'formService'       => $this->getFormServiceId(),
            'formTypeService'       => $this->getFormTypeServiceId(),
        ));

        $services = $this->bundle->getPath() . '/Resources/config/services.' . $format;
        $content = "\n    " . '- { resource: services/' . $configName . '.' . $format . ' }';
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

        $this->renderFile('controller.php.twig', $target, array(
            'actions'           => $this->actions,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'bundle'            => $this->bundle->getName(),
            'entity'            => $this->entity,
            'entity_class'      => $entityClass,
            'namespace'         => $this->bundle->getNamespace(),
            'entity_namespace'  => $entityNamespace,
            'format'            => $this->format,
            // customizations
            'service'           => $this->getServiceId(),
            'formService'       => $this->getFormServiceId(),
            'viewFormat'        => $this->viewFormat,
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
        $target = $dir .'/'. str_replace('\\', '/', $entityNamespace).'/'. $entityClass .'Service.php';

        $methods = $this->getServiceMethods($this->actions);

        $this->renderFile('services/service.php.twig', $target, array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'entity_class'      => $entityClass,
            'namespace'         => $this->bundle->getNamespace(),
            'entity_namespace'  => $entityNamespace,
            'actions'           => $this->actions,
            'methods'           => $methods,
            'form_type_name'    => strtolower(str_replace('\\', '_', $this->bundle->getNamespace()).($parts ? '_' : '').implode('_', $parts).'_'.$entityClass.'Type'),
            'bundle'            => $this->bundle->getName(),
            'service'           => $this->getServiceId(),
        ));
    }

    /**
     * Generates the index.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateIndexView($dir)
    {
        $this->renderFile('views/index.html.' . $this->viewFormat . '.twig', $dir.'/index.html.' . $this->viewFormat, array(
            'entity'            => $this->entity,
            'fields'            => $this->metadata->fieldMappings,
            'actions'           => $this->actions,
            'record_actions'    => $this->getRecordActions(),
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
        ));
    }

    /**
     * Generates the show.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateShowView($dir)
    {
        $this->renderFile('views/show.html.' . $this->viewFormat . '.twig', $dir.'/show.html.' . $this->viewFormat, array(
            'entity'            => $this->entity,
            'fields'            => $this->metadata->fieldMappings,
            'actions'           => $this->actions,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
        ));
    }

    /**
     * Generates the new.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateNewView($dir)
    {
        $this->renderFile('views/new.html.' . $this->viewFormat . '.twig', $dir.'/new.html.' . $this->viewFormat, array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'actions'           => $this->actions,
        ));
    }

    /**
     * Generates the edit.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateEditView($dir)
    {
        $this->renderFile('views/edit.html.' . $this->viewFormat . '.twig', $dir.'/edit.html.' . $this->viewFormat, array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'actions'           => $this->actions,
        ));
    }

    protected function getServiceIdPrefix()
    {
        $id = str_replace('\\', '_', $this->bundle->getNamespace());
        $id = str_replace('Bundle', '', $id);
        $id = strtolower($id);

        return $id;
    }

    protected function getServiceId()
    {
        return strtolower($this->getServiceIdPrefix() . '.service.' .  $this->entity);
    }

    protected function getRepositoryId()
    {
        return strtolower($this->getServiceIdPrefix() . '.repository.' .  $this->entity);
    }

    protected function getFormServiceId()
    {
        return strtolower($this->getServiceIdPrefix() . '.form.' .  $this->entity);
    }    

    protected function getFormTypeServiceId()
    {
        return strtolower($this->getServiceIdPrefix() . '.form.type.' .  $this->entity);
    }

    /**
     * Ger service methods, filter by crud actions
     *
     */
    protected function getServiceMethods($actions)
    {
        $methods = array(
            '__construct',
            'getNew',
            'get',
            'getAll',
            'create',
            'update',
            'delete',
            'save',
        );

        foreach ($actions as $action) {

        }

        return $methods;
    }

    /**
     * Returns an array of record actions to generate (edit, show).
     *
     * @return array
     */
    protected function getRecordActions()
    {
        return array_filter($this->actions, function($item) {
            return in_array($item, array('show', 'edit', 'delete'));
        });
    }
}