<?php

namespace ApiDocs\Models;

class Translations extends Model
{
	public $lang;
	public $version;
	public $class_id;
	public $constant_id;
	public $property_id;
	public $method_id;
	public $argument_id;
	public $text;


	public function initialize()
	{
		$this->belongsTo('version', __NAMESPACE__.'\Versions', 'version', [
			'alias'      => 'versionObj',
			'foreignKey' => true,
		]);
		$this->belongsTo('class_id', __NAMESPACE__.'\Classes', 'id', [
			'alias'      => 'classObj',
			'foreignKey' => true,
		]);
		$this->belongsTo('constant_id', __NAMESPACE__.'\Constants', 'id', [
			'alias'      => 'constantObj',
			'foreignKey' => true,
		]);
		$this->belongsTo('property_id', __NAMESPACE__.'\Properties', 'id', [
			'alias'      => 'propertyObj',
			'foreignKey' => true,
		]);
		$this->belongsTo('method_id', __NAMESPACE__.'\Methods', 'id', [
			'alias'      => 'methodObj',
			'foreignKey' => true,
		]);
		$this->belongsTo('argument_id', __NAMESPACE__.'\Arguments', 'id', [
			'alias'      => 'argumentObj',
			'foreignKey' => true,
		]);
	}
}