<?php

/** $Id$
 * abstractController.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Controller;
    
use \Api;
use \Core\Session\SessionGateway;

abstract class abstractController implements abstractControllerInterface {

    /** @var SessionGateway */
    private $sessionGateway = null;

    /** @var array */
    private $coreConfig = null;

    public function __construct(){

        # Fetch core configuration
        $this->coreConfig = Api::getConfig()->getCoreConfig();

        # Configure user session
        $this->sessionGateway = new SessionGateway($this->coreConfig['session']['handler']);
    }

    /**
     * @return SessionGateway
     */
    protected function getSession(){
        return $this->sessionGateway;
    }

    /**
     * @return array
     */
    protected function getConfig(){
        return $this->coreConfig;
    }

}