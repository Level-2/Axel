<?php
namespace Axel\Module;
class Composer implements \Axel\Module {
	private $axel;
	private $cwd;
	private $rootDir;
	private $fileName = 'composer.json';
	private $cachedInstance;

	public function __construct(\Axel\Axel $axel, $rootDir = '', $fileName = 'composer.json') {
		$this->axel = $axel;
		$this->rootDir = realpath($rootDir);
		$this->cwd = getcwd();

	}

	private function loadComposerJson($dir, \Axel\Axel $axel): \Axel\Axel {
		chdir(realpath($dir));

		$json = json_decode(file_get_contents($this->fileName), true);

		if (isset($json['autoload']['psr-4'])) {
			foreach ($json['autoload']['psr-4'] as $namespace => $path) {
				chdir($path);

				$axel = $axel->addModule(new PSR4(getcwd(), $namespace));
				chdir($this->rootDir);
			}
		}

		chdir($this->cwd);
		return $axel;
	}

	public function locate($className) {
		if (!$this->cachedInstance) {
			$json = json_decode(file_get_contents($this->rootDir .  DIRECTORY_SEPARATOR . $this->fileName), true);

			$libraryDir = realpath($this->rootDir) . DIRECTORY_SEPARATOR .($json['config']['vendor-dir'] ?? 'vendor');

			$axel = $this->loadComposerJson($this->rootDir, $this->axel);

			foreach (new \DirectoryIterator($libraryDir) as $vendor) {
				if ($vendor->isDot() || !$vendor->isDir()) continue;

				foreach (new \DirectoryIterator($vendor->getPathName()) as $package) {
					if ($package->isDot() || !$package->isDir()) continue;

					if (is_file($package->getPathName() . DIRECTORY_SEPARATOR . 'composer.json')) $axel = $this->loadComposerJson($package->getPathName(), $axel);
				}
			}

			$this->cachedInstance = $axel;
		}
		return $this->cachedInstance->load($className);
	}



}
