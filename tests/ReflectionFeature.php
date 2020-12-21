<?php

namespace Tests;



trait ReflectionFeature
{

    private static function getMethodOfClass(string $methodName, string $className): ?\ReflectionMethod
    {
        try {
            $class = new \ReflectionClass($className);
            $method = $class->getMethod($methodName);
            $method->setAccessible(true);

            return $method;
        } catch (\ReflectionException $e) {
            return null;
        }
    }

}
