<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 02-05-13
 * Time: 05:29 PM
 */

class Config
{
    private static $config;

    public static function load(){
        include 'cfg.php';
        self::$config = $config;
    }

    /**
     * Singleton
     * @param $item
     * @return mixed
     */
    public static function get($item)
    {
        if (!isset(self::$config)) {
            self::load();
        }
        return self::$config[$item];
    }

    public static function set($item, $value)
    {
        self::$config[$item] = $value;
    }
}
