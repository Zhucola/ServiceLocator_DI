**参考Yii2实现的以依赖注入为基础的服务定位器，Yii2代码部分为vendor/yiisoft/yii2/di/**

**依赖注入DI**
依赖注入知道怎么初始化并配置对象及其依赖的所有对象，核心代码如下(简化，只是说思路)
```
  class Di
  {
    //经过new ReflectionClass()返回后的实例
    public $_reflections = [];
    //构造函数依赖关系
    public $_dependencies = [];
    
    public function build($class,$params)
    {
      $reflection = new ReflectionClass();
      $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                if (version_compare(PHP_VERSION, '5.6.0', '>=') && $param->isVariadic()) {
                    break;
                } elseif ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    $c = $param->getClass();
                    $dependencies[] = $c === null ? null : $c->getName();
                }
            }
        }

        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;
        //....  将$params参数对应给构造函数依赖关系，简化代码
        return $reflection->newInstanceArgs($params);
    }
  }
```
还可以在应用初始化的时候，先定义好依赖关系和那些类是单例模式
```
  class Di
  {
    //经过new ReflectionClass()返回后的实例
    public $_reflections = [];
    //构造函数依赖关系
    public $_dependencies = [];
    //映射出的容器id的类的初始化参数
    private $_params = [];
    //映射出的容器id与类的依赖关系
    private $_definitions = [];
    //那些类是被定义的单例模式
    private $_singletons = [];
  }
```
如果单单仅使用依赖注入，则其实本质还是new class_name，需要和服务定位器配合
**服务定位器**
服务定位器可以在应用初始化的时候定义容器id对应的类关系，还可以在应用运行时候动态修改容器id与类的映射
如test.php中的代码:
```
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
```
