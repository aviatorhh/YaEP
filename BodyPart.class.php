<?php
/*
 * Copyright 2015 Nicholas John Koch (njk@pilot.hamburg)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
 
/*
 * BodyPart represents a data object for working with the email content body parts.
 */
class BodyPart {
    
    private $header = array();			// array holding the header fields as key=>value
    private $body = NULL;				// body content, should be of type string mainly
    
    
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
    
    /*
     * Helper method to get header information easy
     */
    public function getHead($key = '') {
    	if (array_key_exists($key, $this->header)) {
    		return $this->header[$key];
    	} else {
    		return NULL;
    	}
    }

}
