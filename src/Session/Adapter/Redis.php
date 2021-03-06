<?php

/** $Id$
 * Redis.php
 * @version 1.0.0, $Revision$
 * @package eroticam.pl
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Session\Adapter {

    use Core\Cache\Shared\RedisInterface;
    use \Core\Session\AbstractSessionHandler;
    use \Core\Events\EventManager;
    use \SessionHandlerInterface;

    class Redis extends AbstractSessionHandler implements SessionHandlerInterface
    {

        private $dbNumber = 0;
        private $ttl = 0;
        private $prefix = '';

        /** @var \Core\Cache\Shared\Redis */
        private $redis = NULL;

        public function __construct(\Core\Cache\Shared\Redis $redis, $database = 0, $ttl = 1800, $prefix = 'session:')
        {
            # Redis instance
            $this->redis = $redis;

            # Redis database number
            $this->dbNumber = $database;

            # Data prefix in Redis memory
            $this->prefix = $prefix;

            # Session expire time
            $this->ttl = intval($ttl);

            # Configure session handler
            parent::__construct();
        }

        /** Read session data, returns an encoded string of the read data.
         *
         * @param string $sessionId
         * @return string
         */
        public function read($sessionId)
        {
            # Dispatch event
            EventManager::dispatchEvent(self::EVENT_SESSION_READ);

            # Data key-name in redis database
            $sessionId = $this->prefix . $sessionId;

            # Enter transactional mode, once in multi-mode, all subsequent method calls return the same object.
            $multi = $this->redis->multi(\Redis::PIPELINE);

            # Select memory bank for session data.
            $multi->select($this->dbNumber);

            # Fetch all session data.
            $multi->hGetAll($sessionId);

            # Execute multi-mode commands.
            $exec = $multi->exec();

            # Get result of last command (hGetAll).
            $_SESSION = \end($exec);

            # If hGetAll fail
            if (!is_array($_SESSION)) $_SESSION = array();

            # Decode objects and arrays
            if(isset($_SESSION['__session_objects_list'])){
                $objects = explode(',', $_SESSION['__session_objects_list']);
                unset($_SESSION['__session_objects_list']);

                foreach ($objects as $name){
                    $_SESSION[$name] = unserialize($_SESSION[$name]);
                }
            }

            # Encodes the current session data as a string.
            return session_encode();
        }

        /** Write session data
         *
         * @param string $sessionId
         * @param string $data
         * @return void|bool
         */
        public function write($sessionId, $data)
        {
            # Dispatch event
            EventManager::dispatchEvent(self::EVENT_SESSION_WRITE);

            # Data key-name in redis database
            $sessionId = $this->prefix . $sessionId;

            # Select memory bank for session data.
            $this->redis->select($this->dbNumber);

            $objects = array();
            $data = array();

            # Encode object and arrays
            foreach ($_SESSION as $key => $value) {
                if (is_array($value) || is_object($value)){
                    $objects[]  = $key;
                    $data[$key] = serialize($value);
                } else {
                    $data[$key] = (string)strval($value);
                }
            }

            if(count($objects) > 0){
                $data['__session_objects_list'] = implode(',', $objects);
            }

            # Enter transactional mode, once in multi-mode, all subsequent method calls return the same object.
            /** @var RedisInterface $multi */
            $multi = $this->redis->multi(\Redis::MULTI);

            # delete Session ID
            $multi->delete($sessionId);

            # Save data
            $multi->hMSet($sessionId, $data);

            # Update expire time (TTL)
            $multi->expire($sessionId, $this->ttl);

            # Execute multi-mode commands.
            $multi->exec();

            return true;
        }

        /** Destroy a session
         *
         * @param string $sessionId
         * @return void
         */
        public function destroy($sessionId)
        {

            # Select memory bank for session data.
            $this->redis->select($this->dbNumber);

            # Remove specified keys.
            $this->redis->del($this->prefix . $sessionId);
        }

        /** Cleanup old sessions
         * @param int $maxlifetime
         * @return bool
         */
        public function cleanup($maxlifetime)
        {
            # Method is unused since Redis data have internal TTL time.
            return true;
        }

        /** Close the session
         * @return bool
         */
        public function close()
        {
            # Dispatch event
            EventManager::dispatchEvent(self::EVENT_SESSION_CLOSE);

            return true;
        }

    }

}