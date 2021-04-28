<?php
global $adb, $table_prefix;

$result = $adb->query("select templateid, body from {$table_prefix}_emailtemplates where body LIKE '%vtenext%'");
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$body = $row['body'];
		$body = str_replace('VTENEXT 18.12', $enterprise_mode.' '.$enterprise_current_version, $body);
		$body = str_replace('VTENEXT SRL - Viale Fulvio Testi, 223 - 20162 Milano', 'VTENEXT SRL - Viale Sarca, 336/F - 20126 Milano', $body);
		$adb->updateClob($table_prefix.'_emailtemplates','body',"templateid = ".$row['templateid'], $body);
	}
}

if (file_exists('hash_version.txt')) {
	$hash_version = file_get_contents('hash_version.txt');
	$adb->updateClob($table_prefix.'_version','hash_version','id=1',$hash_version);
	@unlink('hash_version.txt');
	$cache = Cache::getInstance('vteCacheHV');
	$cache->clear();
}