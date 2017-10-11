<?php

if (! (   $_SERVER['REQUEST_URI'] === '/'
       || $_SERVER['REQUEST_URI'] === '/en'
       || $_SERVER['REQUEST_URI'] === '/en/'
       || $_SERVER['REQUEST_URI'] === '/en/mac'
       || $_SERVER['REQUEST_URI'] === '/en/mac/'
       || $_SERVER['REQUEST_URI'] === '/en/administration'
       || $_SERVER['REQUEST_URI'] === '/en/administration/'
      )
) {
    header('Location: /maintenance.html', true, 307);
    die();
}

use Symfony\Component\HttpFoundation\Request;

date_default_timezone_set('UTC');

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../app/autoload.php';
include_once __DIR__.'/../var/bootstrap.php.cache';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
