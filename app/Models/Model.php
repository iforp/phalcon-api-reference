<?php

namespace ApiDocs\Models;


class Model extends \Phalcon\Mvc\Model
{
	protected $_validation;

	public function getSource()
	{
		static $className;
		static $prefix;

		if($className === null)
		{
			$className = explode('\\', strtolower(get_class($this)));
			$className = array_pop($className);
		}

		if($prefix === null)
		{
			$prefix = $this->getDI()->get('config')->db->tblPrefix;
		}

		return $prefix . $className;
	}


	public function validation()
	{
		if(!empty($this->_validation))
		{
			foreach($this->_validation as $field => $rules)
			{
				if(is_string($rules))
				{
					$rules = [$rules => []];
				}

				foreach($rules as $validator => $params)
				{
					if(is_numeric($validator))
					{
						$validator = $params;
						$params    = [];
					}
					if(substr($validator, 0, 3) === 'Ph\\')
					{
						$validator = '\Phalcon\Mvc\Model\Validator' . substr($validator, 2);
					}
					$params['field'] = $field;
					$this->validate(new $validator($params));
				}
			}
		}

		return !$this->validationHasFailed();
	}


	public function beforeValidation()
	{
		$metaData = $this->getModelsMetaData();
		$notNull  = array_flip($metaData->getNotNullAttributes($this));
		$numeric  = array_keys($metaData->getDataTypesNumeric($this));
		$pk       = $metaData->getPrimaryKeyAttributes($this);

		foreach(array_diff($numeric, $pk) as $attr)
		{
			if(is_bool($this->$attr) && isset($notNull[$attr]))
			{
				$this->$attr = (int)$this->$attr;
			}
		}
	}
}