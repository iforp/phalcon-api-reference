<?php

$loader = new Phalcon\Loader;
$loader->registerNamespaces([
	'ApiDocs' => APP_ROOT,
]);
$loader->register();