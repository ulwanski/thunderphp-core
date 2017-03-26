<?php

/** $Id$
 * api.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Application;

final class Api {

    /** @var \Core\Loader\ConfigurationLoader */
    private static $config = null;

    /** @var \Core\Cache\Volatile\CacheInterface */
    private static $cache = null;

    /** @var array */
    private static $extensions = array();

    public static function init() {

        # Load list available PHP extensions
        self::$extensions = get_loaded_extensions();

        # Creating Apcu instances
        self::$cache = \Core\Cache\Volatile\Apcu::getInstance();

        # Creating configuration loader instances
        self::$config = \Core\Loader\ConfigurationLoader::getInstance();
    }

    /** Return list of loaded PHP extensions
     * @return array
     */
    public static function getExtensions() {
        return self::$extensions;
    }

    /** Returning cache instances
     * @return \Core\Cache\Volatile\Apcu
     */
    public static function getCache(){
        return self::$cache;
    }

    /** @return \Core\Loader\ConfigurationLoader */
    public static function getConfig(){
        return self::$config;
    }

    /** Returning redis instances
     * @return \Core\Cache\Shared\Redis
     */
    public static function getRedis(){
        return \Core\Cache\Shared\Redis::getInstance(self::$config, self::$cache);
    }

    /** @return \Core\Users\User */
    public static function getUser(){
        return \Core\Users\User::getInstance(self::$config, self::$cache);
    }

    /** @return \Core\Database\SqlGateway */
    public static function getReadDatabase(){
        return \Core\Database\SqlGateway::getInstance(\Core\Database\Adapter\PdoAdapter::DB_SLAVE);
    }

    /** @return \Core\Database\SqlGateway */
    public static function getWriteDatabase(){
        return \Core\Database\SqlGateway::getInstance(\Core\Database\Adapter\PdoAdapter::DB_MASTER);
    }


}
