<?php

namespace ApiDocs\Forms;


class Form extends \Phalcon\Forms\Form
{
	protected $name;
	protected $labels = [];

	public function initialize()
	{
		if(!$this->name)
		{
			$this->name = get_called_class();
			$this->name = substr($this->name, strrpos($this->name, '\\')+1);
		}

		foreach($this->getElements() as $element)
		{
			$name = $element->getName();

			if(!$element->getAttribute('id'))
			{
				$element->setAttribute('id', $this->name .'-'. $name);
			}

			if(!$element->getLabel() && isset($this->labels[$name]))
			{
				$element->setLabel($this->labels[$name]);
			}
		}
	}


	public function appendMessage($name, $message)
	{
		if(empty($this->_messages[$name]))
		{
			$this->_messages[$name] = new \Phalcon\Validation\Message\Group;
		}
		$this->_messages[$name]->appendMessage(new \Phalcon\Validation\Message($message, $name, 'custom'));
		return $this;
	}


	public function renderWithLabel($name, $attributes=null, $labelName=null)
	{
		$element  = $this->get($name);
		$messages = $this->getMessagesFor($name);
		$message  = count($messages) ? $messages[0] : '';
		$label    = $labelName ?: $element->getLabel();

		empty($attributes) || $element->setAttributes($attributes);
		$element->setLabel($label);

		echo '<div class="control-group '.($message?'error':'').'">';
		echo sprintf('<label for="%s" class="control-label">%s</label>', $element->getAttribute('id'), $label);
		echo "<div class=\"controls\">$element <span class=\"help-block\">$message</span></div>";
		echo '</div>';
	}


	public function renderForm($parameters=null, $submitLabel=null)
	{
		if(!$parameters)
		{
			$parameters = trim($this->request->get('_url'), '/');
		}

		if(is_string($parameters) || !isset($parameters['class']))
		{
			$parameters = (array)$parameters;
			$parameters['class'] = 'form-horizontal';
		}

		echo \Phalcon\Tag::form($parameters);
		foreach($this->getElements() as $element)
		{
			$this->renderWithLabel($element->getName());
		}

		if($submitLabel)
		{
			echo '<div class="form-actions">';
			echo (new \Phalcon\Forms\Element\Submit($submitLabel))->render(['class'=>'btn btn-primary']);
			echo '</div>';
		}

		echo '</form>';
	}
}