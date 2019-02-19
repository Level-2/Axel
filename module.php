<?php
namespace Axel;
interface Module {
	public function locate($className);
	public function configureAutoloader(Axel $axel): Axel;
}
