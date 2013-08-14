<?php

namespace ApiDocs\Tasks;

/**
 * Class Task
 * @property \Phalcon\CLI\Dispatcher $dispatcher
 */
abstract class Task extends \Phalcon\CLI\Task
{
	use \ApiDocs\Traits\CLI;

	/**
	 * @var array
	 */
	protected $_options = [
		'help' => [
			'name'        => 'help',
			'short'       => 'h',
			'long'        => 'help',
			'required'    => false,
			'hasValue'    => false,
			'description' => 'This help',
		]
	];

	/**
	 * @var array
	 */
	protected $_availableOptions = [
		'-'  => ['h'],    // short option keys
		'--' => ['help'], // long option keys
	];


	protected function registerOption($variableName, $shortKey, $required=false, $hasValue=false, $description='')
	{
		if(in_array($variableName, $this->_availableOptions['--']))
		{
			throw new \Exception("Option key '--$variableName' is already registered");
		}
		elseif(in_array($shortKey, $this->_availableOptions['-']))
		{
			throw new \Exception("Option key '-$shortKey' is already registered");
		}

		$this->_options[$variableName] = [
			'name'        => $variableName,
			'short'       => $shortKey,
			'long'        => $variableName,
			'required'    => $required,
			'hasValue'    => $hasValue,
			'description' => $description
		];

		$this->_availableOptions['--'][] = $variableName;
		if($shortKey)
			$this->_availableOptions['-'][] = $shortKey;

		return $this;
	}


	public function getOptionDetails($key=null, $isShort=true)
	{
		if(!$key)
		{
			return $this->_options;
		}

		$details = array_filter($this->_options, function($option) use($key, $isShort)
		{
			return $key == $option[$isShort ? 'short' : 'long'];
		});

		return is_array($details) ? current($details) : false;
	}


	public function beforeExecuteRoute()
	{
		$params = $this->dispatcher->getParam('params');

		try
		{
			while(list($i, $param) = each($params))
			{
				if(
					preg_match('/(-{1,2})([a-zA-Z]\w*)(=(.*))?/', $param, $matches)
					&& $matches[2] != 'params'
					&& ($option = $this->getOptionDetails($matches[2], $matches[1]=='-'))
				){
					if($option['name'] == 'help')
					{
						$this->dispatcher->forward([
							'task'   => 'help',
							'params' => ['params'=>[$this->dispatcher->getTaskName()]]
						]);
						return false;
					}

					if($option['hasValue'] && !isset($matches[4]))
					{
						if(isset($params[$i+1]) && substr($params[$i+1], 0, 1) != '-')
						{
							$matches[4] = $params[$i+1];
							unset($params[$i+1]);
						}
						else
						{
							throw new \Exception("Option \"$matches[1]$matches[2]\" should have a value");
						}
					}
					elseif(!$option['hasValue'] && isset($matches[4]))
					{
						throw new \Exception("Flag \"$matches[2]\" shouldn't have any value. Given: $matches[4]");
					}

					$this->dispatcher->setParam($option['name'], isset($matches[4]) ? $matches[4] : true);
				}
				else
				{
					throw new \Exception("Unknown flag \"$param\"");
				}
			};

			foreach($this->_options as $option)
			{
				if($option['required'] && $this->dispatcher->getParam($option['name']) === null)
				{
					throw new \Exception('Option "-'.$option['short'].'" must be set. See help: ' . $this->dispatcher->getTaskName() . ' -h');
				}
			}
		}
		catch(\Exception $e)
		{
			die($e->getMessage());
		}

		return true;
	}
}