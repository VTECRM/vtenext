<?php
global $adb, $table_prefix;
if (file_exists('hash_version.txt')) {
	$hash_version = file_get_contents('hash_version.txt');
	$hash_version_d = Users::de_cryption($hash_version);
	
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$processMakerUtils = ProcessMakerUtils::getInstance();
	$hash_version_d = preg_replace('/\$this->limit_processes = [0-9]+;/','$this->limit_processes = '.$processMakerUtils->limit_processes.';',$hash_version_d);
	$hash_version = Users::changepassword($hash_version_d);
	
	$adb->updateClob($table_prefix.'_version','hash_version','id=1',$hash_version);
	@unlink('hash_version.txt');
}
$cache = Cache::getInstance('vteCacheHV');
$cache->clear();

global $enterprise_current_version, $enterprise_mode, $enterprise_website;
SDK::setLanguageEntries('APP_STRINGS', 'LBL_BROWSER_TITLE', array(
	'it_it'=>"$enterprise_mode $enterprise_current_version",
	'en_us'=>"$enterprise_mode $enterprise_current_version",
	'de_de'=>"$enterprise_mode $enterprise_current_version",
	'nl_nl'=>"$enterprise_mode $enterprise_current_version",
	'pt_br'=>"$enterprise_mode $enterprise_current_version")
);

$result = $adb->query("select templateid, body from {$table_prefix}_emailtemplates where body LIKE '%vtenext 18.05%'");
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$body = $row['body'];
		$body = str_replace('VTENEXT 18.05', $enterprise_mode.' '.$enterprise_current_version, $body);
		$adb->updateClob($table_prefix.'_emailtemplates','body',"templateid = ".$row['templateid'],$body);
	}
}
