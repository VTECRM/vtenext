<?php

/* new release 19.10 ! */

global $enterprise_current_version, $enterprise_mode, $enterprise_website;
SDK::setLanguageEntries('APP_STRINGS', 'LBL_BROWSER_TITLE', array(
	'it_it'=>"$enterprise_mode $enterprise_current_version",
	'en_us'=>"$enterprise_mode $enterprise_current_version",
	'de_de'=>"$enterprise_mode $enterprise_current_version",
	'nl_nl'=>"$enterprise_mode $enterprise_current_version",
	'pt_br'=>"$enterprise_mode $enterprise_current_version")
);

$result = $adb->query("select templateid, body from {$table_prefix}_emailtemplates where body LIKE '%vtenext 19.07%'");
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$body = $row['body'];
		$body = str_replace('VTENEXT 19.07', $enterprise_mode.' '.$enterprise_current_version, $body);
		$adb->updateClob($table_prefix.'_emailtemplates','body',"templateid = ".$row['templateid'],$body);
	}
}

// crmv@181165
require_once('include/utils/VTEProperties.php');
$VP = VTEProperties::getInstance();
$gc = $VP->get('performance.global_cache');
if ($gc === null) {
	$VP->set('performance.global_cache', 'best');
	$VP->set('performance.global_cache_keys', ['langs']);
	$VP->set('performance.global_cache_config', '');
}
