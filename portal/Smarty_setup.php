<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require('Smarty/libs/Smarty.class.php');

class VTECRM_Smarty extends Smarty{

	function __construct()
	{
		parent::__construct(); // crmv@168297
		$this->template_dir = 'Smarty/templates';
		$this->compile_dir = 'Smarty/templates_c';
		$this->config_dir = 'Smarty/configs';
		$this->cache_dir = 'Smarty/cache';
		//$this->caching = true;
		
		$this->assign("CSRF_TOKEN", VteCsrf::get_token()); // crmv@171581
	}
	
	// crmv@168297
	function fetch($resource_name, $cache_id = null, $compile_id = null, $display = false) {
		
		if ($sdkTpl = SDK::getTemplate($resource_name)) {
			$resource_name = $sdkTpl;
		}

		return parent::fetch($resource_name, $cache_id, $compile_id, $display);
	}
	
	function _smarty_include($params) {
		
		$resource_name = $params['smarty_include_tpl_file'];
		
		if ($sdkTpl = SDK::getTemplate($resource_name)) {
			$resource_name = $sdkTpl;
			$params['smarty_include_tpl_file'] = $resource_name;
		}
		
		return parent::_smarty_include($params);
	}
	// crmv@168297e
}