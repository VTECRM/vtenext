<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@75301 */

require_once('include/BaseClasses.php');
require_once('modules/Area/Area.php');
require_once('vtlib/Vtecrm/Link.php');

class VTEPageHeader extends SDKExtendableUniqueClass {
	
	public $headerTpl = 'Header.tpl';
	public $headerMenuTpl = 'HeaderMenu.tpl';
	public $headerAllMenuTpl = 'header/HeaderAllMenu.tpl';	// crmv@140887
	public $searchMenuTpl = 'header/HeaderSearchMenu.tpl'; // crmv@140887
	
	protected $isVteDesktop = false;
	
	/**
	 * Constructor, caches some variables
	 */
	public function __construct() {
		$this->isVteDesktop = isVteDesktop();
	}
	
	/**
	 * Display the VTE header
	 */
	public function displayHeader($options = array()) {
		// display the header
		$smarty = $this->initSmarty($options);
		if ($smarty) {
			$this->setModulesVars($smarty, $options);
			$this->setAreasVars($smarty, $options);
			$this->setAdvancedVars($smarty, $options);
			$this->setCustomVars($smarty, $options);
			$smarty->display($this->headerTpl);
		}
	}
	
	// crmv@140887
	public function displayAllMenu($options = array()) {
		$smarty = $this->initSmarty($options);
		if ($smarty) {
			$this->setModulesVars($smarty, $options);
			$this->setAreasVars($smarty, $options);
			$smarty->display($this->headerAllMenuTpl);
		}
	}
	
	public function displaySearchMenu($options = array()) {
		$smarty = $this->initSmarty($options);
		if ($smarty) {
			$this->setModulesVars($smarty, $options);
			$this->setAreasVars($smarty, $options);
			$smarty->display($this->searchMenuTpl);
		}
	}
	// crmv@140887e
	
	/**
	 * Initialize the smarty template with some basic values
	 */
	public function initSmarty($options = array()) { // crmv@189903
		global $theme;
		global $app_strings, $app_list_strings;
		global $currentModule, $current_user;
		
		$smarty = new VteSmarty();
		
		$theme_path="themes/".$theme."/";
		$image_path=$theme_path."images/";
		
		$smarty->assign("THEME",$theme);
		$smarty->assign("IMAGEPATH",$image_path);
		$smarty->assign("APP", $app_strings);
		$smarty->assign("DATE", getDisplayDate(date("Y-m-d H:i")));
		$smarty->assign("MODULE_NAME", $currentModule);
		
		$smarty->assign('ISVTEDESKTOP', $this->isVteDesktop);
		if ($this->isVteDesktop) VteSession::set('menubar', 'no');
		
		$smarty->assign("MENU_TPL", $this->headerMenuTpl);
		
		if ($current_user) {
			$smarty->assign("CURRENT_USER", getUserFullName($current_user->id));	//crmv@29079
			$smarty->assign("CURRENT_USER_ID", $current_user->id);
			if (is_admin($current_user)) {
				$smarty->assign("ADMIN_LINK", "<a href='index.php?module=Settings&action=index'>".$app_strings['LBL_SETTINGS']."</a>");
			}
		}
		
		//Assign the entered global search string to a variable and display it again
		if ($_REQUEST['query_string'] != '') {
			$smarty->assign("QUERY_STRING", htmlspecialchars($_REQUEST['query_string'],ENT_QUOTES)); //ds@16s Bugfix "Cross-Site-Scripting"
		} else {
			$smarty->assign("QUERY_STRING", $app_strings['LBL_GLOBAL_SEARCH_STRING']);
		}
		
		// Gather the custom link information to display
		$hdrcustomlink_params = Array('MODULE'=>$currentModule);
		$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERLINK','HEADERSCRIPT', 'HEADERCSS'), $hdrcustomlink_params);
		$smarty->assign('HEADERLINKS', $COMMONHDRLINKS['HEADERLINK']);
		$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
		$smarty->assign('HEADERCSS', $COMMONHDRLINKS['HEADERCSS']);
		
		// crmv@42024 - pass global JS vars to template
		$JSGlobals = ( function_exists('getJSGlobalVars') ? getJSGlobalVars() : array() );
		$smarty->assign('JS_GLOBAL_VARS', Zend_Json::encode($JSGlobals));
		// crmv@42024e
		
		// crmv@187403
		if (function_exists('get_logo_override')) {
			$smarty->assign('LOGOHEADER', get_logo_override('header'));
			$smarty->assign('LOGOTOGGLE', get_logo_override('toggle'));
		} else {
			$smarty->assign('LOGOHEADER', get_logo('header'));
			$smarty->assign('LOGOTOGGLE', get_logo('toggle'));
		}
		// crmv@187403e
		
		return $smarty;
	}
	
	/**
	 * Set the variables for the module bar
	 */
	protected function setModulesVars(&$smarty, $options = array()) {
		global $app_strings, $app_list_strings;
		global $currentModule, $theme; // crmv@164448
		
		$smarty->assign("MODULELISTS",$app_list_strings['moduleList']);
		
		//crmv@18592
		$menuLayout = getMenuLayout();
		if ($menuLayout['type'] != 'modules') {
			$header_array = getHeaderArray();
			$smarty->assign("HEADERS",$header_array);
		}
		//crmv@18592e
		
		$qc_modules = getQuickCreateModules();
		$smarty->assign("QCMODULE", $qc_modules);
		$smarty->assign("CNT", count($qc_modules));
		
		$smarty->assign("CATEGORY",getParentTab());
		
		$smarty->assign("QUICKACCESS",getAllParenttabmoduleslist($menuLayout['type']));
		
		if (!$this->isVteDesktop && ($menuLayout['type'] == 'modules' || $theme == 'next')) { // crmv@164448
			$menu_module_list = getMenuModuleList(true);
			$smarty->assign('VisibleModuleList', $menu_module_list[0]);
			$smarty->assign('OtherModuleList', $menu_module_list[1]);
	
			$arr1 = array_filter($menu_module_list[0], function($v) use ($currentModule) {
				if ($v['name'] == $currentModule) return true;
			});
			if (count($arr1) == 0 && !in_array($currentModule,array('Settings','Users','Administration','com_workflow','Area')) && getParentTab() != 'Settings') { //crmv@31347 //crmv@207901
				VteSession::set('last_module_visited', $currentModule);
			}
			$smarty->assign("LAST_MODULE_VISITED", VteSession::get('last_module_visited'));
		}
		
	}
	
	/**
	 * Set variables about areas
	 */
	public function setAreasVars(&$smarty, $options = array()) {
		$areaManager = AreaManager::getInstance();
		$menu_module_list = $areaManager->getModuleList();
		$smarty->assign('AREAMODULELIST', $menu_module_list[1]);
		$smarty->assign('BLOCK_AREA_LAYOUT', $areaManager->getToolValue('block_area_layout'));	//crmv@54707
		$smarty->assign('ENABLE_AREAS', $areaManager->getToolValue('enable_areas'));			//crmv@54707
		//crmv@159559
		if ($_REQUEST['query'] == 'true' && $_REQUEST['searchtype'] == 'BasicSearch' && !empty($_REQUEST['search_text'])) {
			$smarty->assign('UNIDIEDSEARCH_QUERY_STRING', $_REQUEST['search_text']);
		}
		//crmv@159559e
	}
	
	/**
	 * Set some extra variables
	 */ 
	protected function setAdvancedVars(&$smarty, $options = array()) {
		global $theme, $current_user;//crmv@208475

		$theme_path="themes/".$theme."/";
		$image_path=$theme_path."images/";
		
        //crmv@208475
		
		//crmv@169305
		$smarty->assign("USE_ASTERISK", get_use_asterisk($current_user->id)); // outgoing calls
		$smarty->assign("USE_ASTERISK_INCOMING", get_use_asterisk($current_user->id,'incoming')); // incoming calls
		//crmv@169305e

		// crmv@92034
		if (PerformancePrefs::getBoolean('JS_DEBUG', false)) {
			$smarty->assign("ENABLE_JS_LOGGER", true);
		}
		// crmv@92034e
		
		//crmv@125629
		if (!VteSession::isEmpty('vtealert')) {
			$smarty->assign("VTEALERT", addslashes(VteSession::get('vtealert')));
			VteSession::remove('vtealert');
		}
		//crmv@125629e
		
		if (isset($_REQUEST['fastpanel'])) $smarty->assign("FAST_PANEL", $_REQUEST['fastpanel']); // crmv@187621
	}
	
	/**
	 * Set variables to customize the header
	 * This method can be overridden to provide customizations.
	 * The content of the variables is drawn directly in the page
	 */ 
	protected function setCustomVars(&$smarty, $options = array()) {
		$overrides = array(
			'post_menu_bar' => null,
			'post_primary_bar' => null,
			'post_secondary_bar' => null,
			'user_icon' => null,
			'settings_icon' => null,
		);
		$smarty->assign("HEADER_OVERRIDE", $overrides);
	}
	
}