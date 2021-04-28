<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('vtlib/Vtecrm/Package.php');

class Vtecrm_LanguageExport extends Vtecrm_Package {
	
	const TABLENAME = '_language';

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Generate unique id for insertion
	 * @access private
	 */
	function __getUniqueId() {
		global $adb,$table_prefix;
		return $adb->getUniqueID($table_prefix.self::TABLENAME);
	}

	/**
	 * Initialize Language Schema
	 * @access private
	 */
	static function __initSchema() {
		global $table_prefix;
		$hastable = Vtecrm_Utils::CheckTable($table_prefix.self::TABLENAME);
		if(!$hastable) {
			Vtecrm_Utils::CreateTable(
				$table_prefix.self::TABLENAME,
				'id I(11) NOTNULL PRIMARY,
				name C(50), 
				prefix C(10), 
				label C(30), 
				lastupdated T, 
				sequence I(11), 
				isdefault I(1), 
				active INT(1)',
				true
			);
			global $languages, $adb;
			foreach($languages as $langkey=>$langlabel) {
				$uniqueid = self::__getUniqueId();
				$adb->pquery('INSERT INTO '.$table_prefix.self::TABLENAME.'(id,name,prefix,label,lastupdated,active) VALUES(?,?,?,?,?,?)',
					Array($uniqueid, $langlabel,$langkey,$langlabel,date('Y-m-d H:i:s',time()), 1));
			}
		}
	}

	/**
	 * Register language pack information.
	 */
	static function register($prefix, $label, $name='', $isdefault=false, $isactive=true, $overrideCore=false) {
		self::__initSchema();
		global $table_prefix;
		$prefix = trim($prefix);
		// We will not allow registering core language unless forced
		if(strtolower($prefix) == 'en_us' && $overrideCore == false) return;

		$useisdefault = ($isdefault)? 1 : 0;
		$useisactive  = ($isactive)?  1 : 0;

		global $adb;
		$checkres = $adb->pquery('SELECT * FROM '.$table_prefix.self::TABLENAME.' WHERE prefix=?', Array($prefix));
		$datetime = date('Y-m-d H:i:s');
		if($adb->num_rows($checkres)) {
			$id = $adb->query_result($checkres, 0, 'id');
			$adb->pquery('UPDATE '.$table_prefix.self::TABLENAME.' set label=?, name=?, lastupdated=?, isdefault=?, active=? WHERE id=?',
				Array($label, $name, $datetime, $useisdefault, $useisactive, $id));
		} else {
			$uniqueid = self::__getUniqueId();
			$adb->pquery('INSERT INTO '.$table_prefix.self::TABLENAME.' (id,name,prefix,label,lastupdated,isdefault,active) VALUES(?,?,?,?,?,?,?)',
				Array($uniqueid, $name, $prefix, $label, $datetime, $useisdefault, $useisactive));
		}
		self::log("Registering Language $label [$prefix] ... DONE");		
	}

	/**
	 * De-Register language pack information
	 * @param String Language prefix like (de_de) etc
	 */
	static function deregister($prefix) {
		global $table_prefix;
		$prefix = trim($prefix);
		// We will not allow deregistering core language
		if(strtolower($prefix) == 'en_us') return;

		self::__initSchema();

		global $adb;
		$checkres = $adb->pquery('DELETE FROM '.$table_prefix.self::TABLENAME.' WHERE prefix=?', Array($prefix));
		self::log("Deregistering Language $prefix ... DONE");
	}

	/**
	 * Get all the language information
	 * @param Boolean true to include in-active languages also, false (default)
	 */
	static function getAll($includeInActive=false) {
		global $adb,$table_prefix;
		$hastable = Vtecrm_Utils::CheckTable($table_prefix.self::TABLENAME);

		$languageinfo = Array();

		if($hastable) {
			if($includeInActive) $result = $adb->query('SELECT * FROM '.$table_prefix.self::TABLENAME);
			else $result = $adb->query('SELECT * FROM '.$table_prefix.self::TABLENAME . ' WHERE active=1');

			for($index = 0; $index < $adb->num_rows($result); ++$index) {
				$resultrow = $adb->fetch_array($result);
				$prefix = $resultrow['prefix'];
				$label  = $resultrow['label'];
				$languageinfo[$prefix] = $label;
			}
		} else {
			global $languages;
			foreach((Array)$languages as $prefix=>$label) { //crmv@36557
				$languageinfo[$prefix] = $label;
			}
		}
		return $languageinfo;
	}
	
	//crmv@187234
	static function str2File($string) {
		$string = html_entity_decode($string, ENT_QUOTES);
		$string = addcslashes($string, '\\'); // serve per i casi "bla bla \'bla\' bla" es. en_us APP_STRINGS CANT_SELECT_CONTACTS
		$string = addcslashes($string, "'");
		return $string;
	}
	
	static function makeLanguageFiles($language, $path='') {
		global $current_language, $enterprise_current_version;
		$old_current_language = $current_language;
		$current_language = $language;
		
		if (empty($path)) $path = 'cache/vtlib/'.$language.'/';
		
		require_once('include/utils/FSUtils.php');
		folderDetete($path);
		mkdir($path,0777,true);
		
		// modules translations
		$modules = SDK::getModuleLanguageList();
		foreach ($modules as $module){
			$lang = get_lang_strings($module,$language);
			if(!is_array($lang)) $lang = array();
			
			$data = "<?php\n\$mod_strings = array(\n";
			foreach($lang as $key => $value){
				$data .= "\t'".self::str2File($key)."'=>'".self::str2File($value)."',\n";
			}
			$data .= ");\n?>";
			mkdir($path."modules/$module/language",0777,true);
			$fp = fopen($path."modules/$module/language/$language.lang.php","w+");
			fwrite($fp,$data);
		}
		
		// include/js translations
		$data = SDK::loadJsLanguage();
		@mkdir($path."include/js",0777,true);
		$fp = fopen($path."include/js/$language.lang.js","w+");
		fwrite($fp,$data);
		
		// include/language translations
		$data = "<?php";
		$lang = get_lang_strings('APP_STRINGS',$language);
		$data .= "\n\$app_strings = array(\n";
		foreach($lang as $key => $value){
			$data .= "\t'".self::str2File($key)."'=>'".self::str2File($value)."',\n";
		}
		$data .= ");";
		$lang = get_lang_strings('APP_LIST_STRINGS',$language);
		$data .= "\n\$app_list_strings = array(\n";
		foreach($lang as $key => $value){
			if(is_array($lang[$key])){
				$data .= "\t'{$key}' => array(\n";
				foreach($lang[$key] as $lkey => $lvalue){
					$data .= "\t\t'".self::str2File($lkey)."'=>'".self::str2File($lvalue)."',\n";
				}
				$data .= "\t),\n";
			}
			else{
				$data .= "\t'".self::str2File($key)."'=>'".self::str2File($value)."',\n";
			}
		}
		$data .= ");";
		$lang = get_lang_strings('APP_CURRENCY_STRINGS',$language);
		if(!is_array($lang)) $lang = array();
		$data .= "\n\$app_currency_strings = array(\n";
		foreach($lang as $key => $value){
			$data .= "\t'".self::str2File($key)."'=>'".self::str2File($value)."',\n";
		}
		$data .= ");";
		$data .= "\n?>";
		@mkdir($path."include/language",0777,true);
		$fp = fopen($path."include/language/$language.lang.php","w+");
		fwrite($fp,$data);
		
		// some translations are still on file so copy the file in this folder
		@mkdir($path."include/Webservices/language",0777,true);
		copy("include/Webservices/language/$language.lang.php",$path."include/Webservices/language/$language.lang.php");
		
		@mkdir($path."portal/language",0777,true);
		copy("portal/language/$language.lang.php",$path."portal/language/$language.lang.php");
		
		@mkdir($path."vtlib/ModuleDir/$enterprise_current_version/language",0777,true);
		copy("vtlib/ModuleDir/$enterprise_current_version/language/$language.lang.php",$path."vtlib/ModuleDir/$enterprise_current_version/language/$language.lang.php");
		
		$current_language = $old_current_language;
		
		return $path;
	}
	
	function export($prefix, $todir='', $zipfilename='', $directDownload=false) {
		global $adb, $table_prefix, $default_charset, $enterprise_current_version;
		$this->_export_tmpdir = 'cache/vtlib/'.$prefix;
		$zip_export_tmpdir = 'cache/vtlib';

		$result = $adb->pquery("SELECT name, label FROM {$table_prefix}_language WHERE prefix = ?", array($prefix));
		if ($adb->num_rows($result) == 0) return false;
		$name = $adb->query_result($result,0,'name');
		$label = $adb->query_result_no_html($result,0,'label');
		
		$path = self::makeLanguageFiles($prefix);
		
		// make manifest
		$this->_export_modulexml_filename = "manifest.xml";
		$this->_export_modulexml_file = fopen($this->__getManifestFilePath(), 'w');
		$charset = (empty($default_charset) ? 'UTF-8' : $default_charset);
		$this->__write("<?xml version=\"1.0\" encoding=\"$charset\" ?>\n");
		$this->openNode('module');
		$this->outputNode('language', 'type');
		$this->outputNode($name, 'name');
		$this->outputNode($label, 'label');
		$this->outputNode($prefix, 'prefix');
		//$this->outputNode('', 'version');
		$this->openNode('dependencies');
		$this->outputNode($enterprise_current_version, 'vtenext_version');//crmv@207991
		$this->closeNode('dependencies');
		$this->closeNode('module');
		$this->__finishExport();
		
		// Export as Zip
		if($zipfilename == '') $zipfilename = "$name-" . date('YmdHis') . ".zip";
		$zipfilename = "$zip_export_tmpdir/$zipfilename";
		
		$zip = new Vtecrm_Zip($zipfilename);
		$zip->addFile($this->__getManifestFilePath(), "manifest.xml");
		$zip->copyDirectoryFromDisk($this->_export_tmpdir.'/include', "include");
		$zip->copyDirectoryFromDisk($this->_export_tmpdir.'/modules', "modules");
		$zip->copyDirectoryFromDisk($this->_export_tmpdir.'/portal', "portal");
		$zip->copyDirectoryFromDisk($this->_export_tmpdir.'/vtlib', "vtlib");
		$zip->save();
				
		if($directDownload) {
			$zip->forceDownload($zipfilename);
			unlink($zipfilename);
		}
		
		// clean
		require_once('include/utils/FSUtils.php');
		folderDetete($path);
	}
	//crmv@187234e
}
