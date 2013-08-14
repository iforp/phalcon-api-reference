<?php

namespace ApiDocs\Components;


class Url extends \Phalcon\Mvc\Url
{
	public function getApiUrl($class, $lang=null, $version=null)
	{
		$dispatcher = $this->getDI()->get('dispatcher');
		return $this->get([
			'for'      => 'showClass',
			'class'    => str_replace('\\', '/', $class),
			'language' => $lang    ?: $dispatcher->getParam('language'),
			'version'  => $version ?: $dispatcher->getParam('version'),
		]);
	}

	public function getGitUrl($file, $version=null, $line=null)
	{
		$dispatcher = $this->getDI()->get('dispatcher');
		$baseUrl    = $this->getDI()->get('config')->fileGithubUrl;
		$version    = $version ?: $dispatcher->getParam('version');
		$url        = sprintf($baseUrl, $version, $file);
		if($line)
			$url .= "#L$line";
		return $url;
	}
}