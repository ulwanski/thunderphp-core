<?php

/** $Id$
 * SessionGateway.php
 * @version 1.0.0, $Revision$
 * @package eroticam.pl
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Session;

use Api;

class SessionGateway implements SessionGatewayInterface {

    const ADAPTER_REDIS = '\Core\Session\Adapter\Redis';

    private $adapter = null;

    # Instance of sessionMessages class
    private static $messages = null;

    private static $instance = false;

    public static function fetchInstance()
    {
        return self::$instance;
    }

    public function __construct($adapter) {

        self::$instance = $this;

        switch($adapter){

            case self::ADAPTER_REDIS:

                $name = self::ADAPTER_REDIS;
                $redis = Api::getRedis();
                $this->adapter = new $name($redis);
                break;

        }
    }

    public function __set($name, $value){
        $_SESSION[$name] = $value;
    }

    public function __get($name){
        return $_SESSION[$name];
    }

    public function getAdapter(){
        return $this->adapter;
    }

    public function set($name, $value){
        $this->$name = $value;
    }

    public function get($name){
        return $this->$name;
    }

    /** Return sessionMessages class
     * @return \Core\Session\sessionMessages
     */
    final public function sessionMessages(){
        if (self::$messages == null)
            self::$messages = new sessionMessages();
        return self::$messages;
    }

}