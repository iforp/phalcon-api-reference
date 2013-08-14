<?php

namespace ApiDocs\Controllers;

use ApiDocs\Models;
use ApiDocs\Filters\AddApiLinksFilter;
use ApiDocs\Filters\PrepareDocsFilter;

class ApiReferenceController extends Controller
{
	protected $version;
	protected $tree;


	public function beforeExecuteRoute()
	{
		if(!$this->dispatcher->getParam('version') || $this->dispatcher->getParam('version') == 'latest')
		{
			$this->dispatcher->setParam('version', Models\Versions::maximum([
				'column'     => 'version',
				'conditions' => 'version NOT LIKE("tmp-%")'
			]));
		}

		if(!$this->dispatcher->getParam('language'))
		{
			$lang = strtolower(substr($this->request->getBestLanguage(), 0, 2));
			$lang = $lang && $this->_hasLanguage($lang) ? $lang : $this->config->defaultLang;
			$this->dispatcher->setParam('language', $lang);
		}
		elseif($this->dispatcher->getParam('language') != $this->config->defaultLang && !$this->_hasLanguage())
		{
			$this->forward404();
			return false;
		}

		$this->version = Models\Versions::findFirst([
			'conditions' => 'version = ?0',
			'bind'       => [$this->dispatcher->getParam('version')],
		]);

		if(!$this->version)
		{
			$this->forward404();
			return false;
		}

		if($this->config->cache->enabled && $this->_applyCache() === false)
		{
			return false;
		}

		return true;
	}


	public function initialize()
	{
		$allVersions = Models\Versions::find([
			'conditions' => 'version NOT LIKE "tmp-%"',
			'columns'    => 'version',
			'order'      => 'version DESC'
		]);

		$this->filter->add('apiLinks', new AddApiLinksFilter);
		$this->filter->add('docs',     new PrepareDocsFilter);

		$this->tree = $this->_getStructure($this->version);
		$this->assets
			->addJs ('assets/js/apireference.js')
			->addJs ('assets/plugins/jquery.collapsedlist/jquery.collapsedlist.js')
			->addCss('assets/plugins/jquery.collapsedlist/jquery.collapsedlist.css')
			->addCss('assets/css/apireference.css');

		$this->view->setPartialsDir($this->dispatcher->getControllerName() . '/partials/');

		$this->view->setVar('tree',        $this->tree);
		$this->view->setVar('version',     $this->version->version);
		$this->view->setVar('language',    $this->dispatcher->getParam('language'));
		$this->view->setVar('allVersions', $allVersions);
	}


	public function _applyCache()
	{
		$cacheKey  = $this->dispatcher->getParam('version') . '/';
		$cacheKey .= $this->dispatcher->getParam('language') . '/';
		$cacheKey .= $this->dispatcher->getParam('summary')
			? 'summary-' . $this->dispatcher->getParam('summary')
			: str_replace('\\', '-', $this->dispatcher->getParam('class'));

		$this->view->cache([
			'key' => $cacheKey
		]);

		if($this->view->getCache()->exists($cacheKey))
		{
			return false;
		}

		$dir = $this->config->cache->dir . substr($cacheKey, 0, strrpos($cacheKey, '/'));
		if(!is_dir($dir))
		{
			mkdir($dir, 0755, true);
		}
	}


	public function indexAction()
	{

		if(!$this->version)
		{
			$this->forward404();
			return;
		}

		$summary = $this->dispatcher->getParam('summary', null, 'classes');
		$this->tag->setTitle(ucfirst($summary) . ' — Phalcon '.$this->version->version.' API reference');

		$this->view->setVar('changelog', $this->version->changelog);
		$this->view->setVar('summary',   $summary);
		$this->view->pick('apireference/'.$this->dispatcher->getParam('summary'));
	}


	protected function _getNamespaceInfo($ns)
	{
		if(isset($this->tree->structure[$ns]))
		{
			return $this->tree->structure[$ns];
		}
		$structure = array_change_key_case($this->tree->structure, CASE_LOWER);
		return isset($structure[$ns])
			? $structure[$ns]
			: null;
	}


	public function showClassAction()
	{
		if(!$this->version)
		{
			$this->forward404();
			return;
		}

		/** @var Models\Classes $class */
		$class = $this->version->getClasses([
			'conditions' => 'CONCAT(LCASE(namespace), "\\\\", LCASE(name)) = :class:',
			'bind'       => ['class' => $this->dispatcher->getParam('class')],
			'limit'      => 1,
		])->getFirst();

		$namespace = $this->_getNamespaceInfo($this->dispatcher->getParam('class'));

		if(!$class && !$namespace)
		{
			$this->forward404();
			return;
		}

		if(!$class)
			$type = 'namespace';
		elseif($class->is_interface)
			$type = 'interface';
		elseif($class->is_abstract)
			$type = 'abstract class';
		elseif($class->is_final)
			$type = 'final class';
		else
			$type = 'class';

		$subclasses = $class ? $this->_getSubclasses("$class->namespace\\$class->name") : [];
		$name       = $class ? "$class->namespace\\$class->name" : $namespace->name;

		$this->tag->setTitle(ucfirst($type) . ' ' . $name .  ' — Phalcon '.$this->version->version.' API reference');

		$this->view->setVar('subclasses', $subclasses);
		$this->view->setVar('name',       $name);
		$this->view->setVar('class',      $class ? $class : null);
		$this->view->setVar('type',       $type);
		$this->view->setVar('namespace',  $namespace);
	}


	protected function _getSubclasses($className)
	{
		$subclasses = array_keys($this->tree->inheritance, $className);
		array_walk($this->tree->implementation, function($parents, $child, $class) use(&$subclasses)
		{
			if(in_array($class, $parents))
			{
				$subclasses[] = $child;
			}
		}, $className);
		sort($subclasses, SORT_STRING|SORT_FLAG_CASE);
		return $subclasses;
	}


	protected function _getStructure(Models\Versions $version)
	{
		$tree = (object)[
			'structure'   => [],
			'inheritance' => [],
			'list'        => [],
		];

		foreach($version->classes as $class)
		{
			if(!isset($tree->structure[$class->namespace]))
			{
				$tree->structure[$class->namespace] = new \stdClass;
			}
			$tree->structure[$class->namespace]->name = $class->namespace;

			$type = $class->is_interface ? 'interfaces' : 'classes';
			$tree->structure[$class->namespace]->{$type}[] = $class->name;

			// The structure will not be complete if the namespace doesn't contain any classes
			// so we need to iterate over all namespace parts
			$parts = '';
			$prev  = null;
			foreach(explode('\\', $class->namespace) as $part)
			{
				$parts .= ($parts ? '\\'.$part : $part);
				if($prev) $tree->structure[$prev]->namespaces[] = $part;
				$prev = $parts;
			}


			$tree->list["$class->namespace\\$class->name"] = $class->is_interface ? 'interface' : 'class';


			if($class->extends)
			{
				$tree->inheritance["$class->namespace\\$class->name"] = $class->extends;
			}

			if($class->implements)
			{
				$tree->implementation["$class->namespace\\$class->name"] = explode(',', $class->implements);
			}
		}


		ksort($tree->structure, SORT_STRING|SORT_FLAG_CASE);
		ksort($tree->list,      SORT_STRING|SORT_FLAG_CASE);
		array_walk($tree->structure, function(&$obj)
		{
			if(!isset($obj->namespaces)) $obj->namespaces = [];
			if(!isset($obj->interfaces)) $obj->interfaces = [];
			if(!isset($obj->classes))    $obj->classes    = [];
			$obj->namespaces = array_unique($obj->namespaces);
			sort($obj->namespaces, SORT_STRING|SORT_FLAG_CASE);
			sort($obj->classes,    SORT_STRING|SORT_FLAG_CASE);
			sort($obj->interfaces, SORT_STRING|SORT_FLAG_CASE);
		});

		return $tree;
	}


	protected function _hasLanguage($lang=null, $version=null)
	{
		$lang    = $lang    ?: $this->dispatcher->getParam('language');
		$version = $version ?: $this->dispatcher->getParam('version');

		return (bool)Models\Translations::count([
			'conditions' => 'lang=?0 AND version=?1',
			'bind'       => [$lang, $version],
		]);
	}


	protected function _hasVersion($version=null)
	{
		$version = $version ?: $this->dispatcher->getParam('version');
		return (bool)Models\Versions::count([
			'conditions' => 'version = ?0',
			'bind'       => [$version],
		]);
	}
}