<?php
/**
 * Simple dependency injector service
 *
 * @author     Marie-Pascale Girard <marie.p.girard@gmail.com>
 * @package    framework
 * @filesource
 */
namespace framework;

/**
 *
 * This class is the basic implementation of a dependency injector
 * it implements the singleton design pattern so it will only be instanciated once
 * ex: $di = \framework\injector::getInstance()
 *
 */
class injector extends \framework\pattern\singleton
{
	/**
	 * translated items in the current language
	 *
	 * @array
	 */
	protected $ressources = array();

	/**
	 * Returns a registered ressource
	 *
	 * @string Name of the ressource to fetch
	 * @return Ressource
	 */
	 public function getRessource($key) {
		if (isset($this->ressources[$key])) {
			return $this->ressources[$key];
		}
		throw new \Exception ("Unregistered ressource ".$key);
	 }

	/**
	 * Returns a registered ressource
	 *
	 * @string Name of the ressource to fetch
	 * @return Ressource
	 */
	 public function register($key, $ressource) {
		return $this->ressources[$key] = $ressource;
	 }
}
