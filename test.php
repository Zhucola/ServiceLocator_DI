<?php

use di\Application;
use di\BaseObject;
use di\DiBase;

include "./start.php";
class app extends Application
{
	
}
$config = [
	//依赖注入容器配置
	'container' => [
        'definitions' => [
            
        ],
        'singletons' => [
        	"test_obj\\B" => [
				"name" => 1
			],
        ]
    ],
    //组件容器配置
	"components" => [
		"db" => [
			"class"=>"test_obj\\A",
			"age"=>1
 		],
		"cache" => [
			"class"=>"test_obj\\B",
			"name" => 33
		],
		"log"=>[
			"class"=>"test_obj\\C",
			"age"=>1
		]
	]
];
$app = new app($config);

//获取一个cache服务
if($app->has("cache")){
	$cache = $app->get("cache");
	var_dump($cache->name);//33
}


//重新注册一个cache服务
if($app->has("cache")){
	$app->set("cache",["class"=>"test_obj\\B","name"=>44]);
	$cache = $app->get("cache");
	var_dump($cache->name);//33   因为test_obj\\B已经被注册成了单例模式
}

//获取一个db服务，有注册属性
if($app->has("db")){
	$cache = $app->get("db");
	var_dump($cache->age);//注册属性的逻辑是$this->age = age + 1;
}
