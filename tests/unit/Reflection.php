<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit;

use ReflectionProperty;

class Reflection
{
    /**
     * Invoke protected or private method of object.
     *
     * @param object $object
     * @param string $method name of method to invoke
     * @param mixed  $arg1   args that passed to invoked method [optional]
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public static function callProtectedMethod($object, $method, $arg1 = null)
    {
        $args = array_slice(func_get_args(), 2);
        $reflectionMethod = new \ReflectionMethod(get_class($object), $method);

        if ($reflectionMethod->isPublic()) {
            throw new \Exception(sprintf('%s::%s already accessible', get_class($object), $method));
        }

        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invokeArgs($object, $args);
        $reflectionMethod->setAccessible(false);

        return $result;
    }

    /**
     * Set to protected or private property new value.
     *
     * @param object $object
     * @param string $property name of property to set
     * @param mixed  $value    new value for $property
     *
     * @throws \Exception
     */
    public static function setProtectedProperty($object, $property, $value): void
    {
        $reflectionProperty = self::makePropertyAccessible($object, $property);

        $reflectionProperty->setValue($object, $value);

        $reflectionProperty->setAccessible(false);
    }

    /**
     * Get protected or private property value.
     *
     * @param object $object
     * @param string $property name of property
     *
     * @throws \Exception
     *
     * @return mixed value of $property
     */
    public static function getProtectedProperty($object, $property)
    {
        $reflectionProperty = self::makePropertyAccessible($object, $property);

        $value = $reflectionProperty->getValue($object);

        $reflectionProperty->setAccessible(false);

        return $value;
    }

    /**
     * Set to protected or private properties new values.
     *
     * @param object $object
     * @param array  $properties array of properties to rewrite
     *                           [
     *                           property_name => new_property_value
     *                           ]
     *
     * @throws \Exception
     */
    public static function setProtectedProperties($object, array $properties): void
    {
        foreach ($properties as $property => $value) {
            self::setProtectedProperty($object, $property, $value);
        }
    }

    /**
     * @param object $object
     * @param string  $property
     *
     * @throws \Exception
     *
     * @return ReflectionProperty
     */
    protected static function makePropertyAccessible($object, $property): ReflectionProperty
    {
        $reflectionClass = new \ReflectionClass(get_class($object));
        $reflectionProperty = $reflectionClass->getProperty($property);

        if ($reflectionProperty->isPublic()) {
            throw new \Exception(sprintf('%s::%s already accessible', get_class($object), $property));
        }

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }
}
