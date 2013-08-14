<?php

namespace ApiDocs\Traits;


trait CLI
{
	/**
	 * Gets input from STDIN and returns a string right-trimmed for EOLs.
	 *
	 * @param bool $raw If set to true, returns the raw string without trimming
	 * @return string
	 */
	public function stdin($raw=false)
	{
		return $raw ? fgets(STDIN) : rtrim(fgets(STDIN), PHP_EOL);
	}


	/**
	 * Prints text to STDOUT.
	 *
	 * @param string $text
	 * @param bool $appendEOL
	 * @return int|false Number of bytes printed or false on error
	 */
	public function stdout($text, $appendEOL=true)
	{
		return fwrite(STDOUT, $text.($appendEOL ? PHP_EOL : ''));
	}


	/**
	 * Prints text to STDERR.
	 *
	 * @param string $text
	 * @param bool $appendEOL
	 * @return int|false Number of bytes printed or false on error
	 */
	public function stderr($text, $appendEOL=true)
	{
		return fwrite(STDERR, $text.($appendEOL ? PHP_EOL : ''));
	}


	/**
	 * Asks the user for input. Ends when the user types a PHP_EOL. Optionally
	 * provide a prompt.
	 *
	 * @param string $prompt String prompt (optional)
	 * @return string User input
	 */
	public function input($prompt=null)
	{
		if(isset($prompt))
		{
			$this->stdout($prompt, false);
		}
		return $this->stdin();
	}


	/**
	 * Prints text to STDOUT appended with a PHP_EOL.
	 *
	 * @param string $text
	 * @return int|false Number of bytes printed or false on error
	 */
	public function output($text)
	{
		return $this->stdout($text, true);
	}


	/**
	 * Prints text to STDERR appended with a PHP_EOL.
	 *
	 * @param string $text
	 * @return int|false Number of bytes printed or false on error
	 */
	public function error($text)
	{
		return $this->stderr($text, true);
	}


	/**
	 * Prompts the user for input
	 *
	 * @param string $text    Prompt string
	 * @param array $options Set of options
	 * @return string
	 */
	public function prompt($text, $options=[])
	{
		$options = $options + [
				'required'  => false,
				'default'   => null,
				'pattern'   => null,
				'validator' => null,
				'error'     => 'Input unacceptable.',
			];

		$input = $this->input($text.($options['default']?" [$options[default]]":'').': ');
		$error = null;

		if(!strlen($input))
		{
			if(isset($options['default']))
			{
				$input = $options['default'];
			}
			elseif($options['required'])
			{
				$this->output($options['error']);
				return $this->prompt($text, $options);
			}
		}
		elseif($options['pattern'] && !preg_match($options['pattern'], $input))
		{
			$this->output($options['error']);
			return $this->prompt($text, $options);
		}
		elseif($options['validator'] && !call_user_func_array($options['validator'], [$input, &$error]))
		{
			$this->output($error ?: $options['error']);
			return $this->prompt($text, $options);
		}

		return $input;
	}


	/**
	 * Asks the user for a simple yes/no confirmation.
	 *
	 * @param string $text Prompt string
	 * @return bool
	 */
	public function confirm($text)
	{
		$input = strtolower($this->input("$text [y/n]: "));
		if(!in_array($input, array('y', 'n')))
		{
			return $this->confirm($text);
		}
		return $input === 'y' ? true : false;
	}


	/**
	 * Gives the user an option to choose from. Giving '?' as an input will show
	 * a list of options to choose from and their explanations.
	 *
	 * @param string $text    Prompt string
	 * @param array $options Key-value array of options to choose from
	 * @return string An option character the user chose
	 */
	public function select($text, $options)
	{
		$input = $this->input("$text [" . implode(', ', array_keys($options)) . ', ?]: ');

		if($input === '?')
		{
			foreach($options as $key => $value)
			{
				echo " $key - $value" . PHP_EOL;
			}
			echo ' ? - Show help' . PHP_EOL;
			return $this->select($text, $options);
		}
		elseif(!isset($options[$input]))
		{
			return $this->select($text, $options);
		}

		return $input;
	}
}