<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Import_UI_Viewer {
	
	private $parameters = array();
	function assign($key, $value) {
		$this->parameters[$key] = $value;
	}
	
	function viewController() {
		global $theme, $current_language; // crmv@83878
		$themePath = "themes/".$theme."/";
		$imagePath = $themePath."images/";

		$smarty = new VteSmarty();
		
		foreach($this->parameters as $k => $v) {
			$smarty->assign($k, $v);
		}

		$smarty->assign('MODULE', 'Import');
		$smarty->assign('THEME', $theme);
		$smarty->assign('IMAGE_PATH', $imagePath);
		$smarty->assign('LANGUAGE', $current_language); // crmv@83878

		return $smarty;
	}
	
	function display($templateName, $moduleName='') {
		$smarty = $this->viewController();
		if(empty($moduleName)) {
			$moduleName = 'Import';
		}
		$smarty->display(vtlib_getModuleTemplate($moduleName, $templateName));
	}

	function fetch($templateName, $moduleName='') {
		$smarty = $this->viewController();
		if(empty($moduleName)) {
			$moduleName = 'Import';
		}
		return $smarty->fetch(vtlib_getModuleTemplate($moduleName, $templateName));
	}
	
}