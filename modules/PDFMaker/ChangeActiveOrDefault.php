<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@27623
global $adb,$current_user,$table_prefix;
$templateid=$_REQUEST["templateid"];
$subject=$_REQUEST["subjectChanged"];

$sql="SELECT *
      FROM ".$table_prefix."_pdfmaker_userstatus      
      WHERE templateid=? AND userid=?";
$result = $adb->pquery($sql,array($templateid,$current_user->id));

if($adb->num_rows($result)>0) {
	if($subject=="active") {
		$is_active = $adb->query_result($result,0,'is_active');
		if ($is_active == 0) {
			$new_is_active = 1;
			$new_is_default = 0;
		} else {
			$new_is_active = 0;
			$new_is_default = $adb->query_result($result,0,'is_default');
		}
		$sql = 'UPDATE '.$table_prefix.'_pdfmaker_userstatus SET is_active=?, is_default=? WHERE templateid=? AND userid=?';
		$params = array($new_is_active,$new_is_default,$templateid,$current_user->id);
	} elseif($subject=="default") {
		$is_default = $adb->query_result($result,0,'is_default');
		if ($is_default == 0) {
			$new_is_default = 1;
		} else {
			$new_is_default = 0;
		}
		$sql = 'UPDATE '.$table_prefix.'_pdfmaker_userstatus SET is_default=? WHERE templateid=? AND userid=?';
		$params = array($new_is_default,$templateid,$current_user->id);
	}
} else {
	if ($subject=="active") {
		$sql="INSERT INTO ".$table_prefix."_pdfmaker_userstatus(templateid,userid,is_active,is_default) VALUES(?,?,0,0)";
	} elseif($subject=="default") {
		$sql="INSERT INTO ".$table_prefix."_pdfmaker_userstatus(templateid,userid,is_active,is_default) VALUES(?,?,1,1)";
	}
	$params = array($templateid,$current_user->id);
}
$adb->pquery($sql,$params);

$sql="SELECT is_default, module
      FROM ".$table_prefix."_pdfmaker_userstatus
      INNER JOIN ".$table_prefix."_pdfmaker ON ".$table_prefix."_pdfmaker.templateid = ".$table_prefix."_pdfmaker_userstatus.templateid
      WHERE ".$table_prefix."_pdfmaker.templateid=? AND userid=?";
$result = $adb->pquery($sql,array($templateid,$current_user->id));
$new_is_default = $adb->query_result($result,0,"is_default");
$module = $adb->query_result($result,0,"module");

if($new_is_default=="1") {
	$result = $adb->pquery('select templateid from '.$table_prefix.'_pdfmaker where module = ? and templateid <> ?',array($module, $templateid));
	if ($result && $adb->num_rows($result) > 0) {
		$templates = array();
		while ($row = $adb->fetchByAssoc($result)) {
			$templates[] = $row['templateid'];
		}
		$sql5 = 'update '.$table_prefix.'_pdfmaker_userstatus SET is_default=0 WHERE is_default=1 AND userid=? AND templateid IN ('.implode(',',$templates).')';
		$adb->pquery($sql5, array($current_user->id, $module));
	}
}

echo '<meta http-equiv="refresh" content="0;url=index.php?action=DetailViewPDFTemplate&module=PDFMaker&templateid='.$templateid.'&parenttab=Tools" />';
//crmv@27623e
?>