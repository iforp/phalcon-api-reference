<?php

namespace ApiDocs\Tasks;


/**
 * Class HelpTask
 * @property \Phalcon\CLI\Dispatcher dispatcher
 * @package ApiDocs\Tasks
 */
class HelpTask extends \Phalcon\CLI\Task
{
	const SPACE_LENGTH   = 2;
	const MAX_STR_LENGTH = 80;


	public function mainAction()
	{
		$task = $this->dispatcher->getParam('params');
		$task = isset($task[0]) ? $task[0] : null;

		if(!$task)
			$this->displayCommands();
		elseif($task == 'help')
			$this->displayIntro($task);
		else
			$this->displayHelp($task);
	}


	public function displayIntro($task, $data=[])
	{
		if($task == 'help')
		{
			echo  'Usage: help [task-name]' . PHP_EOL;
		}
		else
		{
			if(!empty($data['description']))
			{
				echo $data['description'] . PHP_EOL . PHP_EOL;
			}

			if(!empty($data['usage']))
			{
				echo 'Usage:', PHP_EOL;
				foreach($data['usage'] as $usage)
				{
					echo $usage, PHP_EOL;
				}
				echo PHP_EOL;
			}
			else
			{
				echo 'Usage: ' . $task . ($data['hasOptions']?' [options]':'') . PHP_EOL.PHP_EOL;
			}
		}
	}


	public function displayCommands()
	{
		$namespace = $this->dispatcher->getDefaultNamespace();
		echo 'Available commands:' . PHP_EOL;
		foreach (new \DirectoryIterator(__DIR__) as $fileInfo)
		{
			$filename = $fileInfo->getFilename();
			$task     = strtolower(substr($filename, 0, -8));
			if(strtolower(substr($filename, -8)) == 'task.php' && class_exists("$namespace\\{$task}task"))
			{
				echo $task . PHP_EOL;
			}
		}
	}


	public function displayOptions($options)
	{
		echo 'Options list:' . PHP_EOL;

		$maxShortLength = 0;
		$maxLongLength  = 0;
		$hasRequired    = false;

		foreach($options as $option)
		{
			if(($shortLength = strlen($option['short'])) > $maxShortLength) $maxShortLength = $shortLength;
			if(($longLength  = strlen($option['long']))  > $maxLongLength)  $maxLongLength  = $longLength;
			$hasRequired = $hasRequired || $option['required'];
		}

		foreach($options as $option)
		{
			$line = $option['short']
				? '-' .$option['short'].str_repeat(' ', $maxShortLength + self::SPACE_LENGTH - strlen($option['short']))
				: str_repeat(' ', $maxShortLength+1 + self::SPACE_LENGTH);
			$line .= $option['long']
				? '--'.$option['long'].str_repeat(' ', $maxLongLength + self::SPACE_LENGTH - strlen($option['long']))
				: str_repeat(' ', $maxLongLength+2 + self::SPACE_LENGTH);

			$descrLength   = strlen($option['description']);
			$lineLength    = strlen($line);
			$leadingSpaces = str_repeat(' ', $maxShortLength+$maxLongLength+3+self::SPACE_LENGTH*2);

			if($hasRequired)
			{
				$line .= ($option['required'] ? '!' : ' ').str_repeat(' ', self::SPACE_LENGTH);
				$leadingSpaces .= str_repeat(' ', 1+self::SPACE_LENGTH);
			}

			$line .= $lineLength < self::MAX_STR_LENGTH && self::MAX_STR_LENGTH < $descrLength + $lineLength
				? rtrim(chunk_split($option['description'], self::MAX_STR_LENGTH-$lineLength, PHP_EOL.$leadingSpaces))
				: $option['description'];

			echo $line . PHP_EOL;
		}

		if($hasRequired)
		{
			echo PHP_EOL . '! - is for required options' . PHP_EOL;
		}
	}


	public function displayHelp($task)
	{
		$namespace = $this->dispatcher->getDefaultNamespace();

		if(!class_exists("$namespace\\{$task}task"))
		{
			die("Unknown task '$task'");
		}

		$reflection = new \ReflectionClass("$namespace\\{$task}task");

		$instance = $reflection->newInstance();
		$instance->registerOptions();
		$options = $instance->getOptionDetails();

		$description = $this->parseComment($reflection->getDocComment());
		$description['hasOptions'] = !empty($options);
		$this->displayIntro($task, $description);

		if(!empty($options))
		{
			$this->displayOptions($options);
		}
	}


	public function parseComment($comment)
	{
		$beforeAT = true;
		$result   = [
			'usage'       => [],
			'description' => [],
		];

		foreach(preg_split("/\r\n|\n/", $comment) as $line)
		{
			if(!$line = ltrim($line, "/* \t"))
			{
				continue;
			}

			if($line[0] == '@')
			{
				$beforeAT = false;
			}

			if($beforeAT)
			{
				$result['description'][] = $line;
			}

			if(substr($line, 0, 10) == '@cli-usage')
			{
				$result['usage'][] = trim(substr($line, 10));
			}
		}

		$result['description'] = join(PHP_EOL, $result['description']);

		return $result;
	}
}