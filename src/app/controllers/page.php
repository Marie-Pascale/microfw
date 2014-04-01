<?php
namespace controllers;

class page extends \framework\controller
{
	public function index($params) {
		$this->i18n = \framework\injector::getInstance()->getRessource("i18n");
		$page = $params['page'];
		// Index page
		if (!$page) {
			$page = "index";
		}
		if (!$this->render($page)) {
			$this->error404();
		}
	}
}