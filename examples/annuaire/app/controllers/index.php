<?php
namespace controllers;

class index extends \framework\controller
{
	public function index($params) {
		$mdl = new \models\personnes();
		$this->liste = $mdl->liste();

		$this->render("liste");

// 		$this->i18n = \framework\injector::getInstance()->getRessource("i18n");
// 		$page = $params['page'];
// 		// Index page
// 		if (!$page) {
// 			$page = "index";
// 		}


// 		if (!$this->render($page)) {
// 			$this->error404();
// 		}
	}
}
