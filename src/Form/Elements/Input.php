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

class Input extends abstractFormElement {
    
    public function renderElement()
    {
        $out = '<div class="form-title">'.$this->getElementLabel().'</div>'.
        '<input class="'.$this->getElementClass().'" type="'.$this->getElementType().'" name="'.$this->elementName.'" value="'.$this->getElementValue().'">';
        if($this->isErrors()) $out .= $this->renderErrors();
        return $out;
    }

}