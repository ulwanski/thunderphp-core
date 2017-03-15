<?php
/** $Id$
 * abstractForm.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form;

abstract class abstractForm {

    protected $formData = array();
    private $actionUrl;
    private $formId;
    private $formClass;
    private $formArray;

    public function __construct($actionUrl = null)
    {
        $this->actionUrl = $actionUrl;
        $this->formArray = array();
    }

    /**
     * @param $element
     */
    public function addFormElement(abstractFormElement $element)
    {
        $elementName = $element->getElementName();
        $this->formArray[$elementName] = $element;
    }

    /**
     * @return array
     */
    public function getFormElements()
    {
        return $this->formArray;
    }

    /**
     * @param $elementName
     * @return abstractFormElement|null
     */
    public function getFormElement($elementName)
    {
        if(!isset($this->formArray[$elementName])) return null;
        return $this->formArray[$elementName];
    }

    /**
     * @param null $actionUrl
     */
    public function setActionUrl($actionUrl = null)
    {
        $this->actionUrl = $actionUrl;
    }

    /**
     * @return mixed
     */
    public function getActionUrl()
    {
        return $this->actionUrl;
    }

    /**
     * @return mixed
     */
    public function getFormId()
    {
        return $this->formId;
    }

    /**
     * @param mixed $formId
     */
    public function setFormId($formId)
    {
        $this->formId = $formId;
    }

    /**
     * @return mixed
     */
    public function getFormClass()
    {
        return $this->formClass;
    }

    /**
     * @param mixed $formClass
     */
    public function setFormClass($formClass)
    {
        $this->formClass = $formClass;
    }

    public function setData($formData)
    {
        if(!is_array($formData)) return $this;

        /** @var abstractFormElement $element */
        foreach($this->getFormElements() as $element){
            $name = $element->getElementName();
            $name = str_replace('[]', '', $name);
            if(isset($formData[$name])){
                $element->setElementValue($formData[$name]);
            }
        }

        return $this;
    }

    public function isValid()
    {
        $isValid = true;

        /** @var abstractFormElement $element */
        foreach($this->getFormElements() as $element){
            $result = $element->isValid();

            if($result == false) $isValid = false;
        }

        return $isValid;
    }

    public function renderForm()
    {
        $formOut = '<form method="post" action="'.$this->actionUrl.'"';
        if($this->formClass != null) $formOut .= ' class="'.$this->formClass.'"';
        if($this->formId != null)    $formOut .= ' id="'.$this->formId.'"';
        $formOut .= '>';

        /** @var abstractFormElement $item */
        foreach($this->formArray as $item){
            $formOut .= $item->renderElement().'<br>';
        }
        $formOut .= '</form>';

        return $formOut;
    }

}