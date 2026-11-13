<?php

/*
 * Fallback links for the site. Admin → Settings → General overrides them.
 * Page content lives in the database and is edited with the page builder.
 */
return [

    'links' => [
        'github' => env('SITE_GITHUB_URL', 'https://github.com/Modrix-site/Modrix-Commerce'),
        'marketplace' => env('SITE_MARKETPLACE_URL', '#marketplace'),
    ],

];
