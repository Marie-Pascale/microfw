<?php
/**
 * Autoloader
 *
 * @author     Marie-Pascale Girard <marie-pascale.girard@tonikgroupimage.com>
 * @package    framework
 * @filesource
 */
namespace framework;

/**
 *
 * Basic autoloader
 *
 */
class autoloader {
	static public function load($name) {
		$path = dirname(__DIR__).DIRECTORY_SEPARATOR;
		$fic = $path.str_replace("\\",DIRECTORY_SEPARATOR,$name).".php";
		if (file_exists($fic)) {
			return include_once $fic;
		} else {
			return false;
		}
	}
}

spl_autoload_register(__NAMESPACE__ .'\autoloader::load'); // Register this autoloader
