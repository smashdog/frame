<?php

namespace sm;

class Loader
{
    public static $classMap = [];

    public static function load($class)
    {
        if (isset($classMap[$class])) {
            return true;
        } else {
            $class = str_replace('\\', '/', $class);
            $file = ROOT.'/'.$class.'.php';
            if (is_file($file)) {
                require_once $file;
                self::$classMap[$class] = $class;
            } else {
                return false;
            }
        }
    }
}
