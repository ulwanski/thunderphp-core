<?php
/** $Id$
 * Email.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Elements;

use \Core\Form\abstractFormElement;
use \Core\Form\Validators\Email as EmailValidator;
use \Core\Form\Validators\NotEmpty;

class Email extends abstractFormElement {

    public function __construct($elementLabel, $elementName = null, $elementClass = null)
    {
        parent::__construct($elementLabel, $elementName, $elementClass);

        $this->elementType = 'email';

        $this->addValidator(new NotEmpty());
        $this->addValidator(new EmailValidator());
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

    public function renderElement()
    {
        $out = '<div class="form-title">'.$this->getElementLabel().'</div>'.
        '<input class="'.$this->getElementClass().'" type="'.$this->getElementType().'" name="'.$this->elementName.'" value="'.$this->getElementValue().'">';
        if($this->isErrors()) $out .= $this->renderErrors();
        return $out;
    }

}