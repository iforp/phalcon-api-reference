<?php

namespace ApiDocs\Filters;


class AddApiLinksFilter
{
	public function filter($value)
	{
		return preg_replace_callback('/(phalcon(\\\\\w+)*)(\[\])?/i', function($matches)
		{
			$tag = \Phalcon\DI::getDefault()->get('tag');
			return $tag->linkToApi([$matches[1], $matches[0]]);
		}, $value);
	}
}