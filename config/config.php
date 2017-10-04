<?php

return [
    // debug mode?
    'debug' => false,

    // Doc specific settings; for each product documentation
    'doc' => [
        'index' => env('DOC_WEAVER_DOC_INDEX', 'documentation'),
    ],

    // versions
    'versions' => [
        // whether to allow worded versions as default
        'allow_worded_default' => false,
    ],

    // route options
    'route' => [
        // prefix that should be used for routes
        'prefix' => env('DOC_WEAVER_ROUTE_PREFIX', 'docs'),
        
        // bindings for routes
        'bindings' => [
            'middleware' => 'web',
        ],
        
        // route names
        'names' => [
            'index' =>  env('DOC_WEAVER_ROUTE_NAME_INDEX', 'docs'),
            'product_index' =>  env('DOC_WEAVER_ROUTE_NAME_PRODUCT_INDEX', 'docs.product'),
            'product_page' =>  env('DOC_WEAVER_ROUTE_NAME_PRODUCT_PAGE', 'docs.show'),
        ]
    ],

    // storage options
    'storage' => [
        // where docs are stored
        'dir' => env('DOC_WEAVER_DIR', 'resources/docs'),
    ],

    // view options
    'view' => [
        // master layout template
        'master_template' => env('DOC_WEAVER_VIEW_MASTER_TEMPLATE', 'app'),
        
        // master content section
        'master_section' => env('DOC_WEAVER_VIEW_MASTER_SECTION', 'content'),
        
        // stack for injecting styles in master template
        'style_stack' => 'styles',
        
        // stack for injecting scripts in master template
        'script_stack' => 'scripts',

        // Documentation index title
        'docs_title' => false,

        // configurable accents
        'accents' => [
            // hide product line accent
            'product_line' => true,
        ],
    ],

];
