<?php

$router = new Phalcon\Mvc\Router(false);
$router->removeExtraSlashes(true);

$router->setDefaults([
	'controller' => 'apireference',
	'action'     => 'index',
]);

$router->add('/error/404',
[
	'controller' => 'error',
	'action'     => '_404',
])->setName('404');

$router->notFound($router->getRouteByName('404')->getPaths());


// this only need to get URL to class reference by route name
$router->add('/{language:[A-Za-z]{2,2}}/{version:((\d+\.\d+\.\d+)|latest)}/{class:(?i)phalcon(/[\w/]+)?}', [
	'controller' => 'apireference',
	'action'     => 'showClass',
])->setName('showClass');


// this only need to get URL by route name
$router->add('/{language:[A-Za-z]{2,2}}/{version:((\d+\.\d+\.\d+)|latest)}/{type:(classes|namespaces|interfaces|changelog)}', [
	'controller' => 'apireference',
	'action'     => 'index',
])->setName('showSummary');


$router->add('/?([A-Za-z]{2,2})?(/((\d+\.\d+\.\d+)|latest))?(/(classes|namespaces|interfaces|changelog))?', [
	'controller' => 'apireference',
	'action'     => 'index',
	'language'   => 1,
	'version'    => 3,
	'summary'    => 6,
])->convert('language', function($param){return $param === 1 || !$param ? null : strtolower($param);})
  ->convert('version', function($param){return $param === 3 || !$param ? null : $param;})
  ->convert('summary', function($param){return $param === 6 || !$param ? 'classes' : $param;});


$router->add('(/([A-Za-z]{2,2}))?(/((\d+\.\d+\.\d+)|latest))?/((?i)phalcon(/[\w/]+)?)', [
	'controller' => 'apireference',
	'action'     => 'showClass',
	'language'   => 2,
	'version'    => 4,
	'class'      => 6
])->convert('language',function($param){return $param === 2 || !$param ? null : strtolower($param);})
  ->convert('version', function($param){return $param === 4 || !$param ? null : $param;})
  ->convert('class',   function($param){return $param === 6 || !$param ? null : str_replace('/', '\\', strtolower($param));});


return $router;