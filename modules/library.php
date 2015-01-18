<?php
namespace Axel\Module;
class Library implements \Autoload\Module {
	private $libraryDir;
	private $axel;
	
	public function __construct(\Axel\Axel $axel, $libraryDir) {
		$this->axel = $axel;
		$this->libraryDir = $libraryDir;
	}
	
	public function locate($className) {
		$rootNs = explode('\\', rtrim('\\', $className))[0];
		if (file_exists($this->libraryDir . DIRECTORY_SEPARATOR . $rootNs . DIRECTORY_SEPARATOR . 'autoload.json')) {
			$json = json_decode(file_get_contents($this->libraryDir . DIRECTORY_SEPARATOR . $rootNs . DIRECTORY_SEPARATOR . 'autoload.json'));
	
			if (isset($json->include)) 	foreach ($json->include as $file) require_once $this->libaryDir . DIRECTORY_SEPARATOR . $rootNs . DIRECTORY_SEPARATOR . $file;
	
			foreach ($json->modules as $key => $value) 	$this->axel->addModule(new $key(...$value));
		}
	}
}