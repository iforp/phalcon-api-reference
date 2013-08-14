<?php

return array
(
	'baseUri'       => '/api/',
	'defaultLang'   => 'en',
	'baseGithubUrl' => 'https://github.com/phalcon/cphalcon/tree',
	'fileGithubUrl' => 'https://github.com/phalcon/cphalcon/blob/%s/ext/%s',
	'schemaPath'    => APP_ROOT . '/data/schema.sql',
	'voltOptions'   => [
		'compiledPath'      => APP_ROOT . '/../cache/',
		'compiledSeparator' => '_'
	],
	'cache' => [
		'enabled'  => true,
		'lifetime' => 86400,
		'dir'      => APP_ROOT . '/../cache/',
	],

	'db' => [
		'host'         => '127.0.0.1',
		'username'     => 'root',
		'password'     => '',
		'dbname'       => 'phalcon_api',
		'tblPrefix'    => 'apidocs_',
	],

	'genapi' => [
		'filesystem' => [
			'baseDir' => '/phalcon-ext',
		],

		'github' => [
			'projectUrl'  => 'https://api.github.com/repos/phalcon/cphalcon/contents/ext',
			'curlOptions' => [
				CURLOPT_USERAGENT      => 'custom php bot',
				CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
				CURLOPT_USERPWD        => 'agent-j:***',
				//CURLOPT_PROXY          => '0.0.0.0:0000',
				CURLOPT_HEADER         => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_RETURNTRANSFER => true,
			],
		],
	],
);
