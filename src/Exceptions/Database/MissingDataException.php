<?php

/** $Id$
 * MissingDataException.php
 *
 * @version 1.0.0, $Revision$
 * @package Core\Exceptions
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2016, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Exceptions\Database;

class MissingDataException extends \Exception {

    public function __construct($code = 0x00, \Exception $previous = null) {

        # Create a simple error message
        $message = 'Required ID not found.';

        # Push the exception further
        parent::__construct($message, $code, $previous);
    }

}