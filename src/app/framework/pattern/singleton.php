<?php
/**
 * Singleton pattern class
 *
 * @author     Marie-Pascale Girard <marie-pascale.girard@tonikgroupimage.com>
 * @package    framework
 * @filesource
 */
namespace framework\pattern;

/**
 *
 * This class is the basic implementation of a singleton pattern
 * classes inheriting of this one can only be instanciated via the getInstance
 * ex: $classvar = myclass::getInstance()
 *
 */
abstract class singleton
{
	/**
	 * translated items in the current language
	 *
	 * @array
	 */
	protected $translated = array();

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @staticvar Singleton $instance The *Singleton* instances of this class.
	 * @return Singleton The *Singleton* instance.
	 */
	public static function getInstance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {
	}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup() {
	}
}
