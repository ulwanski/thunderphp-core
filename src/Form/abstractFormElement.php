<?php
/** $Id$
 * abstractForm.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form;

abstract class abstractFormElement {

    const VALIDATION_BREAK_CHAIN = -1;

    protected $elementName = null;
    protected $elementType = null;
    protected $elementClass = null;
    protected $elementId = null;
    protected $elementLabel = null;
    protected $elementValue = null;
    protected $validators = array();
    protected $errors = array();

    public function __construct($elementLabel, $elementName = null, $elementClass = null)
    {
        $this->elementLabel = $elementLabel;
        $this->elementName  = $elementName;
        $this->elementClass = $elementClass;
        $this->elementType  = 'text';
    }

    /**
     * @return mixed
     */
    abstract public function renderElement();

    /**
     * @param \Core\Form\abstractValidator $validator
     * @return $this
     */
    public function addValidator(abstractValidator $validator)
    {
        $this->validators[] = $validator;
        return $this;
    }

    /**
     * @return $this
     */
    public function removeValidators()
    {
        $this->validators = array();
        return $this;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $isValid = true;

        /** @var \Core\Form\abstractValidator $validator */
        foreach($this->validators as $validator){

            $value = $this->getElementValue();

            $result = $validator->isValid($value);
            if($result == false || $result === self::VALIDATION_BREAK_CHAIN){
                $isValid = false;
                $this->errors = array_merge($this->errors, $validator->getErrors());
            }

            if($result === self::VALIDATION_BREAK_CHAIN) break;
        }

        if($this->isErrors()) $isValid = false;

        return $isValid;
    }

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
    public function addError($errorString)
    {
        $this->errors[] = $errorString;
    }

    /**
     * @return bool
     */
    public function isErrors()
    {
        return (bool)count($this->errors);
    }

    /**
     * @return $this
     */
    public function cleanErrors()
    {
        $this->errors = array();
        return $this;
    }
    
    /**
     * @return null
     */
    public function getElementName()
    {
        return $this->elementName;
    }

    /**
     * @param null $elementName
     * @return $this
     */
    public function setElementName($elementName)
    {
        $this->elementName = $elementName;
        return $this;
    }

    /**
     * @return null
     */
    public function getElementClass()
    {
        return $this->elementClass;
    }

    /**
     * @param null $elementClass
     * @return $this
     */
    public function setElementClass($elementClass)
    {
        $this->elementClass = $elementClass;
        return $this;
    }

    /**
     * @return null
     */
    public function getElementId()
    {
        return $this->elementId;
    }

    /**
     * @param null $elementId
     * @return $this
     */
    public function setElementId($elementId)
    {
        $this->elementId = $elementId;
        return $this;
    }

    /**
     * @return null
     */
    public function getElementLabel()
    {
        return $this->elementLabel;
    }

    /**
     * @param null $elementLabel
     * @return $this
     */
    public function setElementLabel($elementLabel)
    {
        $this->elementId = $elementLabel;
        return $this;
    }

    /**
     * @return string
     */
    public function getElementType()
    {
        return $this->elementType;
    }

    /**
     * @param string $elementType
     * @return $this
     */
    public function setElementType($elementType)
    {
        $this->elementType = $elementType;
        return $this;
    }

    /**
     * @return null
     */
    public function getElementValue()
    {
        return $this->elementValue;
    }

    /**
     * @param null $elementValue
     * @return abstractFormElement
     */
    public function setElementValue($elementValue)
    {
        $this->elementValue = $elementValue;
        return $this;
    }

    protected function renderErrors()
    {
        $out = '<div class="form-errors">';
        foreach($this->getErrors() as $error){
            $out .= '<span class="error">{$'.$error.'}</span>';
        }
        $out .= '</div>';

        return $out;
    }

}