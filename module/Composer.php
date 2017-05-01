<?php
namespace Axel\Module;
class Composer implements \Autoload\Module {
	private $libraryDir;
	private $axel;
	private $cwd;
	private $file;

	public function __construct(\Axel\Axel $axel, $libraryDir, $file = 'composer.json') {
		$this->axel = $axel;
		$this->file = $file;
		$this->libraryDir = getcwd() . DIRECTORY_SEPARATOR . str_replace('./', '', $libraryDir);
	}
	
	public function locate($className) {
		//This is fairly slow but it doesn't matter as all paths are cached to Class:fullpath once the file has been located once
		//This whole class will only get loaded on requests when there is a new class to map
		foreach (new \DirectoryIterator($this->libraryDir) as $vendors) {
			if ($vendors->isDot()) continue;

			if ($vendors->isDir()) {
				foreach (new \DirectoryIterator($vendors->getPathName()) as $package) {
					//$jsonLocation = 
					if ($package->isDot()) continue;

					if ($package->isDir()) {
						if (is_file($package->getPathName() . DIRECTORY_SEPARATOR . $this->file)) {
							$found = $this->loadJsonFile($className, $package->getPathName());
							if ($found) {
								return $found;
							}
						}
					}
				}
			}
		}
	}

	private function loadJsonFile($className, $path) {
		$json = json_decode(file_get_contents($path . DIRECTORY_SEPARATOR . $this->file));
		if (isset($json->autoload)) {
			foreach ($json->autoload as $type => $value) {
				if ($type == 'psr-4') return $this->psr($className, $path, $value);
				else if ($type == 'classmap') return $this->classmap($value);
			}
		}
	}

	private function psr($className, $path, $value) {
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		foreach ($value as $rootNs => $dir) {
			$module = new PSR4($path . DIRECTORY_SEPARATOR . $dir, $rootNs);
			$this->axel->addModule($module);
			return $module->locate($className);
		}
	}

	private function classmap($value) {
		//TODO
	}
}