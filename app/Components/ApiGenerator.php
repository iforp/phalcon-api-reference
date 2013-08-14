<?php

namespace ApiDocs\Components;


/**
 * Class ApiGenerator
 * @package ApiDocs\Components
 * @property-read string $source;
 * @property-read string $name;
 * @property-read \stdClass $class;
 * @property-read \stdClass[] $constants;
 * @property-read \stdClass[] $properties;
 * @property-read \stdClass[] $methods;
 */
class ApiGenerator extends \Phalcon\Mvc\User\Component
{
	protected $_source;
	protected $_name;
	protected $_class;
	protected $_constants;
	protected $_properties;
	protected $_methods;

	/**
	 * @param string $className Class name
	 * @param string $source Source codes
	 */
	public function __construct($className, $source)
	{
		$this->_name   = $className;
		$this->_source = $source;
	}


	/**
	 * @param string $varName
	 */
	public function __get($varName)
	{
		$varName = '_'.$varName;
		if(isset($this->$varName))
		{
			return $this->$varName;
		}
	}


	/**
	 * @param $name
	 * @return string
	 */
	public static function getPathByClassName($name)
	{
		return str_replace('\\', '/', preg_replace('/^phalcon\\\/', '', strtolower($name))) . '.c';
	}


	/**
	 * @param $sources
	 * @return string
	 */
	public static function getSourceVersion($sources)
	{
		$started = false;
		$version = [];

		foreach(explode("\n", $sources) as $line)
		{
			if(!$started && false !== strpos($line, 'PHP_METHOD(Phalcon_Version, _getVersion)'))
			{
				$started = true;
			}
			elseif($started && preg_match('/add_next_index_long\(version, (\d+)\)/', $line, $matches))
			{
				$version[] = $matches[1];
			}

			if(count($version) == 3)
			{
				break;
			}
		}

		return join('.', $version);
	}


	/**
	 * @param $source
	 * @return array|bool
	 */
	public static function findClassName($source)
	{
		if(!preg_match('/^\s*PHALCON_INIT_CLASS\((\w+)\)/m', $source, $matches))
		{
			return false;
		}
		$parts     = explode('_', $matches[1]);
		$name      = array_pop($parts);
		$namespace = join('\\', $parts);
		return [$namespace, $name];
	}


	/**
	 *
	 */
	public function generate()
	{
		$this->reverseEngineer();
		$this->parseSource();
	}


	/**
	 * @return $this
	 */
	protected function reverseEngineer()
	{
		$reflection = new \ReflectionClass($this->_name);
		$this->reflectClass($reflection)
			->reflectConstants($reflection->getConstants())
			->reflectProperties($reflection->getProperties())
			->reflectMethods($reflection->getMethods());

		return $this;
	}


	/**
	 * @param \ReflectionClass $reflectionClass
	 * @return $this
	 */
	protected function reflectClass(\ReflectionClass $reflectionClass)
	{
		$this->_class = new \stdClass;
		$this->_class->name         = $reflectionClass->getShortName();
		$this->_class->namespace    = $reflectionClass->getNamespaceName();
		$this->_class->is_abstract  = $reflectionClass->isAbstract();
		$this->_class->is_final     = $reflectionClass->isFinal();
		$this->_class->is_interface = $reflectionClass->isInterface();
		$this->_class->file         = static::getPathByClassName($this->_name);
		$this->_class->extends      = $reflectionClass->getParentClass();
		$this->_class->extends      = $this->_class->extends ? $this->_class->extends->getName() : null;
		$this->_class->implements   = $reflectionClass->getInterfaceNames();

		return $this;
	}


	/**
	 * @param array $constants
	 * @return $this
	 */
	protected function reflectConstants(array $constants)
	{
		$this->_constants = [];

		foreach($constants as $name => $value)
		{
			$const = new \stdClass;
			$const->name  = $name;
			$const->type  = gettype($value);

			if(is_bool($value))
				$const->value = $value ? 'true' : 'false';
			elseif(is_numeric($value) || is_string($value))
				$const->value = (string)$value;
			elseif(is_null($value))
				$const->value = 'null';
			else
				$const->value = (string)$value;

			$this->_constants[$const->name] = $const;
		}

		return $this;
	}


	/**
	 * @param array $reflectionProperties
	 * @return $this
	 */
	protected function reflectProperties(array $reflectionProperties)
	{
		$this->_properties = [];

		foreach($reflectionProperties as $reflProperty)
		{
			$property = new \stdClass;
			$property->name       = $reflProperty->getName();
			$property->is_static  = $reflProperty->isStatic();
			$property->defined_by = $reflProperty->getDeclaringClass()->getName();

			if($reflProperty->isPrivate())       $property->visibility = 'private';
			elseif($reflProperty->isProtected()) $property->visibility = 'protected';
			elseif($reflProperty->isPublic())    $property->visibility = 'public';

			$this->_properties[$property->name] = $property;
		}

		return $this;
	}


	/**
	 * @param array $arguments
	 * @param \stdClass $method
	 */
	protected function reflectArguments(array $arguments, \stdClass &$method)
	{
		$method->arguments = [];

		foreach($arguments as $reflArg)
		{
			$arg = new \stdClass;
			$arg->name        = $reflArg->getName();
			$arg->is_optional = $reflArg->isOptional();
			$arg->ordering    = $reflArg->getPosition();

			// getDefaultValue() doesn't works with internal functions
			// $arg->default_value = $reflArg->getDefaultValue(),

			$method->arguments[strtolower($arg->name)] = $arg;
		}
	}


	/**
	 * @param array $reflectionMethods
	 * @return $this
	 */
	protected function reflectMethods(array $reflectionMethods)
	{
		$this->_methods = [];

		foreach($reflectionMethods as $reflMethod)
		{
			$method = new \stdClass;
			$method->name        = $reflMethod->getName();
			$method->is_static   = $reflMethod->isStatic();
			$method->is_final    = $reflMethod->isFinal();
			$method->is_abstract = $reflMethod->isAbstract();
			$method->defined_by  = $reflMethod->getDeclaringClass()->getName();

			if($reflMethod->isPrivate())       $method->visibility = 'private';
			elseif($reflMethod->isProtected()) $method->visibility = 'protected';
			elseif($reflMethod->isPublic())    $method->visibility = 'public';

			$this->reflectArguments($reflMethod->getParameters(), $method);

			$this->_methods[strtolower($method->name)] = $method;
		}

		return $this;
	}


	/**
	 * @return $this
	 */
	protected function parseSource()
	{
		$classDoc      = null;
		$openComment   = false;
		$parseNextLine = false;
		$comment       = [];

		$code = explode("\n", $this->_source);

		foreach($code as $i => $line)
		{
			if(trim($line) == '/**')
			{
				$comment       = [];
				$openComment   = true;
				$parseNextLine = false;
			}

			if($openComment)
			{
				$comment[] = $line;
				if(trim($line) == '*/')
				{
					// If it was the first comment in file
					if($classDoc === null)
					{
						$classDoc = $this->parseComment($comment);
						$comment  = [];

						// Sometimes there is no comment for the class, and we may mistakenly catch an initializer comment
						if(isset($code[$i+1]) && preg_match('/^\s*PHALCON_INIT_CLASS\(([a-zA-Z0-9\_]+)\)/', $code[$i+1], $matches))
						{
							$classDoc = $this->parseComment([]);
						}

						$this->addClassAttributes($this->_class, $classDoc);
					}

					$openComment   = false;
					$parseNextLine = true;
					continue;
				}
			}

			if($parseNextLine)
			{
				if(!trim($line))
				{
					continue;
				}

				$details = $this->parseComment($comment);
				$details['line'] = $i+1;

				if(preg_match('/^\s*PHALCON_INIT_CLASS\(([a-zA-Z0-9\_]+)\)/', $line, $matches))
				{
					$this->_class->initializer_line = $i+1;
				}
				elseif(preg_match('/^\s*(PHP_METHOD|PHALCON_DOC_METHOD)\(([a-zA-Z0-9\_]+), (.*)\)/', $line, $matches))
				{
					$name = strtolower($matches[3]);
					if(isset($this->_methods[$name]))
					{
						$this->addMethodAttributes($this->_methods[$name], $details);
					}
				}
				elseif(preg_match('/^\s*zend_declare_property_\w+\(.*SL\("(\w+)"\).*\);/', $line, $matches))
				{
					$name = $matches[1];
					if(isset($this->_properties[$name]))
					{
						$this->addPropertyAttributes($this->_properties[$name], $details);
					}
				}
				elseif(preg_match('/^\s*zend_declare_class_constant_\w+\(.*SL\("(\w+)"\).*\);/', $line, $matches))
				{
					$name = $matches[1];
					if(isset($this->_constants[$name]))
					{
						$this->addConstantAttributes($this->_constants[$name], $details);
					}
				}

				$parseNextLine = false;
			}
		}

		return $this;
	}


	/**
	 * @param \stdClass $class
	 * @param array $details
	 * @return \stdClass
	 */
	protected function addClassAttributes(\stdClass $class, array $details)
	{
		// Cut the first line of the class description if it is the name of the class
		if(isset($details['text']) && strpos($details['text'], "$this->_name\n") === 0)
		{
			$details['text'] = trim(substr($details['text'], strlen("$this->_name\n")));
		}

		$class->docs    = $details['text'];
		$class->example = $details['code'];

		return $class;
	}


	/**
	 * @param \stdClass $constant
	 * @param array $details
	 * @return \stdClass
	 */
	protected function addConstantAttributes(\stdClass $constant, array $details)
	{
		$constant->line = $details['line'];
		$constant->docs = $details['text'];
		return $constant;
	}


	/**
	 * @param \stdClass $property
	 * @param array $details
	 * @return \stdClass
	 */
	protected function addPropertyAttributes(\stdClass $property, array $details)
	{
		$property->line = $details['line'];
		$property->docs = $details['text'];

		if($tag = @array_pop($details['tags']['property']))
		{
			if(isset($tag->type))
				$property->type = $tag->type;

			if(!isset($tag->varName))
				$tag->varName = $property->name;

			// @todo Don't know, should I use tag (@property-read/@property-write) or check methods presence
			/* Let it be methods presence
			if($property->visibility != 'public')
			{
				$property->getter = 'get' . ucfirst($tag->varName);
				$property->setter = 'set' . ucfirst($tag->varName);
			}

			if(isset($tag->access))
			{
				$property->access = $tag->access;
				if($tag->access == 'read')  unset($property->setter);
				if($tag->access == 'write') unset($property->getter);
			}
			*/

			if(isset($this->_methods['get' . strtolower($tag->varName)]))
				$property->getter = 'get' . ucfirst($tag->varName);

			if(isset($this->_methods['set' . strtolower($tag->varName)]))
				$property->setter = 'set' . ucfirst($tag->varName);
		}

		return $property;
	}


	/**
	 * @param \stdClass $method
	 * @param array $details
	 * @return \stdClass
	 */
	protected function addMethodAttributes(\stdClass $method, array $details)
	{
		if($method->name == '__construct')
			$method->returns = 'void';
		else
		{
			if(isset($details['tags']['return']->type))
				$method->returns = $details['tags']['return']->type;
			if(isset($details['tags']['return']->description))
				$method->returns_docs = $details['tags']['return']->description;
		}

		$method->line    = $details['line'];
		$method->docs    = $details['text'];
		$method->example = $details['code'];

		if($method->name == '__construct' && ($method->docs == $this->name || !$method->docs))
		{
			$method->docs = 'Constructor.';
		}

		if(isset($details['tags']['param'])) foreach($details['tags']['param'] as $param)
		{
			if(!$arg = @$method->arguments[strtolower($param->varName)])
			{
				continue;
			}

			if(isset($param->type))
				$arg->type = $param->type;

			if(isset($param->description))
				$arg->description = $param->description;
		}

		return $method;
	}


	/**
	 * @param $comment
	 * @return array
	 */
	protected function parseComment($comment)
	{
		if(is_string($comment))
		{
			$comment = explode("\n", $comment);
		}

		$openCode = false;
		$result   = [
			'text' => '',
			'code' => '',
			'tags' => [],
		];

		foreach($comment as $line)
		{
			if(in_array(trim($line), ['/**', '*/']))
			{
				continue;
			}

			$line = preg_replace('/^\s*\*\s?/', '', $line);

			if(trim($line) == '<code>')
			{
				$openCode = true;
				continue;
			}
			elseif(trim($line) == '</code>')
			{
				$openCode = false;
				continue;
			}

			if($openCode)
			{
				$result['code'] .= "$line\n";
			}
			elseif(preg_match('/\s*@([\w-]+)\s+(.*)/', $line, $matches))
			{
				$tag = $this->parsePhpdocTag($matches[1], $matches[2]);
				$tag->name == 'return'
					? $result['tags'][$tag->name]   = $tag
					: $result['tags'][$tag->name][] = $tag;
			}
			else
			{
				$result['text'] .= "$line\n";
			}
		}

		//$result['text'] = preg_replace('/\n+/', "\n", trim($result['text']));
		$result['text'] = trim($result['text']);
		$result['code'] = rtrim($result['code']);

		return $result;
	}


	/**
	 * @param $tagName
	 * @param $details
	 * @return \stdClass
	 */
	protected function parsePhpdocTag($tagName, $details)
	{
		$details   = trim($details);
		$tag       = new \stdClass;
		$tag->name = $tagName;

		switch($tag->name)
		{
			case 'return':
				$details = preg_split('/\s+/', $details, 2);
				if(isset($details[0]))
					$tag->type = $details[0];
				if(isset($details[1]))
					$tag->description = $details[1];
				break;

			case 'param':
			case 'property':
			case 'property-read':
			case 'property-write':
				if(preg_match('/^(([^\s]+)\s+)?\$(\w+)(\s+(.*))?/', $details, $matches))
				{
					$tag->varName = $matches[3];
					if($matches[2] != '')
						$tag->type = $matches[2];
					if(count($matches) == 6)
						$tag->description = $matches[5];
				}
				else
				{
					$tag->varName = null;
					$arr = preg_split('/\s+/', $details);
					if(isset($arr[0])) $tag->type        = $arr[0];
					if(isset($arr[1])) $tag->description = $arr[1];
				}

				if(preg_match('/(\w+)-(read|write)$/', $tag->name, $matches))
				{
					$tag->name   = $matches[1];
					$tag->access = $matches[2];
				}

				break;

			default:
				$tag->details = $details;
				break;
		}

		return $tag;
	}
}