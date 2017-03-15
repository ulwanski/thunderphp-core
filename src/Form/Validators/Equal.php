<?php
/** $Id$
 * Equal.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Validators;

use \Core\Form\abstractFormElement as Form;
use \Core\Form\abstractValidator;

class Equal extends abstractValidator {

    CONST ERROR_NOT_EQUAL = 'validator_equal_not_equal';

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
        if ($value != $this->equal) $this->addError(self::ERROR_NOT_EQUAL);

        return ($this->isErrors())?Form::VALIDATION_BREAK_CHAIN:true;
    }

}