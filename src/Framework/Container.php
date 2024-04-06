<?php 

declare(strict_types=1);

namespace Framework;

use ReflectionClass, ReflectionNamedType;
use Framework\Exceptions\ContainerException;

class Container
{
    private array $definitions = [];
    private array $resolved = [];

    public function addDefinitions(array $newDefinitions)
    {
        $this->definitions = [...$this->definitions, ...$newDefinitions];
    }

    public function resolve(string $className) //classname provides the name of the contoller and not the instance of the class
    {
        $reflectionClass = new ReflectionClass($className);

        if(!$reflectionClass->isInstantiable()) {
            throw new ContainerException("Class ${className} is not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();

        if(!$constructor) {
            return new $className; //create a class instance if constructor does not exist
        }

        $params = $constructor->getParameters();

        if(count($params) === 0) {
            return new $className; //Fetch parameters in a controller class
        }

        $dependencies = []; //stores dependencies or instances required in the controller

        foreach ($params as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if(!$type) {
                throw new ContainerException("Failes to resolve class {$className} because param {$name} is missing a type hint");
            }

            if(!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new ContainerException("Failed to resolve class {$className} because invalid param name");
            }

            $dependencies[] = $this->get($type->getName()); //compare datatype of parameters with list of definitions (array of class to be instantiated)
        }

        return $reflectionClass->newInstanceArgs($dependencies); //available for reflecting the class being instantiated
    }
    // Reflective class comes in to check for the class available in the controller to be used
    // Reflection api allows for validation

    public function get(string $id) //this returns an instance of any dependency, responsible for instantiating a dependency
    {
        if(!array_key_exists($id, $this->definitions)) {
            throw new ContainerException("Class {$id} does not exist in container.");
        }

        if(array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        $factory = $this->definitions[$id]; //the array is a factory function
        $dependency = $factory();

        $this->resolved[$id] = $dependency;

        return $dependency;
    }
}