<?php
/**
 * Very basic controller base
 *
 * @author     Marie-Pascale Girard <marie.p.girard@gmail.com>
 * @package    framework
 * @filesource
 */
namespace framework;

/**
 *
 * This class implements the basics for controllers
 *
 */
class controller
{
	/**
	 * Displays a view
	 *
	 * @TODO Modify this so the view path can be settable or perhaps use a view manager
	 *
	 * @param string View to display
	 * @param string View path (optional: will go up one dir under the views directory by default)
	 * @return void
	 */
	protected function render($view, $path=null) {
		$path = $path?$path:dirname(__DIR__).DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR;
		if (file_exists($path.$view.".phtml")) {
			return include_once $path.$view.".phtml";
		}
		return false;
	}

	/**
	 * Throw a 404 error
	 *
	 * @return void
	 */
	protected function error404() {
		\framework\injector::getInstance()->getRessource("router")->dispatch("404");
		exit;
	}
}