<?php
namespace Axel;
interface Cache {
	public function __construct($id);
	public function load($name);
	public function save($name, $data);
}
