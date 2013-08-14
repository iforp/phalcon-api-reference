<?php

namespace ApiDocs\Components;


class Tag extends \Phalcon\Tag
{
	public static function linkTo($parameters, $text=null)
	{
		$tag = parent::linkTo($parameters, $text);
		$url = is_string($parameters) ? $parameters : $parameters[0];
		if(!array_key_exists('scheme', parse_url($url)))
		{
			return $tag;
		}
		return preg_replace('/^<a\s+href="[^"]*/', '<a href="'.$url, $tag);
	}


	public static function linkToApi($params, $lang=null, $version=null)
	{
		if(is_string($params))
			$params = [$params];

		if(empty($params[1]))
			$params[1] = $params[0];

		$urlService = static::getUrlService();
		$params[0]  = $urlService->getApiUrl($params[0], $lang, $version);
		// We need to trim BaseUri because \Phalcon\Tag::linkTo() will append it
		$params[0]  = substr($params[0], strlen($urlService->getBaseUri()));

		return static::linkTo($params);
	}


	public static function linkToGit($params, $version=null, $line=null)
	{
		if(is_string($params))
			$params = [$params];

		if(empty($params[1]))
			$params[1] = $params[0];

		$params[0] = static::getUrlService()->getGitUrl($params[0], $version, $line);
		$params['target'] = isset($params['target']) ? $params['target'] : '_blank';

		return static::linkTo($params);
	}


	public static function code($code, $php=true)
	{
		$extra = ($php ? "&lt;?php\n\n" : '');
		return "<div class=\"highlight\"><pre><code>$extra$code</code></pre></div>";
	}
}