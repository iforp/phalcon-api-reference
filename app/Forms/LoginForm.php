<?php

namespace Pixaro\Forms;

use \Phalcon\Forms\Element;
use \Phalcon\Validation\Validator;

class LoginForm extends \Pixaro\Base\Form
{
	protected $labels = [
		'login' => 'Логин',
		'pass'  => 'Пароль',
	];

	public function initialize()
	{
		$login = new Element\Text('login');
		$login->addValidator(new Validator\PresenceOf([
			'message' => 'Необходимо указать логин'
		]));
		$this->add($login);

		$pass = new Element\Password('pass');
		$pass->addValidator(new Validator\PresenceOf([
			'message' => 'Необходимо указать пароль'
		]));
		$this->add($pass);

		parent::initialize();
	}


	public function authenticate()
	{
		$isValid = $this->getValue('login') == $this->config->admin->login &&
		           $this->getValue('pass')  == $this->config->admin->pass;
		if(!$isValid)
		{
			$this->appendMessage('login', "Неправильный {$this->labels['login']} или {$this->labels['pass']}");
		}
		return $isValid;
	}
}