<?php

namespace Core\Session;

class CookieManager {

    private $cookie = array();

    private $expire = 31104000;

    public function __construct(array $cookies, $expire = 31104000)
    {
        foreach($cookies as $name => $value){
            $this->cookie[$name] = $value;
        }

        $this->expire = $expire;
    }

    public function __get($name)
    {
        return $this->getCookie($name);
    }

    public function __set($name, $value)
    {
        $this->setCookie($name, $value, time() + $this->expire);
    }

    public function setCookie($name, $value, $expire = 0, $secure = false)
    {
        $this->cookie[$name] = $value;
        return setcookie($name, $value, $expire, "/", "", $secure);
    }

    public function getCookie($name, $default = null)
    {
        return isset($this->cookie[$name])?$this->cookie[$name]:$default;
    }

    public function deleteCookie($name)
    {
        unset($this->cookie[$name]);
        return setcookie($name, "", time() - 86400);
    }
}