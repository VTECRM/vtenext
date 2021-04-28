<?php
//update to change all sdk_language trans_label , vtiger strings to vte
global $adb, $table_prefix;

$sql = "SELECT trans_label FROM sdk_language WHERE trans_label LIKE '%VTIGER%' ";
// crmv@190493e
$res = $adb->query($sql);
$num_rows = $adb->num_rows($result);
$resultSet = array();
if ($res) {
    while($row=$adb->fetchByAssoc($res)) {
        $resultSet[] = $row['trans_label'];

    }
}
foreach($resultSet as $index => $value ){
    //can be edited, it is in this format in case you need to edit strings in other formats
    //può essere modificato, è in questo formato nel caso in cui sia necessario modificare le stringhe in altri formati
    if (str_contains($value, 'vTiger')) {
        $value2 = str_replace('vTiger',"vte",$value);
    }
    else{
        $value2 = str_replace('vtiger',"vte",$value);
    }

    $adb->query("UPDATE sdk_language SET trans_label='{$value2}' WHERE trans_label='{$value}'");

}