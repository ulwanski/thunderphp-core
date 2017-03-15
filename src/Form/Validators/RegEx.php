<?php
/** $Id$
 * RegEx.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Validators;

use \Core\Form\abstractFormElement as Form;
use \Core\Form\abstractValidator;

class RegEx extends abstractValidator {

    CONST ERROR_INVALID_REGEX = 'validator_regex_invalid';

    protected $regex = null;

    public function __construct($regex)
    {
        $this->regex = trim($regex);
    }

    /**
     * @param $value
     * @return bool|void
     */
    public function isValid($value)
    {
        # Regular expression validation
        if (!preg_match($this->regex, $value)) $this->addError(self::ERROR_INVALID_REGEX);

        return ($this->isErrors())?Form::VALIDATION_BREAK_CHAIN:true;
    }

}