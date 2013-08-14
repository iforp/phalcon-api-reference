<?php

namespace ApiDocs\Models;

use Phalcon\Mvc\Model\Relation;


class Constants extends Model
{
	public $id;
	public $class_id;
	public $name;
	public $type;
	public $value;
	public $docs;
	public $line;

	public function initialize()
	{
		$this->belongsTo('class_id',  __NAMESPACE__.'\Classes', 'id', [
			'alias'      => 'class',
			'foreignKey' => ['action' => Relation::ACTION_CASCADE],
		]);
	}
}