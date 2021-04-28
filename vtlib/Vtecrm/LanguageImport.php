<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198038 */

require_once('vtlib/Vtecrm/LanguageExport.php');

class Vtecrm_LanguageImport extends Vtecrm_LanguageExport {
	
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	function getPrefix() {
		return $this->_modulexml->name;
	}

	/**
	 * Initialize Import
	 * @access private
	 */
	function initImport($zipfile, $overwrite) {
		$this->__initSchema();

		$name = $this->getModuleNameFromZip($zipfile);
	}

	/**
	 * Import Module from zip file
	 * @param String Zip file name
	 * @param Boolean True for overwriting existing module
	 */
	function import($zipfile, $overwrite=false, $tabidtouse=false) { // crmv@146653
		$this->initImport($zipfile, $overwrite);
	
		// Call module import function
		$this->import_Language($zipfile);
	}

	/**
	 * Update Module from zip file
	 * @param Object Instance of Language (to keep Module update API consistent)
	 * @param String Zip file name
	 * @param Boolean True for overwriting existing module
	 */
	function update($instance, $zipfile, $overwrite=true) {
		$this->import($zipfile, $overwrite);
	}

	/**
	 * Import Module
	 * @access private
	 */
	function import_Language($zipfile) {
		$name = $this->_modulexml->name;
		$prefix = $this->_modulexml->prefix;
		$label = $this->_modulexml->label;

		SDK::deleteLanguage('',$prefix);	//crmv@sdk-18430
		
		self::log("Importing $label [$prefix] ... STARTED");
		$unzip = new Vtecrm_Unzip($zipfile);
		$filelist = $unzip->getList();

		foreach($filelist as $filename=>$fileinfo) {
			if(!$unzip->isdir($filename)) {
				
				if(strpos($filename, '/') === false) continue;

				$targetdir  = substr($filename, 0, strripos($filename,'/'));
				$targetfile = basename($filename);

				$prefixparts = explode('_', $prefix);

				$dounzip = false;
				if(is_dir($targetdir)) {
					// Case handling for jscalendar
					// crmv@190519
					if(stripos($targetdir, 'include/js/jscalendar/lang') === 0
						&& stripos($targetfile, "calendar-".$prefixparts[0].".js")===0) {

							if(file_exists("$targetdir/calendar-en.js")) {
								$dounzip = true;
							}
					}
					// crmv@190519e
					// Case handling for phpmailer
				   	else if(stripos($targetdir, 'modules/Emails/language') === 0
						&& stripos($targetfile, "phpmailer.lang-$prefix.php")===0) {

							if(file_exists("$targetdir/phpmailer.lang-en_us.php")) {
								$dounzip = true;
							}
					} 
					// Handle javascript language file
					else if(preg_match("/$prefix.lang.js/", $targetfile)) {
						$corelangfile = "$targetdir/en_us.lang.js";
						if(file_exists($corelangfile)) {
							$dounzip = true;
						}
					} 
					// Handle php language file 
					else if(preg_match("/$prefix.lang.php/", $targetfile)) {
						$corelangfile = "$targetdir/en_us.lang.php";
						if(file_exists($corelangfile)) {
							$dounzip = true;
						}
					}
					//crmv@sdk-18430
					//CALENDAR NEW FILES
					else if(preg_match("/_lang_$prefix./", $targetfile)) {
						$dounzip = true;
					}
					//crmv@sdk-18430e
				}

				if($dounzip) {					
					if($unzip->unzip($filename, $filename) !== false) {
						self::log("Copying file $filename ... DONE");
					} else {
						self::log("Copying file $filename ... FAILED");
					}
				} else {
					self::log("Copying file $filename ... SKIPPED");
				}
			}
		}
		if($unzip) $unzip->close();

		self::register($prefix, $label, $name);
		
		//crmv@sdk-18430
		SDK::importPhpLanguage($prefix);
		//SDK::importJsLanguage($prefix);
		//crmv@sdk-18430 e
		
		self::log("Importing $label [$prefix] ... DONE");

		return;
	}
}			
