<?php 

/* crmv@205449 */

require_once('vtlib/Vtecrm/Module.php');
require_once('vtlib/Vtecrm/Access.php');

// get all custom inventory modules
$res = $adb->pquery(
	"SELECT t.tabid, t.name 
	FROM {$table_prefix}_tab t
	INNER JOIN {$table_prefix}_tab_info ti ON ti.tabid = t.tabid AND ti.prefname = 'is_inventory'
	WHERE t.customized = 1 AND t.isentitytype = 1 AND ti.prefvalue = ?",
	[1]
);
// remove tools for them!
while ($row = $adb->fetchByAssoc($res, -1, false)) {
	$modinst = Vtecrm_Module::getInstance($row['name']);
	if ($modinst) {
		Vtecrm_Access::deleteTools($modinst);
	}
}
