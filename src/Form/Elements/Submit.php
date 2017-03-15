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

class Submit extends abstractFormElement {

    public function __construct($elementLabel, $elementName = null, $elementClass = null)
    {
        parent::__construct($elementLabel, $elementName, $elementClass);

        $this->elementType = 'submit';
    }

    public function renderElement(){
        $out = '<button class="'.$this->getElementClass().'" type="'.$this->getElementType().'">'.$this->getElementLabel().'</button>';
        return $out;
    }

}