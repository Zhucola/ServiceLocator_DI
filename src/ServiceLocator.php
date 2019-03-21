<?php
namespace di;

use Exception;
use Closure;

class ServiceLocator extends BaseObject
{
    private $_components = [];
    private $_definitions = [];

    public function __get($name)
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        throw new Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    public function __isset($name)
    {
        if ($this->has($name)) {
            return true;
        }

        return false;
    }

    public function get($id, $throwException = true)
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }

        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
            /*  $func = function(){};
	         *  $res = is_object($func); //true
	        */
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_components[$id] = $definition;
            }
            return $this->_components[$id] = DiBase::createObject($definition);
        } elseif ($throwException) {
            throw new Exception("Unknown component ID: $id");
        }

        return null;
    }

    public function set($id, $definition)
    {
        unset($this->_components[$id]);

        if ($definition === null) {
            unset($this->_definitions[$id]);
            return;
        }
        /*  $func = function(){};
         *  $res = is_callable($func,true); //true
         *  $res = is_callable($func,false); //true
         *  $res = is_callable("func",true); //true
         *  $res = is_callable("func",false); //false
         *  class a{}
         *  $res = is_callable("a");  //true
        */
        if (is_object($definition) || is_callable($definition, true)) {
            $this->_definitions[$id] = $definition;
        } elseif (is_array($definition)) {
            if (isset($definition['class'])) {
                $this->_definitions[$id] = $definition;
            } else {
                throw new Exception("The configuration for the \"$id\" component must contain a \"class\" element.");
            }
        } else {
            throw new Exception("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
    }

    public function has($id, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

    public function clear($id)
    {
        unset($this->_definitions[$id], $this->_components[$id]);
    }

    public function getComponents($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_definitions : $this->_components;
    }

    public function setComponents($components)
    {
        foreach ($components as $id => $component) {
            $this->set($id, $component);
        }
    }
}