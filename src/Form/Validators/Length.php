<?php
/** $Id$
 * Length.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Validators;

use \Core\Form\abstractValidator;

class Length extends abstractValidator {

    CONST ERROR_INVALID_LENGTH_MIN = 'validator_length_min_invalid';
    CONST ERROR_INVALID_LENGTH_MAX = 'validator_length_max_invalid';

    protected $minLength = null;
    protected $maxLength = null;

    public function __construct($min = null, $max = null)
    {
        $this->minLength = $min;
        $this->maxLength = $max;
    }

    /**
     * @param $value
     * @return bool|void
     */
    public function isValid($value)
    {
        # Min length validation
        if($this->minLength !== null){
            if(strlen($value) < $this->minLength) $this->addError(self::ERROR_INVALID_LENGTH_MIN.','.$this->minLength);
        }

        # Max length validation
        if($this->maxLength !== null){
            if(strlen($value) > $this->maxLength) $this->addError(self::ERROR_INVALID_LENGTH_MAX.','.$this->maxLength);
        }

        return (bool)!$this->isErrors();
    }

}