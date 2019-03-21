<?php
namespace di;

use Exception;

class DiBase
{
    public static $classMap;
    public static $container;

	public static function createObject($type, array $params = [])
    {
        if (is_string($type)) {
            return static::$container->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::$container->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return static::$container->invoke($type, $params);
        } elseif (is_array($type)) {
            throw new Exception('Object configuration must be an array containing a "class" element.');
        }
        throw new Exception('Unsupported configuration type: ' . gettype($type));
    }

    public static function autoload($className){
        if (isset(static::$classMap[$className])) {
            $classFile = static::$classMap[$className];
        } else {
            return;
        }

        include $classFile;
    }

    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }
}