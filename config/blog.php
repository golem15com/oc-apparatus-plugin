<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Blog URL Validation Middleware
    |--------------------------------------------------------------------------
    |
    | This middleware validates Winter.Blog URLs to ensure proper SEO handling.
    | - Returns 404 for non-existing categories
    | - Redirects posts with incorrect category URLs to correct URLs (301)
    |
    */

    'url_validation' => [
        /*
        | Enable or disable the middleware
        | Set to false to completely disable URL validation
        */
        'enabled' => env('BLOG_URL_VALIDATION_ENABLED', true),

        /*
        | Blog route base paths (without leading/trailing slashes)
        | Comma-separated string or array
        | Examples: "news", "blog", "news,articles"
        */
        'routes' => env('BLOG_URL_VALIDATION_ROUTES', 'blog'),

        /*
        | HTTP status codes
        */
        'status_codes' => [
            'not_found' => 404,
            'permanent_redirect' => 301,
        ],
    ],
];
