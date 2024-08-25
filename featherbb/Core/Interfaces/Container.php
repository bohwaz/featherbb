<?php
namespace FeatherBB\Core\Interfaces;

class Container extends SlimSugar
{
    public static function get($key)
    {
        if (static::$slim->getContainer()->has($key)) {
            return static::$slim->getContainer()->get($key);
        }
        return false;
    }

    public static function set($key, $value)
    {
        return static::$slim->getContainer()->set($key, $value);
    }
}
