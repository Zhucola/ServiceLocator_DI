<?php
$src_basedir = dirname(__DIR__).DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
$test_obj_basedir = dirname(__DIR__).DIRECTORY_SEPARATOR."test_obj".DIRECTORY_SEPARATOR;
return [
	"di\\Container"=>$src_basedir."Container.php",
	"di\\ServiceLocator"=>$src_basedir."ServiceLocator.php",
	"di\\Application"=>$src_basedir."Application.php",
	"di\\BaseObject"=>$src_basedir."BaseObject.php",
	"di\\Instance"=>$src_basedir."Instance.php",
	"di\\Configurable"=>$src_basedir."Configurable.php",
	"test_obj\\A"=>$test_obj_basedir."A.php",
	"test_obj\\B"=>$test_obj_basedir."B.php",
	"test_obj\\C"=>$test_obj_basedir."C.php",
	"test_obj\\D"=>$test_obj_basedir."D.php",
	"test_obj\\E"=>$test_obj_basedir."E.php",
	"test_obj\\F"=>$test_obj_basedir."F.php",
];