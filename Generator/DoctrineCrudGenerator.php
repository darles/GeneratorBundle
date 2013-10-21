<?php
namespace Estina\GeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator as BaseGenerator;

class DoctrineCrudGenerator extends BaseGenerator
{
    protected $viewFormat = 'php';
    protected $translationPrefix = '';
    protected $translationCorePrefix = '';

    public function generate(BundleInterface $bundle, $entity, ClassMetadataInfo $metadata, $format, $routePrefix, $needWriteActions, $forceOverwrite)
    {
        $routePrefix = $this->generateRoutePrefix($bundle, $routePrefix);
        $this->translationPrefix = $routePrefix . '.';
        $this->translationGlobalPrefix = 'global.';

        parent::generate($bundle, $entity, $metadata, $format, $routePrefix, $needWriteActions, $forceOverwrite);
        $this->generateServiceConfiguration();
        $this->generateServiceClass();
        $this->generateServiceTestClass();
        $this->generateDictionary();
        $this->generateDataFixture();
    }

    /**
     * Creates route prefix
     */
    protected function generateRoutePrefix(BundleInterface $bundle, $routePrefix)
    {
        $ns = explode("\\", strtolower($bundle->getNamespace()));
        $project = $ns[0];
        $bundle = str_replace('bundle', '', $ns[1]);

        return $project . '_' . $bundle . '.' . $routePrefix;
    }

    /**
     * List of translations used in generated crud
     */
    protected function getTranslations()
    {
        $data = array();
        $data['_name'] = $this->entity;

        foreach ($this->metadata->fieldMappings as $field => $metadata) {
            $data[$field] = ucfirst($field);
        }

        if (isset($data['id'])) {
            $data['id'] = 'ID';
        }

        return $data;
    }

    /**
     * List of global translations used in generated crud
     */
    protected function getGlobalTranslations()
    {
        return array(
            'edit' => 'Edit',
            'name' => 'Name',
            'actions' => 'Actions',
            'create_new' => 'Create new',
            'no_entries' => 'No entries',
            'general_information' => 'General information',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'back' => 'Back',
            'delete' => 'Delete',
            'show' => 'Show',
        );
    }

    /**
     * Generates translations messages dictionary file
     *
     */
    protected function generateDictionary()
    {
        $translations = $this->getTranslations();
        $translationsGlobal = $this->getGlobalTranslations();

        $name = strtolower(str_replace('\\', '_', $this->entity));
        $target = sprintf(
            '%s/Resources/translations/%s.messages.en.php',
            $this->bundle->getPath(),
            $name
        );

        $this->renderFile('crud/messages.php.twig', $target, array(
            'translation_prefix' => substr($this->translationPrefix, 0, -1),
            'translation_global_prefix' => substr($this->translationGlobalPrefix, 0, -1),
            'translations' => $translations,
            'translations_global' => $translationsGlobal,
            'entity' => $this->entity,
        ));
    }

    /**
     * Generates data fixtures for entity
     *
     */
    protected function generateDataFixture()
    {
        $name = strtolower(str_replace('\\', '_', $this->entity));
        $target = sprintf(
            '%s/DataFixtures/ORM/Load%sData.php',
            $this->bundle->getPath(),
            $this->entity
        );

        $data = array(
            'setName' => array(
                'value' => 'Labadiena',
                'slug' => 'slug',
            ),
            'setValue' => array(
                'value' => 'Labadiena',
                'slug' => 'slug',
            ),
        );

        $this->renderFile('crud/datafixtures/data.php.twig', $target, array(
            'entity' => $this->entity,
            'entity_prefix' => $name,
            'fixturedata' => $data,
        ));
    }

    /**
     * Generates the functional test class only.
     *
     */
    protected function generateServiceTestClass()
    {
        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $entityNamespace = implode('\\', $parts);

        $dir    = $this->bundle->getPath() .'/Tests/Service';
        $target = $dir .'/'. str_replace('\\', '/', $entityNamespace).'/'. $entityClass .'ServiceTest.php';

        $methods = $this->getServiceTestableMethods($this->actions);

        $this->renderFile('crud/tests/service.php.twig', $target, array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'bundle'            => $this->bundle->getName(),
            'entity_class'      => $entityClass,
            'namespace'         => $this->bundle->getNamespace(),
            'entity_namespace'  => $entityNamespace,
            'actions'           => $this->actions,
            'methods'           => $methods,
            'form_type_name'    => strtolower(str_replace('\\', '_', $this->bundle->getNamespace()).($parts ? '_' : '').implode('_', $parts).'_'.$entityClass.'Type'),
        ));
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

    protected function generateTestClass()
    {
        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $entityNamespace = implode('\\', $parts);
        $fields = $this->metadata->fieldMappings;
        unset($fields['id']);

        $dir    = $this->bundle->getPath() .'/Tests/Controller';
        $target = $dir .'/'. str_replace('\\', '/', $entityNamespace).'/'. $entityClass .'ControllerTest.php';

        $this->renderFile('crud/tests/test.php.twig', $target, array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'bundle'            => $this->bundle->getName(),
            'entity_class'      => $entityClass,
            'namespace'         => $this->bundle->getNamespace(),
            'entity_namespace'  => $entityNamespace,
            'actions'           => $this->actions,
            'form_type_name'    => strtolower(str_replace('\\', '_', $this->bundle->getNamespace()).($parts ? '_' : '').implode('_', $parts).'_'.$entityClass),
            'fields'            => $fields,
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
            'translation_prefix' => $this->translationPrefix,
            'translation_global_prefix' => $this->translationGlobalPrefix,
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
            'getters'            => $this->getGetters(),
            'actions'           => $this->actions,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'translation_prefix' => $this->translationPrefix,
            'translation_global_prefix' => $this->translationGlobalPrefix,
        ));
    }

    /**
     * Generates the new.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateNewView($dir)
    {
        $fields = $this->metadata->fieldMappings;
        unset($fields['id']);

        $this->renderFile('views/new.html.' . $this->viewFormat . '.twig', $dir.'/new.html.' . $this->viewFormat, array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'actions'           => $this->actions,
            'fields'            => $fields,
            'translation_prefix' => $this->translationPrefix,
            'translation_global_prefix' => $this->translationGlobalPrefix,
        ));
    }

    /**
     * Generates the edit.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateEditView($dir)
    {
        $fields = $this->metadata->fieldMappings;
        unset($fields['id']);

        $this->renderFile('views/edit.html.' . $this->viewFormat . '.twig', $dir.'/edit.html.' . $this->viewFormat, array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'actions'           => $this->actions,
            'fields'            => $fields,
            'translation_prefix' => $this->translationPrefix,
            'translation_global_prefix' => $this->translationGlobalPrefix,
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

        return $methods;
    }

    /**
     * Get service methods for unit testing
     *
     * @return array
     */
    protected function getServiceTestableMethods($actions)
    {
        $methods = array(
            'getNew',
            'get',
            'getAll',
            'create',
            'update',
            'delete',
            'save',
        );

        return $methods;
    }

    /**
     * Get getter methods for entity fields
     *
     * @return array
     */
    protected function getGetters()
    {
        $fields = $this->metadata->fieldMappings;
        $return = array();

        foreach ($fields as $field => $metadata) {
            $return[$field] = $this->camelCase('get_' . $field, '_');
        }

        return $return;
    }

    /**
     * Convert string to camel case which is splitted by $delimiter
     *
     * @var string $string
     * @var string $delimiter
     */
    protected function camelCase($string, $delimiter)
    {
        $exp = explode($delimiter, $string);
        $exp = array_map('ucfirst', $exp);

        return lcfirst(implode('', $exp));
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