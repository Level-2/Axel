<?php
namespace Axel;
class Axel {
	private $paths = [];
	private $cache;
	private $saveCache = false;
	private $modules = [];

	public function __construct(Cache $cache = null) {	
		$this->cache = $cache;	
		spl_autoload_register([$this, 'load']);
		$this->paths = ($this->cache !== null) ? $this->cache->load('axelpaths') : [];		
	}

	public function load($className) {
		$className = trim($className, '\\');
		$classNameLc = strtolower($className);
		if (isset($this->paths[$classNameLc])) {
			if (file_exists($this->paths[$classNameLc])) require_once $this->paths[$classNameLc];
			else {
				$this->saveCache = true;
				unset($this->paths[$classNameLc]);
				//Something changed since the last run, clear the path for the file and try to load it again.
				$this->load($className);
			}
		}
		else {
			foreach ($this->modules as $module) {
				if ($file = $module->locate($className)) {
					$this->paths[$classNameLc] = $file;
					$this->saveCache = true;
					require_once $this->paths[$classNameLc];
					break;
				}
			}
		}
	}
	
	public function addModule(Module $module) {
		$this->modules[] = $module;
	}
	
	public function __destruct() {
		if ($this->cache !== null && $this->saveCache) $this->cache->save('axelpaths', $this->paths);
	}	
}
