<?php
global $adb, $table_prefix;

/* crmv@193035 */
$res = $adb->pquery("select reportid, advfilters from {$table_prefix}_reportconfig where advfilters like ?", array('% ( %'));
if ($res && $adb->num_rows($res) > 0) {
	while ($row = $adb->fetchByAssoc($res, -1, false)) {
		$advfilters = Zend_Json::decode($row['advfilters']);
		foreach($advfilters as &$advfilter) {
			if (!empty($advfilter['conditions'])) {
				foreach($advfilter['conditions'] as &$condition) {
					if (stripos($condition['value'],' ( ') !== false) {
						require_once('include/Webservices/WebserviceField.php');
						$fieldInstance = WebserviceField::fromQueryResult($adb,$adb->pquery("SELECT * FROM {$table_prefix}_field WHERE fieldid = ?",array($condition['fieldid'])),0);
						if ($fieldInstance->getFieldDataType() == 'owner' || ($fieldInstance->getFieldDataType() == 'reference' && in_array('Users',$fieldInstance->getReferenceList()))) {
							$condition['value'] = str_replace(' ( ',' (',$condition['value']);
						}
					}
				}
			}
		}
		$adb->pquery("update {$table_prefix}_reportconfig set advfilters = ? where reportid = ?", array(Zend_Json::encode($advfilters),$row['reportid']));
	}
}

/* crmv@193096 */
$folderName = 'CUSTOM_REPORTS_DIR';
$adb->pquery("update {$table_prefix}_crmentityfolder set foldername = ? where foldername = ?", array($folderName,'NEWSLETTER_G_UNSUBSCRIBE_DIR'));
SDK::clearSessionValue('sdk_reportfolders');

$reportName = 'Active Processes';
SDK::setReport($reportName, '', $folderName, 'modules/Processes/ReportActiveProcesses.php', 'ActiveProcessesReportRun');
SDK::deleteLanguageEntry('Reports', '', 'NEWSLETTER_G_UNSUBSCRIBE_DIR');
SDK::setLanguageEntries('Reports', $reportName, array('it_it'=>'Processi attivi','en_us'=>'Active Processes'));
SDK::setLanguageEntries('Reports', $folderName, array('it_it'=>'Report custom','en_us'=>'Custom report folder'));
SDK::setLanguageEntries('Reports', 'NEWSLETTER_G_UNSUBSCRIBE', array('it_it'=>'Disiscrizioni totali da Newsletter','en_us'=>'Newsletter unsubscriptions'));

$table_name = "{$table_prefix}_processmaker";
$cols = $adb->getColumnNames($table_name);
if (!in_array('creatorid', $cols) && !in_array('createdtime', $cols)) {
	$adb->addColumnToTable($table_name, 'creatorid', 'INT(19) NOTNULL DEFAULT 0');
	$adb->addColumnToTable($table_name, 'createdtime', 'T NOTNULL DEFAULT \'0000-00-00 00:00:00\'');
	
	$adb->query("update {$table_prefix}_processmaker
		inner join {$table_prefix}_processmaker_versions on {$table_prefix}_processmaker_versions.processmakerid = {$table_prefix}_processmaker.id and {$table_prefix}_processmaker_versions.xml_version = 1
		set {$table_prefix}_processmaker.creatorid = {$table_prefix}_processmaker_versions.userid, {$table_prefix}_processmaker.createdtime = {$table_prefix}_processmaker_versions.date_version;");
}

SDK::setLanguageEntries('Settings', 'LBL_EMPTY_DYNAFORM_VALUES', array('it_it'=>'Svuota campi','en_us'=>'Empty fields'));

$VTEP = VTEProperties::getInstance();
if ($VTEP->getProperty('modules.messages.messages_cleaned_by_schedule') == '-1 day') {
	$VTEP->setProperty('modules.messages.messages_cleaned_by_schedule', 500);
	$VTEP->setProperty('modules.messages.preserve_search_results_date', '-1 day');
}

/* crmv@193317 */
$adb->pquery("update {$table_prefix}_def_org_share
	inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_def_org_share.tabid
	inner join {$table_prefix}_tab_info on {$table_prefix}_tab.tabid = {$table_prefix}_tab_info.tabid and {$table_prefix}_tab_info.prefname = ? and {$table_prefix}_tab_info.prefvalue = ?
	set editstatus = ?", array('is_mod_light','1',2));