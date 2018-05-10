<?php
/*
 * This file contains the list of routes used by the application
 *
 */
$routes = array();

// This is a catchall route that will put forward everything to a base page-display controller
// that will display same-named views in the view directory.
$routes["(/.*|)"] = array("controller"=>"\controllers\page","action"=>"index","params"=>array("page"=>1));

return $routes;
