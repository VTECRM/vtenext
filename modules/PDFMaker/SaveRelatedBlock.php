<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/
// ITS4YOU TT0093 VlMe N

require_once('include/database/PearDatabase.php');
require_once("include/Zend/Json.php");

global $adb, $current_user,$table_prefix;

$smarty = new VteSmarty();

//$adb->database->debug = true;

$rel_module = $_REQUEST["pdfmodule"];
$record = $_REQUEST["record"];

$rel_module_id = getTabid($rel_module);

require_once('include/utils/UserInfoUtil.php');
global $current_language;
global $app_strings;
global $mod_strings;
global $theme,$default_charset;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

global $current_language;


$relblockid = vtlib_purify($_REQUEST["record"]);

$name = vtlib_purify($_REQUEST["blockname"]);
$module = vtlib_purify($_REQUEST["primarymodule"]);
$secmodule = vtlib_purify($_REQUEST["secondarymodule"]);
$block = vtlib_purify($_REQUEST["relatedblock"]);


$stdDateFilterField = vtlib_purify($_REQUEST["stdDateFilterField"]);
$stdDateFilter = vtlib_purify($_REQUEST["stdDateFilter"]);
$startdate = getValidDBInsertDateValue($_REQUEST["startdate"]);
$enddate = getValidDBInsertDateValue($_REQUEST["enddate"]);

$json = new Zend_Json();

$advft_criteria = $_REQUEST['advft_criteria'];
$advft_criteria = $json->decode($advft_criteria);

$advft_criteria_groups = $_REQUEST['advft_criteria_groups'];
$advft_criteria_groups = $json->decode($advft_criteria_groups);


if ($relblockid != "")
{
    //crmv@19166
    $sql = "UPDATE {$table_prefix}_pdfmaker_relblocks SET name = ? WHERE relblockid = ?";//crmv@208173
    $adb->pquery($sql, array($name, $relblockid));
    $adb->updateClob($table_prefix.'_pdfmaker_relblocks','block',"relblockid=$relblockid",$block);
    //crmv@19166e

    //$sql2 = "delete from vte_pdfmaker_relblockcol where relblockid=?";
    //$idelreportsortcolsqlresult = $adb->pquery($sql2, array($relblockid));
}
else
{
    $relblockid = $adb->getUniqueID($table_prefix.'_pdfmaker_relblocks');
    //crmv@19166
    $sql = "INSERT INTO ".$table_prefix."_pdfmaker_relblocks (relblockid, name, module, secmodule, block) VALUES (?,?,?,?,?)";
    $params = array($relblockid,$name,$module,$secmodule,$adb->getEmptyClob(true));
    $adb->pquery($sql,$params);
    $adb->updateClob($table_prefix.'_pdfmaker_relblocks','block',"relblockid=$relblockid",$block);
    //crmv@19166e

    $selectedcolumnstring = $_REQUEST["selectedColumnsString"];
    $selectedcolumns = explode(";",$selectedcolumnstring);

    for($i=0 ;$i<count($selectedcolumns);$i++)
    {
        if(!empty($selectedcolumns[$i]))
        {
            $icolumnsql = "insert into ".$table_prefix."_pdfmaker_relblockcol (relblockid,colid,columnname) values (?,?,?)";
            $icolumnsqlresult = $adb->pquery($icolumnsql, array($relblockid,$i,(decode_html($selectedcolumns[$i]))));
        }
    }
}

$idelrelcriteriasql = "delete from ".$table_prefix."_pdfmaker_relblckcri where relblockid=?";
$idelrelcriteriasqlresult = $adb->pquery($idelrelcriteriasql, array($relblockid));

$idelrelcriteriagroupsql = "delete from ".$table_prefix."_pdfmaker_relblckcri_g where relblockid=?";
$idelrelcriteriagroupsqlresult = $adb->pquery($idelrelcriteriagroupsql, array($relblockid));

if (count($advft_criteria) > 0)
{
    foreach($advft_criteria as $column_index => $column_condition)
    {
        if(empty($column_condition)) continue;

        $adv_filter_column = $column_condition["columnname"];
        $adv_filter_comparator = $column_condition["comparator"];
        $adv_filter_value = $column_condition["value"];
        $adv_filter_column_condition = $column_condition["columncondition"];
        $adv_filter_groupid = $column_condition["groupid"];

        $column_info = explode(":",$adv_filter_column);
        $temp_val = explode(",",$adv_filter_value);
        if(($column_info[4] == 'D' || ($column_info[4] == 'T' && $column_info[1] != 'time_start' && $column_info[1] != 'time_end') || ($column_info[4] == 'DT')) && ($column_info[4] != '' && $adv_filter_value != '' ))
        {
            $val = Array();
            for($x=0;$x<count($temp_val);$x++) {
                list($temp_date,$temp_time) = explode(" ",$temp_val[$x]);
                $temp_date = getValidDBInsertDateValue(trim($temp_date));
                $val[$x] = $temp_date;
                if($temp_time != '') $val[$x] = $val[$x].' '.$temp_time;
            }
            $adv_filter_value = implode(",",$val);
        }

        $irelcriteriasql = "insert into ".$table_prefix."_pdfmaker_relblckcri(relblockid,colid,columnname,comparator,value,groupid,column_condition) values (?,?,?,?,?,?,?)";
        $irelcriteriaresult = $adb->pquery($irelcriteriasql, array($relblockid, $column_index, $adv_filter_column, $adv_filter_comparator, $adv_filter_value, $adv_filter_groupid, $adv_filter_column_condition));

        // Update the condition expression for the group to which the condition column belongs
        $groupConditionExpression = '';
        if(!empty($advft_criteria_groups[$adv_filter_groupid]["conditionexpression"])) {
            $groupConditionExpression = $advft_criteria_groups[$adv_filter_groupid]["conditionexpression"];
        }
        $groupConditionExpression = $groupConditionExpression .' '. $column_index .' '. $adv_filter_column_condition;
        $advft_criteria_groups[$adv_filter_groupid]["conditionexpression"] = $groupConditionExpression;
    }
}

if (count($advft_criteria_groups) > 0)
{
    foreach($advft_criteria_groups as $group_index => $group_condition_info)
    {
        if(empty($group_condition_info)) continue;

        $irelcriteriagroupsql = "insert into ".$table_prefix."_pdfmaker_relblckcri_g (groupid,relblockid,group_condition,condition_expression) values (?,?,?,?)";
        $irelcriteriagroupresult = $adb->pquery($irelcriteriagroupsql, array($group_index, $relblockid, $group_condition_info["groupcondition"], $group_condition_info["conditionexpression"]));
    }
}


$idelreportdatefiltersql = "delete from ".$table_prefix."_pdfmaker_relblckdfilt where datefilterid=?";
$idelreportdatefiltersqlresult = $adb->pquery($idelreportdatefiltersql, array($relblockid));

$ireportmodulesql = "insert into ".$table_prefix."_pdfmaker_relblckdfilt (datefilterid,datecolumnname,datefilter,startdate,enddate) values (?,?,?,?,?)";
$ireportmoduleresult = $adb->pquery($ireportmodulesql, array($relblockid, $stdDateFilterField, $stdDateFilter, $startdate, $enddate));

//echo '<script>window.opener.location.href =window.opener.location.href;self.close();</script>';

header("Location:index.php?module=PDFMaker&action=PDFMakerAjax&file=ListRelatedBlocks&parenttab=Tools&pdfmodule=".$rel_module);

?>