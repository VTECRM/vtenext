<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

 /* crmv@110561 crmv@181170 */

require_once('modules/SDK/SDK.php');

class VteSmartyBase extends Smarty {

	function __construct() {
		global $WORLD_CLOCK_DISPLAY, $current_user, $FCKEDITOR_DISPLAY, $site_URL; // crmv@180714 crmv@197575 crmv@208475

		parent::__construct();

		$theme = CRMVUtils::getApplicationTheme();

		// crmv@140887
		$TU = ThemeUtils::getInstance($theme);
		$this->assign("THEME_CONFIG", $TU->getAll());
		// crmv@140887e

		$templateDirs = array();
		
		$themeTemplatesDir = "Smarty/templates/themes/{$theme}";
		if (file_exists($themeTemplatesDir)) {
			$templateDirs[] = $themeTemplatesDir;
		}
		
		$templateDirs[] = 'Smarty/templates';
		
		$this->setTemplateDir($templateDirs);
		$this->setCompileDir('Smarty/templates_c');
		$this->setConfigDir('Smarty/configs');
		$this->setCacheDir('Smarty/cache');
		// $this->caching = true;
		// un-comment the following line to show the debug console
		// $this->debugging = true;

		$this->assign('WORLD_CLOCK_DISPLAY', $WORLD_CLOCK_DISPLAY);//crmv@208475
		$this->assign('FCKEDITOR_DISPLAY', $FCKEDITOR_DISPLAY);

		// crmv@181170
		if (!empty($current_user->id)) {

			// crmv@17889
			if (is_admin($current_user)) {
				$this->assign('IS_ADMIN', '1');
			}
			// crmv@17889e

			$this->assign('DATE_FORMAT', getTranslatedString($current_user->date_format, 'Users'));
			$this->assign("AUTHENTICATED_USER_LANGUAGE", VteSession::get('authenticated_user_language'));
			$this->assign("SHORT_LANGUAGE", get_short_language());
		}
		// crmv@181170e

		$this->assign('REQUEST_ACTION', $_REQUEST['action']); // crmv@18549
		$this->assign("MENU_LAYOUT", getMenuLayout()); // crmv@18592
		
		// crmv@sdk-18509
		$this->assign("SDK", new SmartySDK()); // crmv@171009
		// crmv@sdk-18509e

		$this->assign('JSON', new Zend_Json()); // crmv@181170

		$this->assign('PERFORMANCE_CONFIG', PerformancePrefs::getAll()); // crmv@115378

		// crmv@118551
		$CU = CRMVUtils::getInstance();
		$this->assign("LAYOUT_CONFIG", $CU->getAllConfigurationLayout());
		// crmv@118551e

		// crmv@140887
		$toggleState = $_COOKIE['togglePin'];
		if (empty($toggleState)) $toggleState = 'enabled';
		$this->assign("MENU_TOGGLE_STATE", $toggleState);
		// crmv@140887e

		$this->assign("FAST_MODE", isset($_REQUEST['fastmode'])); // crmv@181170
		$this->assign('HIDE_MENUS', boolval($_REQUEST['hide_menus']));

		$this->assign("CSRF_TOKEN", RequestHandler::getCSRFToken()); // crmv@171581
		
		$this->assign("SITE_URL", $site_URL); // crmv@197575
	}

	/* crmv@sdk-18502 crmv@sdk-24699 crmv@25671 crmv@54375 crmv@140887 */
	function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		if (isModuleInstalled('SDK') && !in_array($template, SDK::getNotRewritableSmartyTemplates())) {
			$sdkSmartyTemplate = SDK::getSmartyTemplate($_REQUEST);
			if ($sdkSmartyTemplate != '') {
				$template = $sdkSmartyTemplate;
			}
		}
		
		if (!empty($this->getTemplateVars('RETURN_ID')) && !empty($this->getTemplateVars('RETURN_MODULE'))) {
			$ret = getEntityName($this->getTemplateVars('RETURN_MODULE'), array($this->getTemplateVars('RETURN_ID')));
			$this->assign('RETURN_RECORD_NAME', $ret[$this->getTemplateVars('RETURN_ID')]);
			$this->assign('RETURN_RECORD_LINK', 'index.php?module=' . $this->getTemplateVars('RETURN_MODULE') . '&action=DetailView&record=' . $this->getTemplateVars('RETURN_ID'));
		}
		
		return parent::fetch($template, $cache_id, $compile_id, $parent);
	}

	public function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		if (isModuleInstalled('SDK') && !in_array($template, SDK::getNotRewritableSmartyTemplates())) {
			$sdkSmartyTemplate = SDK::getSmartyTemplate($_REQUEST);
			if ($sdkSmartyTemplate != '') {
				$template = $sdkSmartyTemplate;
			}
		}
		
		if (!empty($this->getTemplateVars('RETURN_ID')) && !empty($this->getTemplateVars('RETURN_MODULE'))) {
			$ret = getEntityName($this->getTemplateVars('RETURN_MODULE'), array($this->getTemplateVars('RETURN_ID')));
			$this->assign('RETURN_RECORD_NAME', $ret[$this->getTemplateVars('RETURN_ID')]);
			$this->assign('RETURN_RECORD_LINK', 'index.php?module=' . $this->getTemplateVars('RETURN_MODULE') . '&action=DetailView&record=' . $this->getTemplateVars('RETURN_ID'));
		}
		
		return parent::display($template, $cache_id, $compile_id, $parent);
	}
	/* crmv@sdk-18502e crmv@sdk-24699e crmv@25671e crmv@54375e crmv@140887e */

}

// crmv@171009
/**
 * Wrapper class to use in SDK to call SDK methods non statically
 * because static calls don't work in Smarty 2
 */
class SmartySDK {
	public function __call($name, $arguments = array()) {
		if (is_callable(array('SDKUtils', $name))) {
			return call_user_func_array(array('SDKUtils', $name), $arguments);
		}
		return call_user_func_array(array('SDK', $name), $arguments);
	}
}
// crmv@171009e

// enable the override of standard VteSmarty methods
if (file_exists('modules/SDK/src/VteSmarty.php')) {
	require_once('modules/SDK/src/VteSmarty.php');
}

if (!class_exists('VteSmarty')) {
	class VteSmarty extends VteSmartyBase {}
}