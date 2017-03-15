<?php

/** $Id$
 * Request.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Router;

use \Core\Session\CookieManager;

class Request {

    const SCHEME_HTTP = 1;
    const SCHEME_HTTPS = 2;
    const ACCEPT_TEXT = 'text/plain';
    const ACCEPT_HTML = 'text/html';
    const ACCEPT_JSON = 'application/json';
    const ACCEPT_JAVASCRIPT = 'text/javascript';

    private $postData = array();        # Filtered POST data
    private $postRaw = array();         # Raw POST data
    private $requestPath = array();     # Request path
    private $requestParams = array();   # Filtered GET data
    private $urlCount = 0;              // Liczba parametrów w ścieżce do zasobów (np. "/home/last/10" = 3, parametry GET nie są wliczane)
    private $urlData = array();
    private $urlScheme = false;        // Rodzaj protokołu (http lub https)
    private $request_id = false;        // Jeżeli żądanie posiadało liczbę na końcu ścieżki, jest ona przepisywana do tej zmiennej
    private $argv = array();            # Command line arguments array
    private $argc = 0;                  # Command line arguments qty
    private $cookies = null;

    public function __construct($source = StandardRouter::ROUTER_SOURCE_REMOTE){

        /** @var \Core\Session\CookieManager cookies */
        $this->cookies = new CookieManager($_COOKIE);

        if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '::1') $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        if (!isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['SERVER_PROTOCOL'])){
            $scheme = explode('/', $_SERVER['SERVER_PROTOCOL']);
            $_SERVER['REQUEST_SCHEME'] = strtolower($scheme[0]);
        }

        if($source == StandardRouter::ROUTER_SOURCE_CONSOLE){
            if(isset($_SERVER['argc'])) $this->argc = $_SERVER['argc'];
            if(isset($_SERVER['argv'])) $this->argv = $_SERVER['argv'];
            if($this->argc > 1){
                $this->urlData['path'] = trim($this->argv[1], '/');
                $this->requestPath = explode('/', $this->urlData['path']);
            }
        } else {
            $urlString = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];          // Tworzymy pełny adres zapytania, razem z parametrami
            $this->urlData = parse_url(str_replace(trim(dirname($_SERVER['SCRIPT_NAME']), '/'), '', $urlString));             // Parsujemy pełny adres, ale z pominięciem ścieżki zdalnej
            $this->requestPath = explode('/', trim($this->urlData['path'], '/'));
        }

        $this->urlCount = count($this->requestPath);

        if (isset($this->urlData['scheme'])){
            $this->urlScheme = $this->urlData['scheme'];
        }

        # Read POST data
        if (isset($_POST) and !empty($_POST)){
            foreach ($_POST as $name => $value) {
                $name = $this->clean_param($name);
                $this->postData[$name] = $this->clean_data($value);
                $this->postRaw[$name]  = $value;
            }
        }

        # Read GET data
        if (isset($this->urlData['query']) && !empty($this->urlData['query'])){
            $queryArray = explode('&', $this->urlData['query']);
            foreach ($queryArray as $param) {
                $tmp = explode('=', $param);
                $name = $this->clean_param($tmp[0]);
                if (isset($tmp[1])) {
                    $this->requestParams[$name] = $this->clean_param($tmp[1]);
                } else {
                    $this->requestParams[$name] = null;
                }
            }
        }

        $count = ((int) $this->urlCount) - 1;
        if (isset($this->requestPath[$count])) {
            if ($this->is_decimal($this->requestPath[$count]))
                $this->request_id = (int) $this->requestPath[$count];                                                   // Jeżeli ostatni parametr jest liczbą dziesiętną, przepisujemy je jako 'id'
        }

        $_REQUEST = array();
        //$_COOKIE = array(); # Nie można tego czyścić bo usuwa się id sesji od razu
        $_POST = array();
        $_GET = array();
    }

    public function isPost()
    {
        return (bool)count($this->postData);
    }

    public function __get($name) {
        if(isset($this->requestParams[$name])){
            return $this->requestParams[$name];
        }
        if(isset($this->postData[$name])){
            return $this->postData[$name];
        }
        return false;
    }

    public function __set($name, $value) {
        $this->requestParams->$name = $value;
    }

    public function getPost($name = null, $default = null) {
        if($name === null){
            return $this->postData;
        }
        if(isset($this->postData[$name])){
            return $this->postData[$name];
        }
        return $default;
    }

    public function toArray() {
        return $this->urlData;
    }

    /** Return cookie manager
     * @return CookieManager
     */
    public function getCookies(){
        return $this->cookies;
    }

    /** Zwraca ID przekazane w adresie lub zwraca wartość domyślną przekazaną w parametrze.
     * @access public
     * @param  mixed $default Domyślna wartość, zwracana jeśli ID nie istnieje.
     * @return int Numer id przekazany w rządaniu http (jeśli został przekazany)
     */
    public function getId($default = false) {
        if ($this->request_id)
            return (int) $this->request_id;
        else
            return $default;
    }

    /** Zwraca parametr <b>GET</b> przekazany w żądaniu. Po przekazaniu numeru parametry zamiast nazwy,
     *   próbuje najpierw dopasować pasujący parametr ścieżki w żądania http, bez argumentów zwraca tablicę rządań.
     * @access public
     * @param  string|int $name Nazwa lub numer parametru.
     * @param  mixed $default Wartość zwracana, jeśli parametr nie został odnaleziony.
     * @return mixed Dane z żądania http
     */
    public function getParam($name = null, $default = false) {
        if ($name === null) return $this->requestParams;
        if (isset($this->requestParams[$name]))
            return $this->requestParams[$name];
        return $default;
    }

    public function getView(){
        return $this->getPath(2);
    }

    public function getPath($num = 0){
        return isset($this->requestPath[$num])?$this->requestPath[$num]:false;
    }

    public function getPathString(){
        return implode('/', $this->requestPath);
    }

    public function getPathLast(){
        return end($this->requestPath);
    }

    /** Funkcja zwraca w żaden sposób <b>nie filtrowane</b> dane przesłane metodą POST.
     *   Zwrócone dane, należy <i>samodzielnie przefiltrować</i> przed zapisaniem do bazy danych.
     * @access public
     * @param  string $name Nazwa przekazanego parametru metodą POST.
     * @return string Dane POST
     */
    public function getRaw($name = null){
        if ($name === null) return (array) $this->postRaw;
        if (isset($this->postRaw[$name])) return $this->postRaw[$name];
        return false;
    }

    public function isAjaxRequest() {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']))
            return false;
        $hrw = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
        return (bool) (!empty($hrw) && $hrw == 'xmlhttprequest');
    }

    public function isXmlRequest() {
        $pos = strpos($_SERVER['REQUEST_URI'], '?');
        if($pos === false) $pos = strlen($_SERVER['REQUEST_URI']);
        $ext = substr($_SERVER['REQUEST_URI'], $pos - 4, 4);
        if($ext == ".xml") return true;
        return false;
    }

    public function isSSLRequest() {
        return (bool)($this->urlScheme == 'https')?true:false;
    }

    /* Metoda zwraca pierwszy ze znanych sobie formatów danych ze wszystkich wysłanych w żądaniu http
     * @access public
     * @return mixed Zwraca string opisujący żądanie lub <i>false</i>
     */
    public function getAcceptFormat() {
        $hac = strtolower(filter_input(INPUT_SERVER, 'HTTP_ACCEPT'));
        $accept = explode(',', $hac);

        $formats = array(self::ACCEPT_HTML, self::ACCEPT_JSON, self::ACCEPT_TEXT, self::ACCEPT_JAVASCRIPT);

        foreach ($accept as $a) {
            if (in_array($a, $formats))
                return $a;
        }
        return false;
    }

    private function clean_param($string) {
        if (is_numeric($string)) return (int) $string;
        $search = array('--', '..', '__', ' ');
        $replace = array('-', '.', '_', '');
        $string = str_replace($search, $replace, $string);
        $string = preg_replace("/[^A-Za-z0-9@._-]+/", "", $string);
        return trim($string);
    }

    private function clean_data($string) {

        if (is_array($string)) {                                                                                    // Jeżeli dane są tablicą obrabiany kolejno każdy jej element
            foreach ($string as $key => $val)
                $string[$key] = $this->clean_data($val);
            return $string;
        }

        $isZeroNumber = (substr(trim($string), 0, 1) == '0');

        if (is_numeric($string) && $isZeroNumber == false) {                                                            // Jeżeli dane są numeryczne, nie obrabiamy ich
            if (floor((float) $string) != $string and fmod((float) $string, 1) !== 0)
                return (float) $string;                                                                                 // Jeżeli dane są wartością 'float', zwracamy je ...
            else
                return (int) $string;                                                                                   // w przeciwnym razie zwracamy je jako 'int'
        } else {
            $string = strip_tags($string);                                                                              // Usuwamy tagi html
            $string = htmlspecialchars($string, ENT_QUOTES);                                                            // Usuwamy znaki specjalne
            return (string) trim($string);                                                                              // Trimujemy i zwracamy
        }
    }

    private function is_decimal($val) {
        return is_numeric($val) && floor($val) == $val;
    }

    public function getClientIp(){
        return filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    }

    public function getClientIpLong(){
        $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
        return ip2long($ip);
    }

}
