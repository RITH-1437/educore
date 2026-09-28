<?php

/*
|--------------------------------------------------------------------------
| Frontend Connection
|--------------------------------------------------------------------------
|
| Single source of truth for how the back-end connects to (and advertises)
| the front-end. Values come from the root `.env` and are read by:
|
|   - the Inertia shared props  (frontend/src/app.js — Vue runtime)
|   - the Vite dev server       (frontend/vite.config.js — HMR origin)
|   - absolute URLs / redirects (blade, controllers)
|
| Same-origin deployments (nginx -> Laravel/Inertia -> Vue) need nothing
| extra; FRONTEND_URL / VITE_DEV_SERVER_URL only matter when the SPA or the
| Vite dev server live on a different origin than the Laravel app.
*/

$url = rtrim((string) env('FRONTEND_URL', env('APP_URL', 'http://localhost')), '/');

return [

    /*
    | Public origin of the web front-end (the page users open in the browser).
    */
    'url' => $url,

    /*
    | Vite dev server used for HMR during development. Laravel's `@vite`
    | normally reads the auto-generated `public/hot` file; this is the
    | explicit fallback origin (defaults to the standard Vite port).
    */
    'dev_server_url' => rtrim((string) env('VITE_DEV_SERVER_URL', 'http://localhost:5173'), '/'),

    /*
    | Base origin where built assets (the Vite bundle) are served from.
    | Same as `url` unless assets are on a CDN.
    */
    'assets_url' => rtrim((string) env('FRONTEND_ASSETS_URL', $url), '/'),

    /*
    | REST API prefix. Must match routes/api.php (mounted at `/api`).
    */
    'api_prefix' => 'api',

    /*
    | Absolute base URL for API calls from the Vue client.
    */
    'api_url' => rtrim((string) env('FRONTEND_API_URL', $url.'/'.'api'), '/'),
];
