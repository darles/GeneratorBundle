GeneratorBundle
===============

Extension for SensioGeneratorBundle which provides ability to override skeleton templates by putting it to app/Resources/EstinaGenerator.

''Only CRUD generator supported so far.''

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
        "estina/generator-bundle" : "*"
    }
    ...

Put the templates you want to override to app/Resources/EstinaGeneratorBundle/crud/<folder>/<template>

Run command
    app/console doctrine:generate:crud