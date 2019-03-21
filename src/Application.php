<?php
namespace di;

class Application extends ServiceLocator
{
	public function setDiServerSingleton($class, $definition = [], array $params = [])
	{
		return DiBase::$container->setSingleton($class, $definition, $params);
	}

	public function setDiServer($class, $definition = [], array $params = [])
	{
		return DiBase::$container->set($class, $definition, $params);
	}

	public function setContainer($config)
    {
        DiBase::configure(DiBase::$container, $config);
    }
}