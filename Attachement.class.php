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
 * This class is for attachement data objects and holds the content and
 * some meta data.
 */
class Attachement {

	private $filename = '';			// Filename, either specified by email or cid:
	private $content = NULL;		// content from the attachement
	private $contentType = '';		// holds the type string, e.g. 'image/png'
	
	/*
	 * Stores the attachement to the given destination by using the filename variable
	 * of the object.
	 */
	public function storeTo($path = './') {
		file_put_contents($path . $this->filename, $this->content);	
	}

	public function setFilename($filename) {
		$this->filename = $filename;
	}
	
	public function getFilename() {
		return $this->filename;
	}
	
	public function setContent($content) {
		$this->content = $content;
	}
	
	public function getContent() {
		return $this->content;
	}
	
	public function setContentType($contentType) {
		$this->contentType = $contentType;
	}
	
	public function getContentType() {
		return $this->contentType;
	}
	
}