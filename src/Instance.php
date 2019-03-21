<?php
namespace di;

class Instance
{
	public $id;

	public function __construct($id)
	{	
		$this->id = $id;
	}

	public static function of($id)
	{
		return new static($id);
	}
}