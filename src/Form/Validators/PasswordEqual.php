<?php
/** $Id$
 * PasswordEqual.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Validators;

use \Core\Form\abstractFormElement as Form;
use \Core\Form\abstractValidator;

class PasswordEqual extends abstractValidator {

    CONST ERROR_PASS_EQUAL = 'validator_password_not_equal';

    protected $equal = null;

    public function __construct($equal)
    {
        $this->equal = $equal;
    }

    /**
     * @param $value
     * @return bool|void
     */
    public function isValid($value)
    {
        # Regular expression validation
        if ($value != $this->equal) $this->addError(self::ERROR_PASS_EQUAL);

        return ($this->isErrors())?Form::VALIDATION_BREAK_CHAIN:true;
    }

}