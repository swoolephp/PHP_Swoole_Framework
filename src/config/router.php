<?php

use App\Controllers\HomeController;
use App\Controllers\ApiController;

/*
// News routes with dynamic category and ID
$router->add('GET', '/news/{category}/{id}', NewsController::class, 'categoryDetails');

// Using :any for any non-slash characters
$router->add('GET', '/news/:any', NewsController::class, 'viewNews'); // Any string except slash for news titles

// Using full regex for more complex patterns
$router->add('GET', '/news/:any/:num', NewsController::class, 'newsDetails'); // Combination of string and numeric

//Using regex
$router->add('GET', '/news/([a-zA-Z0-9]+)/', NewsController::class, 'blog');

$router->add('GET', '/custom/{id:[0-9]{3,6}}', NewsController::class, 'customMethod');
*/

return [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/home' => [HomeController::class, 'index'],
        '/home/{methodName:any}' => [HomeController::class, 'index'],
    ],
    'POST' => [
        '/api' => [ApiController::class, 'create']
    ],
    'PUT' => [
        '/api' => [ApiController::class, 'update']
    ],
    'DELETE' => [
        '/api' => [ApiController::class, 'delete']
    ]
];
