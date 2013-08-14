<?php

define('APP_ROOT', __DIR__);

try
{
	require 'loader.php';
	require 'di.php';

	$app = new Phalcon\Mvc\Application($di);
	echo $app->handle()->getContent();
}
catch(Exception $e)
{
	echo $e->getMessage();
	//pd($e);
}

