<?php

// crmv@208111


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
