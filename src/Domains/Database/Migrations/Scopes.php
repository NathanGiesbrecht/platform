<?php

namespace SuperV\Platform\Domains\Database\Migrations;

class Scopes
{
    protected static $scopes = [];

    public static function register($key, $path)
    {
        static::$scopes[$key] = realpath($path);
    }

    public static function scopes()
    {
        return static::$scopes;
    }

    public static function key($path)
    {
        return array_get(array_flip(static::$scopes), $path);
    }

    public static function path($key)
    {
        return array_get(static::$scopes, $key);
    }
}