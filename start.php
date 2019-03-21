<?php

use di\DiBase;
use di\Container;

require "./src/DiBase.php";
$classMap = require './src/classes.php';
spl_autoload_register(["di\DiBase","autoload"],true,true);
DiBase::$classMap = $classMap;
DiBase::$container = new Container();