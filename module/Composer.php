<?php
namespace Axel\Module;
class Composer implements \Autoload\Module {
	private $axel;
	private $cwd;
	private $rootDir;
	private $fileName = 'composer.json';
	private $configured;

	public function __construct(\Axel\Axel $axel, $rootDir = '', $fileName = 'composer.json') {
		$this->axel = $axel;
		$this->rootDir = basename($rootDir);
		$this->cwd = getcwd();
	}

	private function loadComposerJson($dir) {
		chdir(realpath($dir));

		$json = json_decode(file_get_contents($this->fileName), true);

		if (isset($json['autoload']['psr-4'])) {
			foreach ($json['autoload']['psr-4'] as $namespace => $path) {
				chdir($path);

				$this->axel->addModule(new PSR4(getcwd(), $namespace));
			}
		}


		chdir($this->cwd);
	}

	public function locate($className) {
		//Load composer.json and use the rules defined there to reconfigure the axel instance
		//This works recursively so only needs ot happen once.
		if ($this->configured == false) {
			$json = json_decode(file_get_contents($this->rootDir .  DIRECTORY_SEPARATOR . $this->fileName), true);


			$libraryDir = realpath($this->rootDir) . DIRECTORY_SEPARATOR .($json['config']['vendor-dir'] ?? 'vendor');

			$this->loadComposerJson($this->rootDir);

			foreach (new \DirectoryIterator($libraryDir) as $vendor) {
				if ($vendor->isDot() || !$vendor->isDir()) continue;

				foreach (new \DirectoryIterator($vendor->getPathName()) as $package) {
					if ($package->isDot() || !$package->isDir()) continue;

					if (is_file($package->getPathName() . DIRECTORY_SEPARATOR . 'composer.json')) $this->loadComposerJson($package->getPathName());
				}
			}

			$this->configured = true;
		}
		return false;
	}

}
