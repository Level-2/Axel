<?php 
namespace Axel\Module;
class PSR0 implements \Autoload\Module {
	private $baseDir;
	private $namespace;
	
	public function __construct($baseDir, $namespace = null) {
		if ($baseDir[0] == '/' || $baseDir[1] == ':') $this->baseDir = $baseDir; 
		else $this->baseDir = getcwd() . DIRECTORY_SEPARATOR . str_replace(['./'], '', $baseDir);

		$this->namespace = trim($namespace, '\\');
	}
	
	public function locate($className) {
		if ($this->namespace != null) {
			if (strpos(strtolower($className), strtolower($this->namespace)) === 0) {
				$className = str_replace($this->namespace, '', $className);				
			}	
		}
		
		$parts = explode('\\', $className);

		$file = $this->baseDir . DIRECTORY_SEPARATOR .
				implode(DIRECTORY_SEPARATOR, $parts) .  '.php';

		//First check using case-sensitivity
		if ($file !== null && is_file($file)) return $file;	 

		//Now do case insensitive autoloading
		for ($i = 0; $i < count($parts); $i++) {
			$parts[$i] = strtolower($parts[$i]);
			$file = $this->baseDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) .  '.php';
			if ($file !== null && is_file($file)) return $file;	 
		}
		
		
	}
	
}