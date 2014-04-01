<?php
/**
 * Very basic router
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
class router
{
	/**
	 * Route list consisting of arrays like so:
	 * array(
	 *   "/([0-9]+)-mypage/aa/(aa|nn)=>array(
	 *     "controller"=>"\controller\myController",
	 *     "action"=>"myAction",
	 *     "params"=>array(1=>"number",2=>"param2"))
	 * )
	 *
	 * @array
	 */
	private $_routes = array();

	/**
	 * Registers a new route
	 *
	 * @param string Url pattern
	 * @param array Route options & definition
	 * @return void
	 */
	public function add($urlPat, $definition) {
		return $this->_routes[$urlPat] = $definition;
	}

	/**
	 * Registers a new route
	 *
	 * @param string Url to dispatch to the router
	 * @param string Request method
	 * @return void
	 */
	public function dispatch($url, $method="") {
		// interate through the routes until we find one that matches
		$route = false;
		foreach ($this->_routes as $pattern=>$description) {
			// Extract all the parameters in the pattern and see if the url matches the route
			$params = array();
			if (preg_match("#^".$pattern."$#",$url,$params)) {
				if (isset($description['controller'])) {
					$controller = $description['controller'];
				} else {
					$controller = '\controller\index';
				}
				if (isset($description['action'])) {
					$action = $description['action'];
				} else {
					$action = 'index';
				}

				// Prepare the parameter array for the controller
				$parameters = array();
				if (isset($description['params'])) {
					foreach($description['params'] as $name=>$key) {
						if (isset($params[$key])) {
							$parameters[$name] = $params[$key];
						}
					}
				}
				// resolve action and controller if marked as param dependent
				if (is_numeric($controller) && isset($params[$controller])) {
					$controller = $params[$controller];
				}
				if (is_numeric($action) && isset($params[$action])) {
					$action = $params[$action];
				}
				// validate if the controller & action exist and then execute it
				if (method_exists($controller,$action)) {
					$ctrl = new $controller($this);
					$di = \framework\injector::getInstance();
					$di->register("controller",$ctrl);
					$ctrl->$action($parameters);
					return;
				}
				// If it does not exist, try the other routes
			}
		}
		// If there is a 404 route set, call it
		if (isset($this->_routes['404'])) {
			return $this->dispatch('404');
		}
		else {
			header( $_ENV['SERVER_PROTOCOL']." 404 Not Found", true, 404 );
			print "404 Not Found";
			return;
		}
	}

}