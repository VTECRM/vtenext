<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

require_once('include/CustomFieldUtil.php');


global $mod_strings, $app_strings, $app_list_strings, $theme, $adb, $log, $table_prefix;

$theme_path = "themes/" . $theme . "/";
$image_path = "themes/images/";

$tabid = vtlib_purify($_REQUEST['tabid']);
$fieldid = vtlib_purify($_REQUEST['fieldid']);

// Set the tab type only during Custom Field creation for Calendar module based on the activity type
if ($fieldid == '' && $_REQUEST['fld_module'] == 'Calendar' && isset($_REQUEST['activity_type'])) {
    $activitytype = vtlib_purify($_REQUEST['activity_type']);
    if ($activitytype == 'E') $tabid = '16';
    if ($activitytype == 'T') $tabid = '9';
} else {
    $tabid = getTabid(vtlib_purify($_REQUEST['fld_module']));
}
$blockid = getBlockId($tabid, 'LBL_CUSTOM_INFORMATION');
if (isset($_REQUEST['uitype']) && $_REQUEST['uitype'] != '') {
    $uitype = vtlib_purify($_REQUEST['uitype']);
} else {
    $uitype = 1;
}

$readonly = '';
$smarty = new VteSmarty();
$cfimagecombo = array($image_path . "text.gif",
    $image_path . "number.gif",
    $image_path . "percent.gif",
    $image_path . "cfcurrency.gif",
    $image_path . "date.gif",
    $image_path . "email.gif",
    $image_path . "phone.gif",
    $image_path . "cfpicklist.gif",
    $image_path . "url.gif",
    $image_path . "checkbox.gif",
    $image_path . "text.gif",
    $image_path . "cfpicklist.gif",
    $image_path . "skype.gif",
    $image_path . "cfpicklist.gif");

$cftextcombo = array($mod_strings['Text'],
    $mod_strings['Number'],
    $mod_strings['Percent'],
    $mod_strings['Currency'],
    $mod_strings['Date'],
    $mod_strings['Email'],
    $mod_strings['Phone'],
    $mod_strings['PickList'],
    $mod_strings['LBL_URL'],
    $mod_strings['LBL_CHECK_BOX'],
    $mod_strings['LBL_TEXT_AREA'],
    $mod_strings['LBL_MULTISELECT_COMBO'],
    $mod_strings['Skype'],
    $mod_strings['Picklistmulti'],
);
$typeVal = array(
    '0' => 'Text',
    '1' => 'Number',
    '2' => 'Percent',
    '3' => 'Currency',
    '4' => 'Date',
    '5' => 'Email',
    '6' => 'Phone',
    '7' => 'Picklist',
    '8' => 'URL',
    '9' => 'Checkbox',
    '11' => 'MultiSelectCombo',
    '12' => 'Skype',
    '13' => 'Picklistmulti');
if (isset($fieldid) && $fieldid != '') {
    $mode = 'edit';
    $customfield_columnname = getCustomFieldData($tabid, $fieldid, 'columnname');
    $customfield_typeofdata = getCustomFieldData($tabid, $fieldid, 'typeofdata');
    $customfield_fieldlabel = getCustomFieldData($tabid, $fieldid, 'fieldlabel');
    $customfield_typename = getCustomFieldTypeName($uitype);
    $fieldtype_lengthvalue = getFldTypeandLengthValue($customfield_typename, $customfield_typeofdata);
    list($fieldtype, $fieldlength, $decimalvalue) = explode(";", $fieldtype_lengthvalue);
    $readonly = "readonly";
    if ($fieldtype == '7' || $fieldtype == '11') {
        $query = "select * from " . $table_prefix . "_" . $adb->sql_escape_string($customfield_columnname);
        $result = $adb->pquery($query, array());
        $fldVal = '';
        while ($row = $adb->fetch_array($result)) {
            $fldVal .= $row[$customfield_columnname];
            $fldVal .= "\n";
        }
        $smarty->assign("PICKLISTVALUE", $fldVal);
    }
    $selectedvalue = $typeVal[$fieldtype];
}


if (isset($_REQUEST["duplicate"]) && $_REQUEST["duplicate"] == "yes") {
    $error = $mod_strings['ERR_CUSTOM_FIELD_WITH_NAME'] . $_REQUEST["fldlabel"] . $mod_strings['ERR_ALREADY_EXISTS'] . ' ' . $mod_strings['ERR_SPECIFY_DIFFERENT_LABEL'];
    $smarty->assign("DUPLICATE_ERROR", $error);
    $customfield_fieldlabel = vtlib_purify($_REQUEST["fldlabel"]);
    $fieldlength = vtlib_purify($_REQUEST["fldlength"]);
    $decimalvalue = vtlib_purify($_REQUEST["flddecimal"]);
    $fldVal = vtlib_purify($_REQUEST["fldPickList"]);
    $selectedvalue = $typeVal[$_REQUEST["fldType"]];
} elseif ($fieldid == '') {
    $selectedvalue = "0";
}

if ($mode == 'edit')
    $disable_str = 'disabled';
else
    $disable_str = '';

$output = '';

$combo_output = '';
for ($i = 0; $i < count($cftextcombo); $i++) {
    if ($selectedvalue == $i && $fieldid != '')
        $sel_val = 'selected';
    else
        $sel_val = '';
    $combo_output .= '<a href="javascript:void(0);" onClick="makeFieldSelected(this,' . $i . ',' . $blockid . ');" id="field' . $i . '" style="text-decoration:none;background-image:url(' . $cfimagecombo[$i] . ');" class="customMnu" ' . $disable_str . '>' . $cftextcombo[$i] . '</a>';

}
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("FLDVAL", $fldVal);
$smarty->assign("THEME", $theme);
$smarty->assign("FLD_MODULE", vtlib_purify($_REQUEST['fld_module']));
$smarty->assign("MODE", $mode);
$smarty->assign("BLOCKID", $blockid);
$smarty->assign("ACTIVITYTYPE", $activitytype);
$smarty->assign("FIELDID", $fieldid);
$smarty->assign("CUSTOMNAMEFIELD_COLUMNNAME", $customfield_columnname);
$smarty->assign("CUSTOMNAMEFIELD_TYPENAME", $customfield_typename);
$smarty->assign("CUSTOMNAMEFIELD_FIELDLABEL", $customfield_fieldlabel);
$smarty->assign("FIELDLENGTH", $fieldlength);
$smarty->assign("DECIMALVALUE", $decimalvalue);
$smarty->assign("READONLY", $readonly);
$smarty->assign("SELECTEDVALUE", $selectedvalue);
$smarty->assign("COMBOOUTPUT", $combo_output);
//crmv@171581
$smarty->display("CreateCustomField.tpl");
