<?php

namespace test_obj;

class A extends \di\BaseObject
{
	private $age;

	public function setage($age)
	{
		$this->age = $age + 1;
	}

	public function getage()
	{
		return $this->age;
	}
}