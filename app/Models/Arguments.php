<?php

namespace ApiDocs\Models;


class Arguments extends Model
{
	public $id;
	public $method_id;
	public $name;
	public $type;
	public $is_optional;
	public $default_value;
	public $description;
	public $ordering;

	public function initialize()
	{
		$this->belongsTo('method_id',  __NAMESPACE__.'\Methods', 'id', [
			'alias'      => 'method',
			'foreignKey' => true
		]);
	}
}