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

class Password extends abstractFormElement {

    public function __construct($elementLabel, $elementName, $elementClass)
    {
        parent::__construct($elementLabel, $elementName, $elementClass);

        $this->elementType = 'password';
    }

    public function renderElement(){
        $out = '<div class="form-title">'.$this->getElementLabel().'</div>'.
        '<input class="'.$this->getElementClass().'" type="'.$this->getElementType().'" name="'.$this->elementName.'">';
        if($this->isErrors()) $out .= $this->renderErrors();
        return $out;
    }

}