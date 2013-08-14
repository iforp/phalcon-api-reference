<?php

namespace ApiDocs\Tasks;

use ApiDocs\Components\ApiGenerator,
	ApiDocs\Models;

/**
 * Class ApiDocs\Tasks\GenapiTask
 * CLI task for generating Phalcon API documentation.
 * It's available to scan source files on filesystem or on GitHub (using cURL)
 * Note: GitHub limit requests to 5000 per hour.
 * @cli-usage genapi -g -u <login:pass> -b <branch> [-d <dir>] [-o]
 * @cli-usage genapi [-d <dir>] [-o]
 *
 * @property-read \Phalcon\Db\AdapterInterface $db
 */
class GenapiTask extends Task
{
	protected $_sources = [];
	protected $_currentVersion;
	protected $_curl;


	/**
	 * @return bool|void
	 */
	public function beforeExecuteRoute()
	{
		$this->registerOptions();

		if(!parent::beforeExecuteRoute())
		{
			return false;
		}

		$this->_currentVersion = join('.', sscanf(\Phalcon\Version::get(), '%d.%d.%d'));
		if(!$this->dispatcher->getParam('overwrite') && $this->db->tableExists((new Models\Versions)->getSource()))
		{
			$hasRecords = (bool)Models\Versions::count([
				'conditions' => 'version = ?0',
				'bind'       => [$this->_currentVersion]
			]);
			if($hasRecords && !$this->confirm('Docs are already presents in the database. Do you want to overwrite it?'))
			{
				return false;
			}
		}
	}


	public function registerOptions()
	{
		$this
			->registerOption('github',    'g',  false,  false, 'Should use GitHub, otherwise use filesystem directory')
			->registerOption('branch',    'b',  false,  true,  'GitHub branch. It will use current Phalcon version if not set')
			->registerOption('user',      'u',  false,  true,  'GitHub user name or e-mail and password <user>:<pass>')
			->registerOption('overwrite', 'o',  false,  false, 'Should be set to overwrite existing data')
			->registerOption('dir',       'd',  false,  true,  'Path to filesystem directory or GitHub path to source files. This option overrides config value');
	}


	/**
	 * @return void
	 */
	public function mainAction()
	{
		$this->dispatcher->getParam('copy')
			? $this->copyAction()
			: $this->generateAction();
	}


	/**
	 * @return void
	 */
	public function generateAction()
	{
		$this->output('Checking your Phalcon version');

		if(!$sourceVersion = $this->_getSource('version.c'))
		{
			die('Sources in the specified path is not found');
		}

		$sourceVersion = ApiGenerator::getSourceVersion($sourceVersion);
		if($this->_currentVersion != $sourceVersion)
		{
			die("Your current Phalcon version is '$this->_currentVersion', but in the sources is '$sourceVersion'. Task stopped");
		}

		$this->output("Ok. Your current Phalcon version is '$this->_currentVersion'");

		if(!$this->db->tableExists((new Models\Versions)->getSource()))
		{
			$this->output('DB schema doesn\'t exists. Creating.');
			$this->_createSchema();
		}

		$tmpVers = 'tmp-'.substr(md5(microtime()), 0, 6);

		$version = new Models\Versions;
		$version->version    = $tmpVers;
		$version->changelog  = $this->_getSource('../CHANGELOG');

		if(!$version->save())
		{
			die(join("\n", $version->getMessages()));
		}

		$this->output('Generating API from sources');

		try
		{
			$list = (new \ReflectionExtension('phalcon'))->getClassNames();
			array_walk($list, [$this, '_generateClass'], $tmpVers);
		}
		catch(\Exception $e)
		{
			if($this->db->isUnderTransaction())
			{
				$this->db->rollback();
			}
			$version->delete();
			die($e->getMessage());
		}


		if(($oldVersion = Models\Versions::findFirst([
			'conditions' => 'version = ?0',
			'bind'       => [$sourceVersion]
		])) && !$oldVersion->delete())
		{
			$version->delete();
			die($oldVersion->getMessages());
		}

		if(!$this->db->update($version->getSource(), ['version'], [$sourceVersion], "version='$tmpVers'"))
		{
			$version->delete();
			die('Unsuccessful attempt');
		}

		die('Done');
	}



	/**
	 * @return bool
	 */
	protected function _createSchema()
	{
		if(!is_file($this->config->schemaPath) || !trim($schema = file_get_contents($this->config->schemaPath)))
		{
			die('DB schema file doesn\'t exists or is empty');
		}
		$schema = str_replace('{{prefix}}', $this->config->db->tblPrefix, $schema);
		return $this->db->execute($schema);
	}


	/**
	 * @param string $file
	 * @return bool|string
	 */
	protected function _getSource($file)
	{
		return $this->dispatcher->getParam('github')
			? $this->_getGitSource($file, $this->dispatcher->getParam('branch', null, $this->_currentVersion))
			: $this->_getFileSource($file);
	}


	/**
	 * @param string $file
	 * @param string $branch
	 * @return bool|string
	 * @throws \Exception
	 */
	protected function _getGitSource($file, $branch)
	{
		static $config;
		if($config === null)
		{
			$config = $this->config->genapi->github;
			$config->projectUrl = rtrim($this->dispatcher->getParam('dir', null, $config->projectUrl), '/');
			if($user = $this->dispatcher->getParam('user'))
				$config->curlOptions[CURLOPT_USERPWD] = $user;
		}

		$curl = $this->_getCurl((array)$config->curlOptions);
		$url  = substr($file, 0, 3) == '../'
			? dirname($config->projectUrl) . '/' . substr($file, 3) . "?ref=$branch"
			: "$config->projectUrl/$file?ref=$branch";

		curl_setopt($curl, CURLOPT_URL, $url);

		if(($result = curl_exec($curl)) === false)
		{
			throw new \Exception(curl_error($curl));
		}

		$data = json_decode($result);

		if(is_object($data) && isset($data->type) && $data->type == 'file')
		{
			switch($data->encoding)
			{
				case 'base64':
					return base64_decode($data->content);
					break;

				default:
					throw new \Exception("Unknown content encoding received from GitHub: \"$data->encoding\"");
					break;
			}
		}
		elseif(is_object($data) && $data->message)
		{
			if($data->message == 'Not Found')
			{
				return false;
			}
			throw new \Exception("Error message from GitHub: \"$data->message\"");
		}
		else
		{
			throw new \Exception('Unexpected data received from GitHub');
		}
	}


	/**
	 * @param string $file
	 * @return bool|string
	 */
	protected function _getFileSource($file)
	{
		$dir  = rtrim($this->dispatcher->getParam('dir', null, $this->config->genapi->filesystem->baseDir), '\/');
		$path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, "$dir/$file");

		if(!is_file($path))
		{
			return false;
		}

		return file_get_contents($path);
	}


	/**
	 * @param array $curlOptions
	 * @param bool $reinit
	 * @return resource
	 */
	protected function _getCurl(array $curlOptions=[], $reinit=false)
	{
		if($this->_curl && $reinit)
		{
			$this->_closeCurl();
		}

		if($this->_curl === null)
		{
			$this->_curl = curl_init();
			curl_setopt_array($this->_curl, $curlOptions);
		}

		return $this->_curl;
	}


	/**
	 *
	 */
	protected function _closeCurl()
	{
		curl_close($this->_getCurl());
		unset($this->_curl);
	}


	/**
	 * @param string $className
	 * @param int $i
	 * @param string $version
	 * @return Models\Classes
	 * @throws \Exception
	 */
	protected function _generateClass($className, $i, $version)
	{
		$this->output("- $className");

		$file = ApiGenerator::getPathByClassName($className);

		if(($source = $this->_getSource($file)) === false)
		{
			$this->output("Unable to get $className sources");
		}

		$api = new ApiGenerator($className, $source);
		$api->generate();

		$constants  = [];
		$properties = [];
		$methods    = [];

		foreach($api->constants as $c)
		{
			$constant = new Models\Constants;
			$constant->assign((array)$c);
			$constants[] = $constant;
		}

		foreach($api->properties as $p)
		{
			$property = new Models\Properties;
			$property->assign((array)$p);
			$properties[] = $property;
		}

		foreach($api->methods as $m)
		{
			$method = new Models\Methods;
			$method->assign((array)$m);
			$methods[] = $method;
			$arguments = [];

			foreach($m->arguments as $arg)
			{
				$argument = new Models\Arguments;
				$argument->assign((array)$arg);
				$arguments[] = $argument;
			}

			$method->arguments = $arguments;
		}

		$class = new Models\Classes;
		$class->assign((array)$api->class);
		$class->version    = $version;
		$class->constants  = $constants;
		$class->properties = $properties;
		$class->methods    = $methods;
		$class->implements = $class->implements ? join(',', $class->implements) : null;

		$this->db->begin();
		if(!$class->save())
		{
			throw new \Exception(join("\n", $class->getMessages()));
		}
		$this->db->commit();

		return $class;
	}
}