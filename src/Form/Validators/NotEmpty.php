<?php
/** $Id$
 * NotEmpty.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Validators;

use \Core\Form\abstractFormElement as Form;
use \Core\Form\abstractValidator;

class NotEmpty extends abstractValidator {

    CONST ERROR_INVALID_EMPTY = 'validator_not_empty_invalid';

    /**
     * @param $value
     * @return bool|void
     */
    public function isValid($value)
    {
        # Not empty validation
        if(strlen($value) == 0) $this->addError(self::ERROR_INVALID_EMPTY);

        return ($this->isErrors())?Form::VALIDATION_BREAK_CHAIN:true;
    }

}