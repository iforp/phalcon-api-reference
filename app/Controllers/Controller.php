<?php

namespace ApiDocs\Controllers;

class Controller extends \Phalcon\Mvc\Controller
{
	/**
	 * @param array|string $forward
	 * @return mixed
	 */
	protected function forward($forward)
	{
		if(is_string($forward))
		{
			$uriParts = explode('/', $forward, 2);
			$forward  = [
				'controller' => $uriParts[0],
				'action'     => $uriParts[1]
			];
		}
		$this->view->cache(false);
		return $this->dispatcher->forward($forward);
	}


	/**
	 * @return mixed
	 */
	protected function forward404()
	{
		$this->view->cache(false);
		$route404 = $this->router->getRouteByName('404');
		return $this->dispatcher->forward($route404->getPaths());
	}
}