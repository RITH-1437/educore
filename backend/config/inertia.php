<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    */

    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | The frontend pages reside in frontend/src/pages in the monorepo, which is
    | mounted into resources/js in Docker. In environments where backend is run
    | outside Docker (e.g. CI), base_path('../frontend/src/pages') provides the
    | path so Inertia page component assertions find the Vue components.
    |
    */

    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => array_values(array_filter([
            resource_path('js/pages'),
            resource_path('js/Pages'),
            is_dir(base_path('../frontend/src/pages')) ? base_path('../frontend/src/pages') : null,
        ])),
        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    */

    'testing' => [
        'ensure_pages_exist' => true,
    ],

];
