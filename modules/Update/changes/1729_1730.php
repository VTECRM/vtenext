<?php
global $adb, $table_prefix;
$result = $adb->query("select templateid, body from {$table_prefix}_emailtemplates where body LIKE '%vtecrm%'");
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$body = $row['body'];
		$body = str_replace('VTE 16.09', $enterprise_mode.' '.$enterprise_current_version, $body);
		$body = str_replace('vtecrm.com', $enterprise_website[1], $body);
		$body = str_replace('Lo Staff VTECRM', 'Lo Staff '.$enterprise_mode, $body);
		$body = str_replace('VTECRM LIMITED - 38 Craven Street London WC2N 5NG - Registration No. 08337393', 'VTENEXT SRL - Viale Fulvio Testi, 223 - 20162 Milano', $body);
		$body = str_replace('VAT No. 166 1940 00 - Phone (+44) 2035298324', 'P.I. 09869110966 - Phone (+39) 02-37901352', $body);
		$adb->updateClob($table_prefix.'_emailtemplates','body',"templateid = ".$row['templateid'],$body);
	}
}
$adb->pquery("update {$table_prefix}_emailtemplates set subject = templatename where subject = ?", array('VTECRM - contratto in scadenza'));