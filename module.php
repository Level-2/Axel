<?php
namespace Autoload;
interface Module {
	public function locate($className);
}
