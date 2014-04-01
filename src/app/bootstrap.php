<?php
/*
Bootstrap file
*/

// Display errors if in dev
if ($env == "dev") {
	error_reporting(E_ALL & ~E_NOTICE);
	ini_set('display_errors', '1');
}

// load the autoloader
require_once __DIR__.'/framework/autoloader.php';

// Load the dependency injector
$di = \framework\injector::getInstance();

// Instanciate the config loader
$cfg = new \framework\config(__DIR__.DIRECTORY_SEPARATOR."config",isset($env)?$env:"prod");
$di->register("config", $cfg);

// Instanciate the database connection unless the flag for no database is set
if (!(isset($noDB) && $noDB)) {
	$dbCfg = $cfg->get("db");
	try {
		$di->register("db", new PDO($dbCfg['DB_TYPE'].':host='.$dbCfg['DB_HOST'].";dbname=".$dbCfg['DB_NAME'], $dbCfg['DB_USER'], $dbCfg['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true)));
	} catch (PDOException $e) {
		print "Erreur !: " . $e->getMessage() . "<br/>";
		die();
	}
}

// set up the language
// If specified in the url, set it
$i18n = new \framework\translations(array("debug"=>true,"accepted-languages"=>$cfg->get("languages")));
// Put the language in the cookies (simpler management)
$i18n->setup("cookie");
$di->register("i18n", $i18n);

// Instanciate a router and add all the routes found in the config file
$router = new \framework\router();
$di->register("router", $router);
foreach ($cfg->get("routes") as $route=>$definition) {
	$router->add($route,$definition);
}

// Dispatch the currently called url to the proper route.
$router->dispatch($_GET['_url']);
