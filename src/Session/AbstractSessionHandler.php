<?php

/** $Id$
 * AbstractSessionHandler.php
 * @version 1.0.0, $Revision$
 * @package eroticam.pl
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Session {

    use \SplSubject;
    use \Exception;
    use \SessionHandler;
    use \SessionHandlerInterface;
    
    abstract class AbstractSessionHandler extends SessionHandler implements SessionHandlerInterface {

        const EVENT_SESSION_WRITE = 'event_session_write';
        const EVENT_SESSION_READ  = 'event_session_read';
        const EVENT_SESSION_CLOSE = 'event_session_close';

        public function __construct(){

            $result = session_set_save_handler($this, false);

            /* This function is registered itself as a shutdown function by
             * session_set_save_handler($obj). The reason we now register another
             * shutdown function is in case the user registered their own shutdown
             * function after calling session_set_save_handler(), which expects
             * the session still to be available.
             */
            session_register_shutdown();

            if($result == false){
                throw new Exception("Fail to sets user-level session storage functions.");
            }

            session_start();
        }

        public function __destruct() {
        }
        
        protected function sessionCommit(){
            session_write_close();
        }
        
        public function __set($name, $value) {
            $_SESSION[$name] = $value;
        }
        
        public function __get($name) {
            return $_SESSION[$name];
        }

    }
}