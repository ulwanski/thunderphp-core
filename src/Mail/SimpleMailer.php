<?php

/** $Id$
 * SimpleMailer.php
 * @version 1.0.0, $Revision$
 * @package eroticam.pl
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Mail {
    
    class SimpleMailer extends AbstractMail {

        /**
         * Constructor.
         * @param $from
         * @param $fromName
         * @param string $charSet
         */
        public function __construct($from, $fromName, $charSet = 'UTF-8'){
            parent::__construct($from, $fromName, $charSet);
        }
        
        public function addReceiver($email, $name = null){
            $this->add_to($email, $name);
        }
        
        public function setSubject($subject){
            $this->subject = $subject;
        }
        
        public function setContent($content){
            $this->content = trim($content);
        }
        
        public function send() {
            
            $headers = array();
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=utf-8';
            
            $to = array();
            foreach($this->to as $email => $name){
                $to[] = $name.' <'.$email.'>';
            }
            
            $cc = array();
            foreach($this->cc as $email => $name){
                $cc[] = $name.' <'.$email.'>';
            }
            if(count($cc)) $headers[] = 'Cc: '.  implode(', ', $cc);

            $bcc = array();
            foreach($this->bcc as $email => $name){
                $bcc[] = $name.' <'.$email.'>';
            }
            if(count($bcc)) $headers[] = 'Bcc: '.  implode(', ', $bcc);
            
            if($this->From) $headers[] = 'From: '.$this->From;
            $result = mail( implode(', ', $to), trim($this->subject), trim($this->content), implode("\r\n", $headers) );
            
            return $result;
        }

    }
    
}