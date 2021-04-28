<?php

/* new release 19.06 ! */

global $enterprise_current_version, $enterprise_mode, $enterprise_website;
SDK::setLanguageEntries('APP_STRINGS', 'LBL_BROWSER_TITLE', array(
	'it_it'=>"$enterprise_mode $enterprise_current_version",
	'en_us'=>"$enterprise_mode $enterprise_current_version",
	'de_de'=>"$enterprise_mode $enterprise_current_version",
	'nl_nl'=>"$enterprise_mode $enterprise_current_version",
	'pt_br'=>"$enterprise_mode $enterprise_current_version")
);

$result = $adb->query("select templateid, body from {$table_prefix}_emailtemplates where body LIKE '%vtenext 18.12%'");
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$body = $row['body'];
		$body = str_replace('VTENEXT 18.12', $enterprise_mode.' '.$enterprise_current_version, $body);
		$adb->updateClob($table_prefix.'_emailtemplates','body',"templateid = ".$row['templateid'],$body);
	}
}

/* crmv@180737 PHP >= 7.0 !! */
Update::info('Vtenext has dropped support for PHP < 7.0!');
Update::info('If you have customizations using old functions or syntax, please review them.');
Update::info('');


// crmv@181177 - move setting fields
require_once('vtlib/Vtecrm/SettingsBlock.php');
$block = Vtecrm_SettingsBlock::getInstance('LBL_STUDIO');
if ($block) {
	$list = $toMove = array();
	$afterIdx = -1;
	$res = $adb->pquery("SELECT fieldid, name, sequence FROM {$table_prefix}_settings_field WHERE blockid = ? ORDER BY sequence ASC", array($block->id));
	while ($row = $adb->fetchByAssoc($res, -1, false)) {
		$name = $row['name'];
		if ($name == 'LBL_LIST_WORKFLOWS') {
			$list[] = $row['fieldid'];
			$afterIdx = count($list);
		} elseif (in_array($name, array('LBL_MAIL_SCANNER', 'LBL_DATA_IMPORTER'))) {
			$toMove[] = $row['fieldid'];
		} else {
			$list[] = $row['fieldid'];
		}
	}
	
	array_splice($list, $afterIdx, 0, $toMove);
	$list = array_values($list);
	
	// now rebuild the sequencies
	foreach ($list as $seq => $fieldid) {
		$adb->pquery("UPDATE {$table_prefix}_settings_field SET sequence = ? WHERE blockid = ? AND fieldid = ?", array($seq+1, $block->id, $fieldid));
	}

}


// crmv@176547 - VteSync

if (!isModuleInstalled('VteSync')) {
	require_once('vtlib/Vtecrm/Package.php');
	$package = new Vtecrm_Package();
	$package->importByManifest('VteSync');
} else {
	require_once('modules/VteSync/VteSync.php');
	$vsync = VteSync::getInstance();
	$vsync->vtlib_handler('VteSync', 'module.postinstall');
}


