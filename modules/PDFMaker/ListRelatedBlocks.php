<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// ITS4YOU TT0093 VlMe N
/* crmv@59054 */

require_once('include/database/PearDatabase.php');

global $adb, $current_user,$table_prefix;

$smarty = new VteSmarty();

$rel_module = $_REQUEST["pdfmodule"];

require_once('include/utils/UserInfoUtil.php');
global $app_strings;
global $mod_strings;
global $theme,$default_charset;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

global $current_language;

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("PARENTTAB", getParentTab());
$smarty->assign("THEME_PATH",$theme_path);
$smarty->assign("IMAGE_PATH",$image_path);

if(isPermitted($currentModule,"EditView") == 'yes'){
	$editing = true;
	$smarty->assign("EDIT","permitted");
	$smarty->assign("EXPORT","yes");
	$smarty->assign("IMPORT","yes");
}
if(isPermitted($currentModule,"Delete") == 'yes'){
	$deleting = true;
	$smarty->assign("DELETE","permitted");
}

$rel_module_id = getTabid($rel_module);

$restricted_modules = array('Emails','Events','Messages'); // crmv@164120 crmv@180240
$Related_Modules = array();

$rsql = "SELECT ".$table_prefix."_tab.name FROM ".$table_prefix."_tab 
		INNER JOIN ".$table_prefix."_relatedlists on ".$table_prefix."_tab.tabid=".$table_prefix."_relatedlists.related_tabid 
		WHERE ".$table_prefix."_tab.isentitytype=1 
		AND ".$table_prefix."_tab.name NOT IN(".generateQuestionMarks($restricted_modules).") 
		AND ".$table_prefix."_tab.presence=0 AND ".$table_prefix."_relatedlists.label!='Activity History'
		AND ".$table_prefix."_relatedlists.tabid = '".$rel_module_id."' AND ".$table_prefix."_tab.tabid != '".$rel_module_id."'";
$relatedmodules = $adb->pquery($rsql,array($restricted_modules));
if($adb->num_rows($relatedmodules)) {
	while($resultrow = $adb->fetch_array($relatedmodules)) {
		$Related_Modules[] = $resultrow['name'];
	}
}

// crmv@106527 - add MyNotes
if (isModuleInstalled('MyNotes') && vtlib_isModuleActive('MyNotes') && isPermitted('MyNotes', 'DetailView')) {
	$notesFocus = CRMEntity::getInstance('MyNotes');
	if ($notesFocus->moduleHasNotes($rel_module)) {
		$Related_Modules[] = 'MyNotes';
	}
}
// crmv@106527e

//crmv@121616
if (SDK::isUitype(220)) {
	$result = $adb->pquery("select fieldname from {$table_prefix}_field where tabid = ? and uitype = ?", array($rel_module_id,220));
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$Related_Modules[] = 'ModLight'.str_replace('ml','',$row['fieldname']);
		}
	}
}
//crmv@121616e

$smarty->assign("RELATEDMODULES",$Related_Modules);

$Related_Blocks =array();
if (!empty($Related_Modules)) {
	$sql = "SELECT * FROM ".$table_prefix."_pdfmaker_relblocks WHERE secmodule IN(".generateQuestionMarks($Related_Modules).") ORDER BY relblockid";
	$result = $adb->pquery($sql,array($Related_Modules));
	while($row = $adb->fetchByAssoc($result))
	{
		 $Edits = array();
		 
	   $Edits[] = "<a href='index.php?module=PDFMaker&action=PDFMakerAjax&file=EditRelatedBlock&mode=add&record=".$row["relblockid"]."'>".$mod_strings["LBL_INSERT_TO_TEXT"]."</a>";
		 
	   if ($editing) $Edits[] = "<a href='index.php?module=PDFMaker&action=PDFMakerAjax&file=EditRelatedBlock&record=".$row["relblockid"]."'>".$app_strings["LBL_EDIT"]."</a>";
		 
		 if ($deleting) $Edits[] = "<a href='javascript: deleteRelBlock(".$row["relblockid"].");'>".$app_strings["LBL_DELETE"]."</a>";
		 
		 
		 $name = "<a href='index.php?module=PDFMaker&action=PDFMakerAjax&file=EditRelatedBlock&mode=add&record=".$row["relblockid"]."'>".$row["name"]."</a>";
		 
		 $edit = implode(" | ", $Edits);
		 
		 $secmodule = getTranslatedString($row["secmodule"],$row["secmodule"]);	//crmv@25443
		 
	   $Related_Blocks[] = array("blockname" => $name, "secmodule" => $secmodule, "edit" => $edit);
	}
}
$smarty->assign("RELATEDBLOCKS",$Related_Blocks);

include_once("version.php");
$smarty->assign("VERSION",$version);

$smarty->assign("REL_MODULE",$rel_module);

$smarty->display("modules/PDFMaker/ListRelatedBlocks.tpl");
?>