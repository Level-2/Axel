<?php

namespace Axel\Module;

class Library implements \Autoload\Module {
	private $libraryDir;
	private $axel;
	private $rootNs;
	private $cwd;

	public function __construct(\Axel\Axel $axel, $libraryDir, $rootNs = null, $baseDir = null) {
		$this->axel = $axel;
		$this->cwd = is_null($baseDir) ? getcwd() : $baseDir;
		$this->libraryDir = $this->cwd . DIRECTORY_SEPARATOR . ltrim(str_replace('./', '', $libraryDir), '/\\');
		$this->rootNs = $rootNs;
	}

	public function locate($className) {

		$rootNs = is_null($this->rootNs) ? explode('\\', rtrim($className, '\\'))[0] . DIRECTORY_SEPARATOR : $this->rootNs;

		if (file_exists($this->libraryDir . DIRECTORY_SEPARATOR . $rootNs . 'autoload.json')) {

			$json = json_decode(file_get_contents($this->libraryDir . DIRECTORY_SEPARATOR . $rootNs . 'autoload.json'));

			if (isset($json->include)) 	foreach ($json->include as $file) require_once $this->libraryDir . DIRECTORY_SEPARATOR . $rootNs . $file;

			foreach ($json->modules as $key => $value) 	{
				if ($key == 'Axel\\Module\\PSR0') {
					$value[0] = $this->libraryDir . DIRECTORY_SEPARATOR . $rootNs . $value[0];
				}
				$module = new $key(...$value);
				$this->axel->addModule($module);
				return $module->locate($className);
			}
		}
	}
}