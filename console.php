<?php
/**
 * Prints human-readable information about a variable and terminate the current script.
 * Used for debugging purposes.
 * @param string $str
 * @param bool $is_dump
 */
function pd($str='this is print-die function', $is_dump=false)
{
	$backTrace = debug_backtrace();
	echo "{$backTrace[0]['file']} ({$backTrace[0]['line']})" . PHP_EOL.PHP_EOL;
	$is_dump || !$str || is_bool($str) || is_numeric($str)
		? var_dump($str)
		: print_r($str);
	die(PHP_EOL.PHP_EOL);
}

error_reporting(E_ALL | E_NOTICE);


if(!extension_loaded('phalcon'))
{
	throw new Exception("Phalcon extension is required");
}

$params = $argv;
$file   = array_shift($argv);
$task   = array_shift($argv);

define('APP_ROOT', __DIR__.'/app');


$loader = new Phalcon\Loader;
$loader->registerNamespaces([
	'ApiDocs' => APP_ROOT,
]);
$loader->register();


$di = new Phalcon\DI\FactoryDefault\CLI;
$di->setShared('dispatcher', function() use ($di)
{
	$dispatcher = new Phalcon\CLI\Dispatcher;
	$dispatcher->setDI($di);
	$dispatcher->setDefaultNamespace('ApiDocs\Tasks');
	return $dispatcher;
});

$di->setShared('modelsManager', function()
{
	return new Phalcon\Mvc\Model\Manager;
});


$di->setShared('db', function() use($di)
{
	$connection = new Phalcon\Db\Adapter\Pdo\Mysql((array)$di->get('config')->db);
	return $connection;
});

$di->setShared('config', function()
{
	return new Phalcon\Config(require APP_ROOT . '/config/config.php');
});

$console = new Phalcon\CLI\Console;
$console->setDI($di);
$console->handle([
	'task'   => $task ?: 'help',
	'action' => 'main',
	'params' => $argv
]);