<?php
/** $Id$
 * Select.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Elements;

use \Core\Form\abstractFormElement;

class Select extends abstractFormElement {

    protected $options = array();

    public function renderElement(){

        $optionsString = '';
        foreach ($this->options as $option => $label){
            if($option == $this->elementValue){
                $optionsString .= '<option value="'.$option.'" selected="selected">'.$label.'</option>';
            } else {
                $optionsString .= '<option value="'.$option.'">'.$label.'</option>';
            }
        }

        if($this->elementId){
            $id = ' id="'.$this->elementId.'"';
        } else {
            $id = '';
        }

        $out = '<div class="form-title">'.$this->getElementLabel().'</div>'.
        '<select class="'.$this->getElementClass().'" name="'.$this->elementName.'"'.$id.'>'.
            $optionsString.
        '</select>';
        return $out;
    }

    /**
     * @param $name
     * @param $label
     */
    public function addOption($name, $label){
        $this->options[$name] =  $label;
    }

}