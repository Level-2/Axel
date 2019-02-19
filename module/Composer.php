<?php
namespace Axel\Module;
class Composer implements \Axel\Module {
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

	private function loadComposerJson($dir, \Axel\Axel $axel) {
		chdir(realpath($dir));

		$json = json_decode(file_get_contents($this->fileName), true);

		if (isset($json['autoload']['psr-4'])) {
			foreach ($json['autoload']['psr-4'] as $namespace => $path) {
				chdir($path);

				$axel = $axel->addModule(new PSR4(getcwd(), $namespace));
			}
		}

		chdir($this->cwd);

		return $axel;
	}

	public function locate($className) {
		//Load composer.json and use the rules defined there to reconfigure the axel instance
		//This works recursively so only needs ot happen once.
		return false;
	}

	public function configureAutoloader(\Axel\Axel $axel): \Axel\Axel {
		$json = json_decode(file_get_contents($this->rootDir .  DIRECTORY_SEPARATOR . $this->fileName), true);

		$libraryDir = realpath($this->rootDir) . DIRECTORY_SEPARATOR .($json['config']['vendor-dir'] ?? 'vendor');

		$axel = $this->loadComposerJson($this->rootDir, $axel);

		foreach (new \DirectoryIterator($libraryDir) as $vendor) {
			if ($vendor->isDot() || !$vendor->isDir()) continue;

			foreach (new \DirectoryIterator($vendor->getPathName()) as $package) {
				if ($package->isDot() || !$package->isDir()) continue;

				if (is_file($package->getPathName() . DIRECTORY_SEPARATOR . 'composer.json')) $axel = $this->loadComposerJson($package->getPathName(), $axel);
			}
		}
		return $axel;
	}

}
