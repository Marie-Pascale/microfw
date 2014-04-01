<?php
/**
 * Very basic config loader
 *
 * @author     Marie-Pascale Girard <marie.p.girard@gmail.com>
 * @package    framework
 * @filesource
 */
namespace framework;

/**
 *
 * This class implements a file-system level cache system
 *
 */
class config
{
	/**
	 * Config basic directory
	 *
	 * @string
	 */
	public $_basepath = 'app/config/';

	/**
	 * Environment to load
	 *
	 * @string
	 */
	public $_env = "prod";

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($basepath="app/config/", $env="prod") {
		$this->_basepath = $basepath;
		$this->_env = $env;
		if (method_exists($this, "initialize")) {
			$this->initialize();
		}
	}

	/**
	 * Returns the config registered for a requested key.
	 * Key names containing / will result in reading of different files in the config structure
	 *
	 * @param string Key name for the fetched item
	 * @param mixed Default config value
	 * @param array Config options
	 * @return Configuration values or null if not found
	 */
	public function get($key, $default=null, $options=array()) {
		$basepath = isset($options['basepath'])?$options['basepath']:$this->_basepath;
		$env = isset($options['env'])?$options['env']:$this->_env;

		// Determine which config file to search
		// Check if there is a config file for the environment & key
		$conf_file = $basepath.DIRECTORY_SEPARATOR.$env.DIRECTORY_SEPARATOR.$key.".php";
		if (file_exists($conf_file)) {
			$confContentCall = function () use ($conf_file) {return include $conf_file;};
			return $confContentCall();
		}

		// Check if there is a default config file for the key
		$conf_file = $basepath.DIRECTORY_SEPARATOR.$key.".php";
		if (file_exists($conf_file)) {
			$confContentCall = function () use ($conf_file) {return include $conf_file;};
			return $confContentCall();
		}

// 		// If the key is a path
// 		$conf_file = $basepath.DIRECTORY_SEPARATOR.$key.".php";
// 		if (file_exists($conf_file)) {
// 			$confContentCall = function () use ($conf_file) {return include $conf_file;};
// 			return $confContentCall();
// 		}

		// No cache content was found, return default value (null)
		return $default;
	}
}