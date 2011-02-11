<?php

/**
 * A specific page within a {@link DocumentationEntity}. Maps 1 to 1 to a file on the 
 * filesystem.
 * 
 * @package sapphiredocs
 */
class DocumentationPage extends ViewableData {
	
	/**
	 * @var DocumentationEntity
	 */
	protected $entity;
	
	/**
	 * Stores the relative path (from the {@link DocumentationEntity} to
	 * this page. The actual file name can be accessed via {@link $this->getFilename()}
	 *
	 * @var String 
	 */
	protected $relativePath;
	
	/**
	 * @var String
	 */
	protected $lang = 'en';
	
	/**
	 * @var string
	 */
	protected $title;
	
	/**
	 * @var String
	 */
	protected $version;
	
	
	/**
	 * @return DocumentationEntity
	 */
	function getEntity() {
		return $this->entity;
	}
	
	/**
	 * @param DocumentationEntity
	 */
	function setEntity($entity) {
		$this->entity = $entity;
	}
		
	/**
	 * @return string
	 */
	function getRelativePath() {
		return $this->relativePath;
	}
	
	/**
	 * @param string
	 */
	function setRelativePath($path) {
		$this->relativePath = $path;
	}
	
	/**
	 * Absolute path including version and lang folder.
	 * 
	 * @throws InvalidArgumentException
	 *
	 * @param bool $defaultFile - If this is a folder and this is set to true then getPath
	 *				will return the path of the first file in the folder
	 * @return string 
	 */
	function getPath($defaultFile = false) {
		if($this->entity) {
			$path = rtrim($this->entity->getPath($this->version, $this->lang), '/') . '/' . trim($this->getRelativePath(), '/');
			
			if(!is_dir($path)) $path = realpath($path);
			else if($defaultFile) {
				$file = DocumentationService::find_page($this->entity, explode('/', $this->getRelativePath()));

				if($file) $path = $file;
			}
		}
		else {
			$path = $this->getRelativePath();
		}
		
		if(!file_exists($path)) {
			throw new InvalidArgumentException(sprintf(
				'Path could not be found. Module path: %s, file path: %s', 
				$this->entity->getPath(),
				$this->getRelativePath()
			));
		}
		
		
		return (is_dir($path)) ? rtrim($path, '/') . '/' : $path;
	}
	
	/**
	 * Returns the link for the web browser
	 *
	 * @return string
	 */
	function Link() {
		if($entity = $this->getEntity()) {
			$link = Controller::join_links($entity->Link($this->version, $this->lang), $this->getRelativeLink());

			$link = rtrim(DocumentationService::trim_extension_off($link), '/');
			
			// folders should have a / on them. Looks nicer
			try {
				if(is_dir($this->getPath())) $link .= '/';
			}
			catch (Exception $e) {}
		}
		else {
			$link = $this->getPath(true);
		}

		return $link;
	}
	
	/**
	 * Relative to the module base, not the webroot
	 * 
	 * @return string
	 */
	function getRelativeLink() {
		$link = rtrim(DocumentationService::trim_extension_off($this->getRelativePath()), '/');
		
		// folders should have a / on them. Looks nicer
		try {
			if(is_dir($this->getPath())) $link .= '/';
		} catch (Exception $e) {};
		
		return $link;
	}
	
	function getLang() {
		return $this->lang;
	}
	
	function setLang($lang) {
		$this->lang = $lang;
	}
	
	function getVersion() {
		return $this->version;
	}
	
	function setVersion($version) {
		$this->version = $version;
	}
	
	function setTitle($title) {
		$this->title = $title;
	}
	
	function getTitle() {
		return $this->title;
	}
	
	/**
	 * Set a variable from the metadata field on this class
	 *
	 * @param String key
	 * @param mixed value
	 */
	public function setMetaData($key, $value) {
		$this->$key = $value;
	}
	
	/**
	 * @return string
	 */
	function getFilename() {
		$path = rtrim($this->relativePath, '/');
		
		try {
			return (is_dir($this->getPath())) ? $path . '/' : $path;
		}
		catch (Exception $e) {}
		
		return $path;
	}

	/**
	 * @return String
	 */
	function getMarkdown() {
		try {
			$path = $this->getPath(true);

			if($path) {
				return file_get_contents($path);
			}
		}
		catch(InvalidArgumentException $e) {}
		
		return null;
	}
	
	/**
	 * @param String $baselink 
	 * @return String
	 */
	function getHTML($baselink = null) {
		// if this is not a directory then we can to parse the file
		return DocumentationParser::parse($this, $baselink);
	}
}