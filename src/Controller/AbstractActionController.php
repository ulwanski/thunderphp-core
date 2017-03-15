<?php

/** $Id$
 * AbstractActionController.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Controller {
    
    use \Api;
    use \Core\Controller\AbstractConsoleController;
    
    abstract class AbstractActionController extends AbstractConsoleController implements BasicActionController {
    
        public function __construct() {
            parent::__construct();
        }

    }
    
    interface BasicActionController {
        
        public function defaultAction();
        
    }
    
}