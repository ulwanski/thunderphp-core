<?php
/** $Id$
 * Checked.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Validators;

use \Core\Form\abstractFormElement as Form;
use \Core\Form\abstractValidator;

class Checked extends abstractValidator {

    CONST ERROR_INVALID_CHECKBOX = 'validator_checked_checkbox';

    /**
     * @param $value
     * @return bool|void
     */
    public function isValid($value)
    {
        # Checkbox validation
        if ($value != "on") $this->addError(self::ERROR_INVALID_CHECKBOX);

        return ($this->isErrors())?Form::VALIDATION_BREAK_CHAIN:true;
    }

}