<?php
/** $Id$
 * abstractForm.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form;

abstract class abstractValidator {

    /** @var array */
    protected $errors = array();

    /**
     * @param $value
     * @return bool
     */
    abstract public function isValid($value);

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $errorString
     */
    protected function addError($errorString)
    {
        $this->errors[] = $errorString;
    }

    /**
     * @return bool
     */
    protected function isErrors()
    {
        return (bool)count($this->errors);
    }

    /**
     * @return $this
     */
    protected function cleanErrors()
    {
        $this->errors = array();
        return $this;
    }

}