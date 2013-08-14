<?php

namespace ApiDocs\Filters;


class PrepareDocsFilter
{
	public function filter($value)
	{
		$newValue = '';

		foreach(preg_split('/\n\n+/', $value) as $line)
		{
			$line = trim($line);

			if(!$line || $line=='Example:')
			{
				continue;
			}

			$newValue .= (strpos($line, '<p>') === 0)
				? $line
				: "<p>$line</p>";
		}

		return $newValue;
	}
}