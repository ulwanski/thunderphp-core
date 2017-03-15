<?php
/** $Id$
 * abstractForm.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Elements;

use \Core\Form\abstractFormElement;

class Date extends abstractFormElement {

    public function __construct($elementLabel, $elementName, $elementClass)
    {
        parent::__construct($elementLabel, $elementName, $elementClass);

        $this->elementType = 'input';
    }

    public function renderElement(){

        if($this->elementId == null) $this->elementId = 'dateInput_'.$this->elementName;

        $out = '<div class="form-title">'.$this->getElementLabel().'</div>'.
        '<input class="'.$this->getElementClass().'" type="'.$this->getElementType().'" id="'.$this->elementId.'" name="'.$this->elementName.'" value="'.$this->elementValue.'">';
        if($this->isErrors()) $out .= $this->renderErrors();
        $out .= '<script>jQuery(function(){ jQuery( "#'.$this->elementId.'" ).datepicker({ changeMonth: true, changeYear: true, yearRange: "'.(date('Y')-100).':'.date('Y').'" }); }); </script>';
        return $out;
    }

}