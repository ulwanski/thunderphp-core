<?php

/** $Id$
 * JsonView.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\View;

class JsonView extends AbstractView {

    # Status is send as request acknowledgement when no additional data is required
    const STATUS_OK    = 'ok';

    # Status is send as success response for data request
    const STATUS_DATA  = 'data';

    # Status is send when request error is occurred
    const STATUS_ERROR = 'error';
    
    # Stores response status (ok, data, error)
    protected $status = null;

    # Stores response code
    protected $code = 0;

    # Stores data to send in response if any
    protected $data = null;

    # If errors occurred this stores error tag, for client site to analise
    protected $error = null;

    # Stores message for user, if this variable is not empty its content should be presented for user
    protected $message = null;
    
    public function __construct() {
        parent::__construct();

        $this->code = 0;
        $this->codeBuffer = null;
        $this->status = self::STATUS_OK;
    }
    
    public function __toString() {
        return (string)json_encode($this->__toArray());
    }
    
    public function __toArray() {
        $time = time();
        return array(
            'status'    => $this->status,
            'code'      => (int)$this->codeBuffer,
            'data'      => $this->data,
            'error'     => $this->error,
            'message'   => $this->message,
            'time'      => $time,
            'md5'       => md5($time.$this->status.$this->codeBuffer),
        );
    }

    public function cleanView() {
        $this->codeBuffer   = null;
        $this->data         = null;
        $this->error        = null;
        $this->status       = self::STATUS_OK;
        $this->message      = null;
    }

    public function parseView() {
        return (string)$this->__toString();
    }

    public function prepareView() {
    }

    public function serialize() {
        return (string)$this->__toString();
    }

    public function unserialize($serialized) {
        $data = json_decode($serialized);
        $this->codeBuffer     = $data['code'];
        $this->data     = $data['data'];
        $this->error    = $data['error'];
        $this->message  = $data['message'];
        $this->status   = $data['status'];
    }
    
    public function setupSuccessResponse($code = 0, $message = null) {
        $this->status = self::STATUS_OK;
        $this->code   = (int)$code;
        $this->data   = null;
        $this->error  = null;

        if($message) $this->setMessage($message);
    }

    public function setupErrorResponse($code = 0, $error = "", $message = null){
        $this->status = self::STATUS_ERROR;
        $this->code   = (int)$code;
        $this->data   = null;
        $this->error  = (string)trim($error);

        if($message) $this->setMessage($message);
    }

    public function setupDataResponse($data, $code = 0, $message = null){
        $this->status = self::STATUS_DATA;
        $this->code   = (int)$code;
        $this->error  = null;
        $this->data   = $data;

        if($message) $this->setMessage($message);
    }

    protected function setMessage($msg = null) {
        $this->message = ($msg == null)?null:trim($msg);
    }

    public function isError() {
        return (bool)($this->error !== null || $this->status == self::STATUS_ERROR);
    }

}