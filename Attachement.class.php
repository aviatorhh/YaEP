<?php

class Attachement {

	private $filename = '';
	private $content = NULL;
	private $contentType = '';
	
	
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
	
	public function storeTo($path = './') {
		file_put_contents($path . $this->filename, $this->content);	
	}

}