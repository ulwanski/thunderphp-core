<?php

/** $Id$
 * User.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Users {

    use \Core\Users\AbstractUser;
    use \Core\Database\SqlGateway;
    use \Core\Encryption\AbstractEncryption as Encryption;

    class User extends AbstractUser
    {

        const ERROR_SUCCESS = 0x00;
        const ERROR_TIMEOUT = 0x01;
        const ERROR_HASH = 0x02;
        const ERROR_USER = 0x03;

        private static $Instance = false;
        private $timeout = 600; // 10 min

        public static function getInstance()
        {
            if (self::$Instance == false) self::$Instance = new \Core\Users\User();
            return self::$Instance;
        }

        public function __construct()
        {
            parent::__construct();
            $this->id       = (int)filter_var($this->id, FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);
            $this->trace    = (int)filter_var($this->trace, FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);

            # TODO: Try to get real client IP in all cases
            $this->ip       = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
            $this->geoip    = $countryCode = empty($_SERVER['COUNTRY_CODE'])?$_SERVER['COUNTRY_CODE2']:$_SERVER['COUNTRY_CODE'];
            $this->country  = $countryCode = empty($_SERVER['COUNTRY_NAME'])?$_SERVER['COUNTRY_NAME2']:$_SERVER['COUNTRY_NAME'];
            $this->city     = $countryCode = empty($_SERVER['CITY_NAME'])?$_SERVER['CITY_NAME2']:$_SERVER['CITY_NAME'];

            if ($this->id != 0 && $this->isAuthorized()) {

                if ($this->trace == 0 or $this->trace < (time() - $this->timeout)) {
                    return $this->logout(self::ERROR_TIMEOUT);
                }

                $hash = $this->getTraceHash($this->id);
                if ($hash != $this->attributes['hash']) {
                    return $this->logout(self::ERROR_HASH);
                }

                $this->trace = time();
            } else {
                $this->auth = 'guest';
                $this->status = false;
            }

        }

        /** Return true if user is logged in, false otherwise.
         * @return bool
         */
        public function isAuthorized()
        {
            return ($this->auth == false || $this->auth == 'guest')?false:true;
        }

        public function getCountryCode()
        {
            return $this->geoip;
        }

        public function login($username, $password)
        {
            /* @var $db \Core\Database\SqlGateway */
            $db = SqlGateway::getInstance();

            # Get basic user data from database before log in
            $info = $db->getRowByField('users', 'id, status, birthday, created', 'email', $username);

            # If user account is not active, they he can't be logged in on it
            if($info['status'] != 'active' && $info['status'] != 'new') return false;

            $user = false;
            if($info){
                $hash = Encryption::hash($password, $info['birthday'].$info['created']);
                $select = "id, firstname, lastname, username, email, status, auth, lang, gender, settings, theme, created, logged, birthday";
                $user = $db->getRowByWhere('users', $select, '`id` = "' . $info['id'] . '" AND `password` = "' . $hash . '"');
            }


            if($user) {
                foreach ($user as $key => $value) {
                    $this->attributes[$key] = $value;
                    $_SESSION[$key] = $value;
                }
                $this->attributes['trace'] = time();
                $this->attributes['hash'] = $this->getTraceHash($this->id);
                $_SESSION['trace'] = $this->attributes['trace'];
                $_SESSION['hash'] = $this->attributes['hash'];

                $userUpdate = array(
                    'logged' => date(SqlGateway::MYSQL_DATETIME_FORMAT, time()),
                );
                $db->updateRow('users', $userUpdate, '`id` = "' . $info['id'] . '"');
                return true;
            }

            return false;
        }

        public function logout($error = false)
        {
            $data = array();
            foreach ($this->attributes as $key => $value) {
                if (substr($key, 0, 1) == '_') {
                    $data[$key] = $value;
                }
            }

            $this->attributes = array();

            foreach ($data as $key => $value) {
                $this->attributes[$key] = $value;
            }

            $_SESSION = $this->attributes;

            $this->logout_reason = $error;
            return $error;
        }

        private function getTraceHash($user_id)
        {
            $params = array(
                filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'),
                filter_input(INPUT_SERVER, 'REMOTE_ADDR'),
            );
            return sha1(json_encode($params) . $user_id);
        }

    }

}