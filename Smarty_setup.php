<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@110561 crmv@181170 */

/**
 * This file is now deprecrated. Don't include it anymore in new developments!
 */

class vtigerCRM_SmartyBase extends VteSmarty {

	function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		logDeprecated('The class vteCRM_Smarty has been renamed to VteSmarty, please review your code.');
		return parent::fetch($template, $cache_id, $compile_id, $parent);
	}

	function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		logDeprecated('The class vteCRM_Smarty has been renamed to VteSmarty, please review your code.');
		return parent::display($template, $cache_id, $compile_id, $parent);
	}
	
}

if (!class_exists('vteCRM_Smarty')) {
	class vtigerCRM_Smarty extends vtigerCRM_SmartyBase {}
}