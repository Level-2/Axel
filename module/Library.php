<?php
namespace Axel\Module;
class Library implements \Autoload\Module {
	private $libraryDir;
	private $axel;
	private $cwd;
		
	public function __construct(\Axel\Axel $axel, $libraryDir) {
		$this->axel = $axel;
		$this->libraryDir = getcwd() . DIRECTORY_SEPARATOR . str_replace('./', '', $libraryDir);
	}
	
	public function locate($className) {

		$rootNs = strtolower(explode('\\', rtrim($className, '\\'))[0]);


		if (file_exists($this->libraryDir . DIRECTORY_SEPARATOR . strtolower($rootNs) . DIRECTORY_SEPARATOR . 'autoload.json')) {

			$json = json_decode(file_get_contents($this->libraryDir . DIRECTORY_SEPARATOR . $rootNs . DIRECTORY_SEPARATOR . 'autoload.json'));
	
			if (isset($json->include)) 	foreach ($json->include as $file) require_once $this->libaryDir . DIRECTORY_SEPARATOR . $rootNs . DIRECTORY_SEPARATOR . $file;
	
			foreach ($json->modules as $key => $value) 	{
				if ($key == 'Axel\\Module\\PSR0') {
					$value[0] = $this->libraryDir . DIRECTORY_SEPARATOR . $rootNs . DIRECTORY_SEPARATOR . $value[0];
				}
				$module = new $key(...$value);
				$this->axel->addModule($module);
				return $module->locate($className);
			}
		}
	}
}