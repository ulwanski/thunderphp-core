<?php
/** $Id$
 * Multiple.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Elements;

use \Core\Form\abstractFormElement;

class Multiple extends abstractFormElement {

    protected $options = array();

    public function renderElement(){

        $optionsString = '';
        foreach ($this->options as $option => $label){
            $selected = $option == $this->elementValue || (is_array($this->elementValue) && in_array($option, $this->elementValue));
            if($selected){
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
        '<select multiple="multiple" class="'.$this->getElementClass().'" name="'.$this->elementName.'"'.$id.'>'.
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