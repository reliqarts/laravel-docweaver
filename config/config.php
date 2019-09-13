<?php

return [
    // debug mode?
    'debug' => false,

    // cache
    'cache' => [
        'key' => 'docweaver.docs',
    ],

    // doc specific settings
    'doc' => [
        'index' => env('DOCWEAVER_DOC_INDEX', 'documentation'),
    ],

    // versions
    'versions' => [
        // whether to allow worded versions as default
        'allow_worded_default' => false,
    ],

    // route options
    'route' => [
        // prefix that should be used for routes
        'prefix' => env('DOCWEAVER_ROUTE_PREFIX', 'docs'),

        // bindings for routes
        'bindings' => [
            'middleware' => 'web',
        ],

        // route names
        'names' => [
            'index' => env('DOCWEAVER_ROUTE_NAME_INDEX', 'docs'),
            'product_index' => env('DOCWEAVER_ROUTE_NAME_PRODUCT_INDEX', 'docs.product'),
            'product_page' => env('DOCWEAVER_ROUTE_NAME_PRODUCT_PAGE', 'docs.show'),
        ],
    ],

    // storage options
    'storage' => [
        // where docs are stored
        'dir' => env('DOCWEAVER_DIR', 'resources/docs'),
    ],

    // view options
    'view' => [
        // master layout template
        'master_template' => env('DOCWEAVER_VIEW_MASTER_TEMPLATE', 'app'),

        // master content section
        'master_section' => env('DOCWEAVER_VIEW_MASTER_SECTION', 'content'),

        // stack for injecting styles in master template
        'style_stack' => 'styles',

        // stack for injecting scripts in master template
        'script_stack' => 'scripts',

        // Documentation index title
        'docs_title' => 'Docs',

        // Documentation page intro paragraph
        'docs_intro' => 'Please consult the documentation provided below for help '
            . 'with setting up and using our commercial/open-source products.',

        // configurable accents
        'accents' => [
            // hide product line accent
            'product_line' => true,
        ],
    ],
];
