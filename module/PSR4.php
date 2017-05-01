<?php 
namespace Axel\Module;
/*
* PSR-4 compatible case insensitive autoloader
* 
* Goal to be 100% PSR-4 compatible but also case insensitive as any sane standard should have been
* (unfortunately composer's lack of caching of PSR-4 loaded files makes it impractical so
* it looks like case sensitivity was designed into PSR-4 to work around composer limitations)
* 
* See: https://r.je/php-autoloaders-should-not-be-case-sensitive.html
* for an explanation of why autoloaders should be case insensitive
*
* Because all paths returned by axel modules are cached, the locate function is only run when
* directory structures are added or a new class is added to the project
* It doesn't matter if this is slow, it's only used on the first request. In production all
* paths are cached and this class is never even loaded.
*/
class PSR4 implements \Autoload\Module {
	private $baseDir;
	private $namespace;
	
	public function __construct($baseDir, $namespace = null) {
		if ($baseDir[0] == '/' || $baseDir[1] == ':') $this->baseDir = $baseDir; 
		else $this->baseDir = getcwd() . DIRECTORY_SEPARATOR . str_replace(['./'], '', $baseDir);
		$this->baseDir = rtrim($this->baseDir, DIRECTORY_SEPARATOR);
		$this->namespace = trim($namespace, '\\');
	}
	
	public function locate($className) {
		if ($this->namespace != null) {
			if (strpos(strtolower($className), strtolower($this->namespace)) === 0) {
				$className = ltrim(str_replace($this->namespace, '', $className), '\\');	
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