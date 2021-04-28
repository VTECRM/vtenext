<?php
global $adb, $table_prefix;
$result = $adb->pquery("select id from tbl_s_newsletter_status where name = ?", array('LBL_INVALID_EMAILADDRESS'));
if ($result && $adb->num_rows($result) == 0) {
	$adb->pquery('insert into tbl_s_newsletter_status (id, name) values (?,?)', array(7,'LBL_INVALID_EMAILADDRESS'));
}
SDK::setLanguageEntries('Newsletter', 'LBL_INVALID_EMAILADDRESS', array('it_it'=>'Indirizzo email non valido','en_us'=>'Invalid email address'));