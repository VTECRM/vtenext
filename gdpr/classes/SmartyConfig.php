<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554

namespace GDPR;

defined('BASEPATH') OR exit('No direct script access allowed');

class SmartyConfig extends \Smarty {
	
	public function __construct() {
		global $CFG, $GPDRManager, $translations;
		
		parent::__construct();
		
		$this->template_dir = BASEPATH.'templates';
		$this->compile_dir = BASEPATH.'cache/Smarty/templates_c';
		$this->cache_dir = BASEPATH.'cache/Smarty/cache';
		
		$this->assign('CURRENT_ACTION', $GPDRManager->getCurrentAction());
		$this->assign('WEBSITE_LOGO', $CFG->website_logo);
		$this->assign('TRANSLATIONS', \Zend_Json::encode($translations));
	}
	
}