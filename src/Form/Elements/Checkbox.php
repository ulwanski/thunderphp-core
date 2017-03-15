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

class Checkbox extends abstractFormElement {

    public function __construct($elementLabel, $elementName, $elementClass = null)
    {
        parent::__construct($elementLabel, $elementName, $elementClass);

        $this->elementType = 'checkbox';
    }

    // <input type="checkbox"><div class="form-title inline">{$lang_accept_terms_of_use,{$terms_link}}</div>
    public function renderElement(){
        $checked = ($this->elementValue == "on")?' checked="checked"':'';
        $out = '<input class="'.$this->getElementClass().'" type="'.$this->getElementType().'" name="'.$this->elementName.'"'.$checked.'>'.
            '<div class="form-title inline">'.$this->getElementLabel().'</div>';
        if($this->isErrors()) $out .= $this->renderErrors();
        return $out;
    }

}