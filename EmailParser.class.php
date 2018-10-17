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
 
require_once ('BodyPart.class.php');
require_once ('Attachement.class.php');
/*
 * version 1.0
 * This class is the main class for creating objects that parse emails. It crawls
 * recursively through the email content and extracts the body parts.
 */
class EmailParser {
    
    const DEBUG = FALSE;				// set TRUE to see some debugging information
    const TRACE = FALSE;				// set TRUE for a lot of information ;-)

    private $emailRaw = array();		// has the raw email data line by line
    
    private $headerLines = array();		// holds the main header lines of the email
    private $bodyLines = array();		// all the email body content
    
    private $contentIds = array();      // HTML email often uses these IDs to identify inine objects
    private $contentDispos = array();   // type identifying attachements
    
    private $attachements = array();	// array holding the extracted attachements
    
    
    private $allBodyParts = array();	// every body that has been found is stored in this array as
    									// a BodyPart object
    
    public $validHeaderKeys = array(	// only these keys are valid to be parsed 
			'subject',
			'content-type',
			'content-id',
			'content-disposition',
			'to',
			'from',
			'content-transfer-encoding',
			'date',
			'received',
			'x-spam-score',
			''
        ); 
        
    public function  __construct($emailRaw) {
    	// Make the email an array
        $this->emailRaw = preg_split ('/$\R?^/m', $emailRaw);
        
        // Major email object
        $mainBodyPart = new BodyPart();

        // Extract all the header information
        $this->headerLines = $this->parseHeader($this->emailRaw);
        
        // and store them in the major object
        $mainBodyPart->setHeader($this->headerLines);
      
        // first level of body part extraction
        $bodies = self::parseBody($this->emailRaw, $this->headerLines['content-type']);
        
        // and storage in main object
        $mainBodyPart->setBody($bodies);
        
        // Routine for the recursive extraction work
        if (sizeof($bodies) > 1) {
        	// Must be a multipart at this point because we found more than one bodies
        	foreach ( $bodies as $body) {
        		$this->dig($body);
        	}
        } else {
        	array_push ($this->allBodyParts, $mainBodyPart);
        }
        
    }
    
    /*
     * Recursive working method
     * It takes the body part as a string and keeps digging if necessary
     */
    private function dig($bodyPart) {
    	$bodyLines = explode(PHP_EOL, trim($bodyPart));
		$headerLines = $this->parseHeader($bodyLines);
		
		if (array_key_exists('content-type', $headerLines)) {
			$bp = new BodyPart();
			$_bodies = self::parseBody($bodyLines, $headerLines['content-type']);
			$bp->setBody($_bodies);
					
			if (sizeof($_bodies) > 1) {
				foreach ( $_bodies as $_body) {
					self::dig($_body);
				}
			} else {			
				$bp->setHeader($headerLines);
				
				// Is it a attachement?
				if (array_key_exists('content-disposition', $headerLines)) {
					array_push ($this->contentDispos, trim($headerLines['content-disposition']));
					$a = new Attachement();
					
					preg_match('/.*[;!].*filename=(.*?)[;!]/', $headerLines['content-disposition'] , $matches);
					$filename = str_replace(array("'", '"'), '', trim($matches[1]));
					
					$a->setFilename($filename);
					$a->setContent(self::makeBody($bp));
					$a->setContentType($headerLines['content-type']);
					
					array_push ($this->attachements, $a);
				} else
				// or just a embedded object?
				if (array_key_exists('content-id', $headerLines)) {
					array_push ($this->contentIds, trim($headerLines['content-id']));
					
					$a = new Attachement();
					
					// Create the filename from the cid:
					$filename = self::sanitizeFileName($headerLines['content-id']);
					
					// The content type string often holds more data than just the mime type
					$ct = $headerLines['content-type'];
					if (strpos($ct, ';')) {
						$_ct = explode(';', $ct);
						$filename .= '.' . explode('/', $_ct[0])[1];
					} else {
						$filename .= '.' . explode('/', $ct)[1];
					}
					
					if (self::DEBUG || self::TRACE) printf( "[" . date(DATE_RFC822) . "] Filename is: %s" . PHP_EOL , $filename);
					
					$a->setFilename($filename);
					$a->setContent(self::makeBody($bp));
					$a->setContentType($ct);
					
					array_push ($this->attachements, $a);
				}  
				array_push ($this->allBodyParts, $bp);
			}	
		}
    }
    
    /*
     * This method creates usable content from the body part object
     */
    private function makeBody($bp) {
    	$ret = '';	// return variable
        
        // Now we parse the body part
        $cte = '';
        
		if (array_key_exists('content-transfer-encoding', $bp->getHeader())) {
			$cte = $bp->getHead('content-transfer-encoding');
			if (self::DEBUG || self::TRACE) printf( "[" . date(DATE_RFC822) . "] Encoding is: %s" . PHP_EOL , $cte);
		}
		
		foreach($bp->getBody() as $bodyLine) {
			$ret .= $bodyLine . PHP_EOL;
		}
		if ($cte === 'base64') {
			$ret = base64_decode(trim($ret));
		} else if ($cte === 'quoted-printable') {
			$ret = quoted_printable_decode(trim($ret));
		}
		// The explode function leaves some dashes behind. We might need to improve this issue
		if (substr(trim($ret), -2) == '--') {
			$ret = substr(trim($ret), 0, -3);
		}
        
        return $ret;
    }
    
    /*
     * Provide the data according to the content type like 'text/plain'
     */
    public function getBody($contentType) {
    	$bp = NULL;
    	foreach ($this->allBodyParts as $_bp) {
        	if (0 === strpos($_bp->getContentType(), $contentType)) {
        		$bp = $_bp;
        		break;
        	}
        }
        
        if ($bp === NULL) return '';
    	
    	return self::makeBody($bp);
    	
        
    }
    
    /*
     * Helper method to determine an empty line
     */
    public function isEmptyLine($line) {
        if ($line == "\n" || $line == "\r" || $line == "\r\n" || empty($line)) {
             return TRUE;
        }
        return FALSE;
    }
    
    
    private function parseBody($body, $contentType) {
    	$ret = array();
    	$tmp = '';
        if (empty($body) || sizeof($body) === 0) {
            throw new Exception('No mail found to parse.');
        }
        $skipHead = TRUE;
        foreach ($body as $bodyLine) {
           
            if ($skipHead && self::isEmptyLine($bodyLine)) {
                $skipHead = FALSE;
                continue;
            } else if ($skipHead) {
                continue;
            }  
            if (self::TRACE) printf( "[" . date(DATE_RFC822) . "] Body Line: %s" . PHP_EOL , $bodyLine);
            $tmp .= $bodyLine . PHP_EOL;
        }
        
        if (0 === strpos($contentType, 'multipart')) {
        	preg_match('!boundary=(.*)$!mi', $contentType , $matches);
            $boundary = str_replace(array("'", '"'), '', trim($matches[1]));
            if (self::DEBUG) printf( "[" . date(DATE_RFC822) . "] Boundary: %s" . PHP_EOL , $boundary);
            $ret = explode ($boundary, trim($tmp));
        } else {
        	$ret = array($tmp);
        }
        
        return $ret;
    }
    
    /*
     * Extract the header information
     */
    private function parseHeader($lines) {
        $headerLines = array();
        $previousLine = '';
        $append = FALSE;
        
        foreach ($lines as $headLine) {
            if (self::TRACE) printf( "[" . date(DATE_RFC822) . "] Header Line: %s" . PHP_EOL , $headLine);
            // End of head?
            /*if (preg_match('/$\R?^/m', $headLine, $matches, PREG_OFFSET_CAPTURE)) {
                if ($matches[0][1] === 0) {
                    break;   
                }
            }
            */
            if (self::isEmptyLine($headLine)) {
                if (self::DEBUG || self::TRACE) print( "[" . date(DATE_RFC822) . "] ### End of Header Lines. ###" . PHP_EOL);
                break;
            }
            
            if ($append) {
                $headLine = trim($previousLine) . $headLine;
                if (self::DEBUG || self::TRACE) printf( "[" . date(DATE_RFC822) . "] Appending to: %s" . PHP_EOL , $headLine);
                $append = FALSE;
            }  
            
            // The next line may be appended to the previous
            if (substr(trim($headLine), -1) == ';') {
                if (self::DEBUG || self::TRACE) printf( "[" . date(DATE_RFC822) . "] Found an appendable line: %s" . PHP_EOL , $headLine);
                $previousLine = $headLine;
                $append = TRUE;
                continue;
            }
            
            
            if (0 === strpos($headLine, ' ')) {
                $headLine = $previousLine . $headLine;
                $previousLine = $headLine;
                continue;
            }
            
            $previousLine = $headLine;
                
            
            
            // If has a ':' it is a head line
            preg_match('/([^:]+): ?(.*)$/', $headLine, $matches);
            if (sizeof($matches) > 1) {
                
                $headerKey = strtolower(trim($matches[1]));
                if (in_array($headerKey, $this->validHeaderKeys)) {
                    $headerValue = trim($matches[2]);
                    $headerLines[$headerKey] = $headerValue;
                    $previousKey = $headerKey;
                    if (self::DEBUG || self::TRACE) printf( "[" . date(DATE_RFC822) . "] Found header: %s: %s" . PHP_EOL , $headerKey, $headerValue);
                }
                
                
            }
        }
        
        return $headerLines;
    }
    
    /*
     * Make it more usable for an os
     */
	function sanitizeFileName( $filename ) {
		$filename_raw = $filename;
		$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
		$filename = str_replace($special_chars, '', $filename);
		$filename = preg_replace('/[\s-]+/', '-', $filename);
		$filename = trim($filename, '.-_');
		return $filename;
	}
	
    public function getSubject() {
        if (!isset($this->headerLines['subject'])) {
            throw new Exception("Couldn't find the subject of the email");
        }
        
        $ret = '';
        
        foreach (imap_mime_header_decode($this->headerLines['subject']) as $h) { // subject can span into several lines
            $charset = ($h->charset == 'default') ? 'US-ASCII' : $h->charset;
            $ret .=  iconv($charset, "UTF-8//TRANSLIT", $h->text);
        }
        return $ret;
    }
    
    public function getFrom() {
        if (!isset($this->headerLines['from'])) {
            throw new Exception("Couldn't find the from of the email");
        }
    
        return $this->headerLines['from'];
    }
    
    public function getTo() {
        if (!isset($this->headerLines['to'])) {
            throw new Exception("Couldn't find the to of the email");
        }
    
        return $this->headerLines['to'];
    }
    
    /*
     * Helper method to get header information easy
     */
    public function getHead($key = '') {
    	if (array_key_exists($key, $this->headerLines)) {
    		return $this->headerLines[$key];
    	} else {
    		return NULL;
    	}
    }
    
    public function getContentIds() {
        return $this->contentIds;
    }
    
    public function getContentDispositions() {
        return $this->contentDispos;
    }
    
    public function getAttachements() {
        return $this->attachements;
    }
}
