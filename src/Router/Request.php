<?php

/** $Id$
 * Request.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Router;

use Iterator;
use ArrayAccess;

class Request implements Iterator, ArrayAccess {

    const SCHEME_HTTP = 1;
    const SCHEME_HTTPS = 2;
    const ACCEPT_TEXT = 'text/plain';
    const ACCEPT_HTML = 'text/html';
    const ACCEPT_XML = 'application/xml';
    const ACCEPT_JSON = 'application/json';

    /** @var bool|int Id from last path element */
    protected $requestId = false;

    /** @var int Command line arguments qty */
    protected $argc = 0;

    /** @var array Command line arguments array */
    protected $argv = [];

    /** @var array Filtered POST data */
    protected $postData = [];

    /** @var array Raw POST data */
    protected $postRaw = [];

    /** @var array Filtered GET data */
    protected $requestParams = [];

    /** @var array Request path */
    protected $requestPath = [];

    /** @var string|null Request method */
    protected $requestMethod = null;

    /** @var int Parameters count in path */
    protected $urlCount = 0;

    /** @var array Parsed url components */
    protected $urlComponents = [];

    /**
     * Request constructor.
     */
    public function __construct(){

        # Get request method for API
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];

        # TODO: Change this for something smarter
        if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '::1') $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        if($this->isConsoleRequest()){
            if(isset($_SERVER['argc'])) $this->argc = $_SERVER['argc'];
            if(isset($_SERVER['argv'])) $this->argv = $_SERVER['argv'];
            if($this->argc > 1){
                $this->urlComponents['path'] = trim($this->argv[1], '/');
                $this->requestPath = explode('/', $this->urlComponents['path']);
            }
        } else {
            $urlString = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];          // Tworzymy pełny adres zapytania, razem z parametrami
            $this->urlComponents = parse_url(str_replace(trim(dirname($_SERVER['SCRIPT_NAME']), '/'), '', $urlString)); // Parsujemy pełny adres, ale z pominięciem ścieżki zdalnej
            # TODO: Add exception when parse_url return false

            # Add full url to array
            $this->urlComponents['url'] = $urlString;

            # Explode url path to array
            $this->requestPath = explode('/', trim($this->urlComponents['path'], '/'));
        }

        $this->urlCount = count($this->requestPath);

        # Read and clean POST data
        if(isset($_POST) and !empty($_POST)) foreach($_POST as $name => $value) {
            $name = $this->cleanParam($name);
            $this->postData[$name] = $this->cleanData($value);
            $this->postRaw[$name]  = $value;
        }

        # Read and clean GET data
        if (isset($this->urlComponents['query']) && !empty($this->urlComponents['query'])){
            $queryArray = explode('&', $this->urlComponents['query']);
            foreach ($queryArray as $param) {
                $tmp = explode('=', $param);
                $name = $this->cleanParam($tmp[0]);
                if (isset($tmp[1])) {
                    $this->requestParams[$name] = $this->cleanParam($tmp[1]);
                } else {
                    $this->requestParams[$name] = null;
                }
            }
        }

        # If last url param is a digit, save it as id
        $count = ((int) $this->urlCount) - 1;
        if (isset($this->requestPath[$count]) && $this->isDecimal($this->requestPath[$count])){
            $this->requestId = intval($this->requestPath[$count]);
        }

        # You can't clean cookies because you remove session id
        //$_COOKIE = array();
        $_REQUEST = array();
        $_POST = array();
        $_GET = array();
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get(string $name) {
        if(isset($this->requestParams[$name])){
            return $this->requestParams[$name];
        }
        if(isset($this->postData[$name])){
            return $this->postData[$name];
        }
        return false;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set(string $name, $value) {
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool {
        return (isset($this->requestParams[$name]) || isset($this->postData[$name]));
    }

    /**
     * @param string $name
     */
    public function __unset(string $name): void
    {
        if(isset($this->requestParams[$name])) unset($this->requestParams[$name]);
        if(isset($this->postData[$name])) unset($this->postData[$name]);
    }

    /** The __toString() method allows a class to decide how it will react when it is treated like a string.
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->urlComponents['url'];
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return current($this->postData);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->postData);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->postData);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return isset($this->postData[key($this->postData)]);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->postData);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->postData[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->postData[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->postData[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->postData[$offset]);
    }

    /** This method is called by var_dump() when dumping an object to get the properties that should be shown.
     * @link http://php.net/manual/en/language.oop5.magic.php#object.debuginfo
     * @return array
     * @since 5.6.0
     */
    public function __debugInfo(): array
    {
        $classData = [
            'requestId'     => $this->requestId,
            'urlCount'      => $this->urlCount,
            'requestMethod' => $this->requestMethod,
        ];

        if(!empty($this->urlComponents)) $classData = array_merge($classData, ['urlComponents' => $this->urlComponents]);
        if(!empty($this->requestPath)) $classData = array_merge($classData, ['requestPath' => $this->requestPath]);
        if(!empty($this->requestParams)) $classData = array_merge($classData, ['requestParams' => $this->requestParams]);
        if(!empty($this->postData)) $classData = array_merge($classData, ['postData' => $this->postData]);
        if(!empty($this->argv)) $classData = array_merge($classData, ['argv' => $this->argv]);

        return $classData;
    }

    /** Returns request method, f.e. GET, POST or DELETE
     * @access public
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    /** Return true if POST data was send
     * @access public
     * @return bool
     */
    public function isPost(): bool
    {
        return (bool)($this->requestMethod === "POST");
    }

    /** Get one item from post data or all of them
     * @access public
     * @param null $name
     * @param null $default
     * @return array|mixed|null
     */
    public function getPost($name = null, $default = null)
    {
        if($name === null) return $this->postData;
        if(isset($this->postData[$name])){
            return $this->postData[$name];
        }
        return $default;
    }

    /** Return not filtered post data
     * @access public
     * @param  string $name
     * @return string Dane POST
     */
    public function getRawPost($name = null)
    {
        if ($name === null) return (array) $this->postRaw;
        if (isset($this->postRaw[$name])) return $this->postRaw[$name];
        return false;
    }

    /** Return all post data as array
     * @access public
     * @return array
     */
    public function postToArray(): array
    {
        return $this->postData;
    }

    /** Zwraca parametr <b>GET</b> przekazany w żądaniu. Po przekazaniu numeru parametry zamiast nazwy,
     *   próbuje najpierw dopasować pasujący parametr ścieżki w żądania http, bez argumentów zwraca tablicę rządań.
     * @access public
     * @param  string|int $name Nazwa lub numer parametru.
     * @param  mixed $default Wartość zwracana, jeśli parametr nie został odnaleziony.
     * @return mixed Dane z żądania http
     */
    public function getParam($name = null, $default = false)
    {
        # TODO: Czy to jest potrzebne w ogóle?
        if ($name === null) return $this->requestParams;
        if (isset($this->requestParams[$name]))
            return $this->requestParams[$name];
        return $default;
    }

    /**
     * @return array
     */
    public function getPathAsArray(): array
    {
        return $this->requestPath;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->urlComponents['url'];
    }

    /**
     * @return string
     */
    public function getUrlPath(): string
    {
        return $this->urlComponents['path'];
    }

    /**
     * @return string
     */
    public function getUrlHost(): string
    {
        return $this->urlComponents['host'];
    }

    /**
     * @return string
     */
    public function getUrlBase(): string
    {
        return $this->urlComponents['scheme'].'://'.$this->urlComponents['host'].'/';
    }

    /**
     * @return bool
     */
    public function isAjaxRequest(): bool
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']))
            return false;
        $hrw = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
        return (bool) (!empty($hrw) && $hrw == 'xmlhttprequest');
    }

    /**
     * @return bool
     */
    public function isSSLRequest(): bool
    {
        return (bool)($this->urlComponents['scheme'] == 'https')?true:false;
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

    /**
     * @param $string
     * @return int|string
     */
    protected function cleanParam($string): string {
        if (is_numeric($string)) return (int) $string;
        $search = array('--', '..', '__', ' ');
        $replace = array('-', '.', '_', '');
        $string = str_replace($search, $replace, $string);
        $string = preg_replace("/[^A-Za-z0-9@._-]+/", "", $string);
        return trim($string);
    }

    /**
     * @param $string
     * @return array|float|int|string
     */
    protected function cleanData($string) {

        if (is_array($string)) {                                                                                    // Jeżeli dane są tablicą obrabiany kolejno każdy jej element
            foreach ($string as $key => $val)
                $string[$key] = $this->cleanData($val);
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

    /**
     * @param $val
     * @return bool
     */
    private function isDecimal($val): bool
    {
        return is_numeric($val) && floor($val) == $val;
    }

    /**
     * @return mixed
     */
    public function getClientIp(){
        return filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    }

    /**
     * @return int
     */
    public function getClientIpLong(): int{
        $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
        return ip2long($ip);
    }

    /**
     * @return bool
     */
    protected function isConsoleRequest(): bool
    {
        $isSvr = (!isset($_SERVER['SERVER_SOFTWARE']));
        $isCli = ($isSvr && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));

        return $isCli;
    }

    public function getClientLanguage($availableLangList, $defaultLanguage) {

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {

            $headersLanguagePattern = '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i';
            $acceptLanguageList = '';
            preg_match_all($headersLanguagePattern, $_SERVER['HTTP_ACCEPT_LANGUAGE'], $acceptLanguageList);

            if(isset($acceptLanguageList[4]) && isset($acceptLanguageList[1])){
                foreach($acceptLanguageList[4] as $key => $languageWeight){
                    if($languageWeight == "") $languageWeight = 1;
                    $acceptLanguageList[4][$key] = intval(floatval($languageWeight)*100);
                }

                if(count($acceptLanguageList[1])) {
                    foreach($acceptLanguageList[1] as $key => $lang){
                        $acceptLanguageList[1][$key] = str_replace('-', '_', $lang);
                    }
                    $acceptLanguageList = array_combine($acceptLanguageList[4], $acceptLanguageList[1]);
                    krsort($acceptLanguageList);

                    # Choose a matching language
                    foreach($acceptLanguageList as $languageWeight => $languageCode){
                        foreach($availableLangList as $availableLanguage){
                            $shortCode = substr($availableLanguage, 0, 2);
                            if($languageCode == $availableLanguage || $languageCode == $shortCode){
                                return $availableLanguage;
                            }
                        }
                    }
                }
            }
        }

        if (isset($_SERVER['COUNTRY_CODE'])) {
            $languageCode = $_SERVER['COUNTRY_CODE'];
            foreach($availableLangList as $availableLanguage){
                list($shortCode1, $shortCode2) = explode('_', $availableLanguage);
                if($languageCode == $availableLanguage || $languageCode == $shortCode1 || $languageCode == $shortCode2){
                    return $availableLanguage;
                }
            }
        }

        return $defaultLanguage;
    }

}
