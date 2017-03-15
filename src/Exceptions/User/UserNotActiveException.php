<?php

/** $Id$
 * UserNotActiveException.php
 *
 * @version 1.0.0, $Revision$
 * @package Core\Exceptions
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2016, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Exceptions\User;

class UserNotActiveException extends \Exception {

    public function __construct($code = 0x00, \Exception $previous = null) {

        # Create a simple error message
        $message = 'User account is not active.';

        # Push the exception further
        parent::__construct($message, $code, $previous);
    }

}