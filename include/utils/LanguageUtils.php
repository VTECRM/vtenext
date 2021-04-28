<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@151474 */

// TODO: move here all the stuff in SDK/LangUtils.php


/**
 * Class to handle language related operations
 */
class LanguageUtils extends SDKExtendableClass {

	protected $languageStack = array();
		
	/**
	 * Change the current language with the one provided and save the old one in a stack.
	 * Then you should call restoreCurrentLanguage to pop the old language from the stack
	 * and restore it
	 */
	public function changeCurrentLanguage($newLanguage) {
		global $app_strings, $mod_strings;
		global $currentModule, $current_language;
		
		array_push($this->languageStack, $newLanguage);
		if ($newLanguage != $current_language) {
			$current_language = $newLanguage;
			$app_strings = return_application_language($current_language);
			$mod_strings = return_module_language($current_language, $currentModule);
		}		
	}
	
	/**
	 * Restore a previously changed language
	 */
	public function restoreCurrentLanguage() {
		global $app_strings, $mod_strings;
		global $currentModule, $current_language;
		
		$oldLanguage = array_pop($this->languageStack);
		if ($oldLanguage && $oldLanguage != $current_language) {
			$current_language = $oldLanguage;
			$app_strings = return_application_language($current_language);
			$mod_strings = return_module_language($current_language, $currentModule);
		}
	}
	
}