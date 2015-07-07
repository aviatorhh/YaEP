<?php

class BodyPart {
    
    private $header = array();
    private $body = NULL;
    
    
    public function __construct() { }
    
    public function getBody() {
        return $this->body;
    }
    
    public function setBody($body) {
        $this->body = $body;
    }
    
    public function getContentType() {
        return $this->header['content-type'];
    }
    
    public function getHeader() {
        return $this->header;
    }
    
     public function setHeader($header) {
        $this->header = $header;
    }
    
    public function getHead($key = '') {
        return $this->header[$key];
    }

}
