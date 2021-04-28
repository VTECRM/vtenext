<?php
global $adb, $table_prefix;

// crmv@199886
$result = $adb->pquery("select id, processid, elementid from {$table_prefix}_process_extws_meta where type <> ?", array('CallExtWS'));
if ($result && $adb->num_rows($result) > 0) {
	
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$PMUtils = ProcessMakerUtils::getInstance();
	
	while($row=$adb->fetchByAssoc($result)) {
		$data = $PMUtils->retrieve($row['processid']);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		$vte_metadata_arr = $vte_metadata[$row['elementid']];
		if (is_array($vte_metadata_arr['actions'])) {
			foreach ($vte_metadata_arr['actions'] as $action) {
				if ($action['action_type'] == 'CallExtWS') {
					$adb->pquery("update {$table_prefix}_process_extws_meta set text = ?, type = ? where id = ? and processid = ? and elementid = ?",
						array($action['action_title'],'CallExtWS',$row['id'],$row['processid'],$row['elementid']));
					break;
				}
			}
		}
	}
}

// fix grapesjs
SDK::setLanguageEntries('ALERT_ARR', "GRAPES_CO_WARNING", array('it_it'=> 'Per utilizzare l`upload delle immagini devi eseguire l`accesso tramite il link %s', 'en_us'=>'In order to make images upload works, you have to connect with this url %s'));