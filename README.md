GeneratorBundle
===============

Extension for SensioGeneratorBundle which provides ability to override skeleton templates by putting it to app/Resources/EstinaGenerator.  
Also introduces **Service layer** to generated CRUDs.

**Only CRUD and Bundle generators override supported so far.**  

**CRUD generator supports only YAML config files**

Usage
=====

Install it via Composer.

    ...
    "repositories": [{
        "type": "vcs",
        "url": "http://github.com/Estina/GeneratorBundle"
    }]
    ...
    "require": {
        "estina/generator-bundle" : "1.*"
    }
    ...

Put the templates you want to override to app/Resources/EstinaGeneratorBundle/crud/FOLDER/TEMPLATE_NAME

To generate CRUD simply run command

    app/console doctrine:generate:crud

or if you want to generate bundle:

    app/console generate:bundle


Service layer
=============


With version v1.0.2 generator has one main flaw: does not support smart <Bundle>Resources/config/services.yml appendage when generating CRUD.  
So after generating CRUD check if your service config

    <Bundle>Resources/config/services.yml

imports entity service resources corectly. Example how imports section should look like:

    imports:
        - { resource: services/car.yml }
        - { resource: services/door.yml }
        - { resource: services/truck.yml }
        - { resource: services/owner.yml }

