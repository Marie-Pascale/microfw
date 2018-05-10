<?php
/*
 * This file contains the list of routes used by the application
 *
 */
$routes = array();

// This is a catchall route that will put forward everything to a base page-display controller
// that will display same-named views in the view directory.
// $routes["(/.*|)"] = array("controller"=>"\controllers\page","action"=>"index","params"=>array("page"=>1));
$routes[""] = array("controller"=>"\controllers\index","action"=>"index");
$routes["([a-z_-]+)/([0-9]+)"] = array("controller"=>"\controllers\index","action"=>1,"params"=>array("id"=>2));

return $routes;
