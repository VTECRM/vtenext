<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$del_id = $_REQUEST['delete_currency_id'];
$tran_id = $_REQUEST['transfer_currency_id'];

// Transfer all the data refering to currency $del_id to currency $tran_id
transferCurrency($del_id, $tran_id);
global $table_prefix;
// Mark Currency as deleted
$sql = "update ".$table_prefix."_currency_info set deleted=1 where id =?";
$adb->pquery($sql, array($del_id));

header("Location: index.php?action=SettingsAjax&module=Settings&file=CurrencyListView&ajax=true");

?>
