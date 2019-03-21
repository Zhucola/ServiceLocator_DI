参考Yii2实现的以依赖注入为基础的服务定位器，Yii2代码部分为vendor/yiisoft/yii2/di/

**依赖注入DI**

依赖注入知道怎么初始化对象，仅仅配置构造参数就可以，核心代码如下(简化，只是说思路)
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
可以非常灵活的自定义如何new对象，如Yii2中:(如何类实现了Configurable接口，则将最后一个构造参数变成$config)
```
	if (!empty($dependencies) && $reflection->implementsInterface('yii\base\Configurable')) {
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;

            return $reflection->newInstanceArgs($dependencies);
        }
```
也可以(将$config依次赋予类，触发魔术方法__set)
```
	$object = $reflection->newInstanceArgs($dependencies);
        foreach ($config as $name => $value) {
            $object->$name = $value;
        }
```
还可以在应用初始化的时候，先定义好依赖关系和那些类是单例模式
```
  class Di
  {
    //经过new ReflectionClass()返回后的实例
    public $_reflections = [];
    //经过new ReflectionClass()返回后的实例的构造函数依赖关系
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

如一个db类，可以理解成是一个db容器，在测试环境需要连接127.0.0.1:3306,在灰度环境需要连接10.8.8.8:3386，在开发环境需要连接10.9.9.9:3307，我们可以进行如下定义，仅仅修改配置就可以让di层知道如何创建容器
```
$config = [
	"components"=>[
		"db"=> function(){
			if(测试环境){
				return [
					"class" => "test_db",
					"params" => [连接参数127.0.0.1:3306]
				];
			} elseif (灰度环境){
				return [
					"class" => "grey_db",
					"params" => [连接参数10.8.8.8:3386]
				];
			} elseif (开发环境){
				return [
					"class" => "pro_db",
					"params" => [连接参数10.9.9.9:3307]
				];
			}
		}
	]
];
```

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
如定义了一个容器db（容器id是db），那么db容器对应的类就是"test_obj\\A",可以在应用中动态修改这个映射，将容器的实例化转移到了DI层，也可以在初始化中修改$config配置，从而减少了容器之间的藕合

在Yii2中，容器id的映射也可以是一个回调，可以非常灵活的设置依赖关系

该模式也支持了设置依赖类属性，具体核心代码(简化)：
```
<?php

namespace di;
use Exception;

class BaseObject implements Configurable{
    public function __construct($config = [])
    {
        if (!empty($config)) {
	    //这么做的目的就是用另一个类去设置属性，触发该类的魔术方法
            DiBase::configure($this, $config);
        }
        $this->init();
    }

    public function init()
    {

    }
    
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . $name)) {
            throw new Exception('Getting write-only property: ' . get_class($this) . '::' . $name);
        }

        throw new Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new Exception('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new Exception('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        }

        return false;
    }

    public function __unset($name)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new Exception('Unsetting read-only property: ' . get_class($this) . '::' . $name);
        }
    }

    public function __call($name, $params)
    {
        throw new Exception('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }

    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name);
    }

    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }
}
```
