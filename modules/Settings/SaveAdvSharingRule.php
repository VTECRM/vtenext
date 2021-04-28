<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb;
global $log;
global $default_charset; //crmv@94866
$cvid = (int) $_REQUEST["shareId"];
$module = $_REQUEST["sharing_module"];
$parenttab = $_REQUEST["parenttab"];
$return_action = $_REQUEST["return_action"];
$return_module = $_REQUEST["return_module"];
$adv_sharing = $_REQUEST["adv_sharing"];
$record = $_REQUEST["userid"];
$title = $_REQUEST["title"];
$description = $_REQUEST["description"];
if($module != "")
{
	$allKeys = array_keys($_REQUEST); 
	
	//<<<<<<<advancedfilter>>>>>>>>>
	//crmv@16312
	for ($i=0;$i<count($allKeys);$i++) {
	   $string = substr($allKeys[$i], 0, 4);
		if($string == "fcol" && $_REQUEST[$allKeys[$i]] !== null &&	$_REQUEST[$allKeys[$i]] !== '') {
           	$adv_filter_col[] = $_REQUEST[$allKeys[$i]];
   	   }
	}
	for ($i=0;$i<count($allKeys);$i++) {
	   $string = substr($allKeys[$i], 0, 3);
		if($string == "fop" && $_REQUEST[$allKeys[$i]] !== null && $_REQUEST[$allKeys[$i]] !== '') {
           	$adv_filter_option[] = $_REQUEST[$allKeys[$i]];
   	   }
	}
	for ($i=0;$i<count($allKeys);$i++) {
   	   $string = substr($allKeys[$i], 0, 4);
   	   //crmv@29615
		if($string == "fval") {
			$index = substr($allKeys[$i], 4);
			if ($_REQUEST["fcol{$index}"] !== null && $_REQUEST["fcol{$index}"] !== '') {
				$adv_filter_value[] = str_replace(", ",",",trim(html_entity_decode(vtlib_purify($_REQUEST[$allKeys[$i]]),ENT_QUOTES,$default_charset))); //crmv@94866
			}
   	   }
   	   //crmv@29615e
	}
	//crmv@16312 end
	//<<<<<<<advancedfilter>>>>>>>>

	if(!$cvid)
	{
		$genCVid = $adb->getUniqueID("tbl_s_advancedrule");
		if($genCVid != "")
		{
			$customviewsql = "INSERT INTO tbl_s_advancedrule(advrule_id, module_name, title, description) VALUES (?,?,?,?)";
			$customviewparams = array($genCVid, $module, $title, $description);
			$customviewresult = $adb->pquery($customviewsql, $customviewparams);
			$log->info("CustomView :: Save :: tbl_s_advancedrule created successfully");
			if($customviewresult)
			{
					for($i=0;$i<count($adv_filter_col);$i++)
					{
						$col = explode(":",$adv_filter_col[$i]);
						$temp_val = explode(",",$adv_filter_value[$i]);
						if($col[4] == 'D' || ($col[4] == 'T' && $col[1] != 'time_start' && $col[1] != 'time_end') || $col[4] == 'DT')
						{
							$val = Array();
							for($x=0;$x<count($temp_val);$x++)
							{
								//if date and time given then we have to convert the date and leave the time as it is, if date only given then temp_time value will be empty
								list($temp_date,$temp_time) = explode(" ",$temp_val[$x]);
								$temp_date = getDBInsertDateValue(trim($temp_date));
								if(trim($temp_time) != '')
									$temp_date .= ' '.$temp_time;
								$val[$x] = $temp_date;
							}
							$adv_filter_value[$i] = implode(", ",$val);
						}
						$advfiltersql = "INSERT INTO tbl_s_advancedrulefilters(advrule_id,columnindex,columnname,comparator,value) VALUES (?,?,?,?,?)";
						$advfilterparams = array($genCVid, $i, $adv_filter_col[$i], $adv_filter_option[$i], $adv_filter_value[$i]);
						$advfilterresult = $adb->pquery($advfiltersql, $advfilterparams);
					}
					$log->info("CustomView :: Save :: tbl_s_advancedrulefilters created successfully");
				}
			$cvid = $genCVid;
		}
	}else
	{
		$genCVid = $cvid;
		$updatecvsql = "UPDATE tbl_s_advancedrule
				SET title = ?, description = ? WHERE advrule_id = ?";
		$updatecvparams = array($title, $description,$genCVid);
		$updatecvresult = $adb->pquery($updatecvsql, $updatecvparams);
		$log->info("CustomView :: Save :: tbl_s_advancedrule upated successfully".$genCVid);
		$deletesql = "DELETE FROM tbl_s_advancedrulefilters WHERE advrule_id = ?";
		$deleteresult = $adb->pquery($deletesql, array($cvid));

		$log->info("AdvFilters :: Save :: tbl_s_advancedrulefilters deleted successfully before update".$genCVid);

		if($updatecvresult)
		{
				for($i=0;$i<count($adv_filter_col);$i++)
				{
					$col = explode(":",$adv_filter_col[$i]);
					$temp_val = explode(",",$adv_filter_value[$i]);
					if($col[4] == 'D' || ($col[4] == 'T' && $col[1] != 'time_start' && $col[1] != 'time_end') || $col[4] == 'DT')
					{
						$val = Array();
						for($x=0;$x<count($temp_val);$x++){

								//if date and time given then we have to convert the date and leave the time as it is, if date only given then temp_time value will be empty
								list($temp_date,$temp_time) = explode(" ",$temp_val[$x]);
								$temp_date = getDBInsertDateValue(trim($temp_date));
								if(trim($temp_time) != '')
									$temp_date .= ' '.$temp_time;
								$val[$x] = $temp_date;
			
						}
						$adv_filter_value[$i] = implode(", ",$val);	
					}
					$advfiltersql = "INSERT INTO tbl_s_advancedrulefilters (advrule_id,columnindex,columnname,comparator,value) VALUES (?,?,?,?,?)";
					$advfilterparams = array($genCVid, $i, $adv_filter_col[$i], $adv_filter_option[$i], $adv_filter_value[$i]);
					$advfilterresult = $adb->pquery($advfiltersql, $advfilterparams);
				}
				$log->info("CustomView :: Save :: tbl_s_advancedrulefilters update successfully".$genCVid);
		}
	}
	//crmv@42329
	//update rules for all users
	$sql = "SELECT id FROM tbl_s_advancedrule_rel where advrule_id = ?";
	$params = Array($genCVid);
	$res = $adb->pquery($sql,$params);
	if ($res && $adb->num_rows($res)>0){
		require_once('modules/Users/CreateUserPrivilegeFile.php');
		while($row = $adb->fetchByAssoc($res,-1,false)){
			createUserSharingPrivilegesfile($row['id']);
		}
	}
	//crmv@42329e	
}

header("Location: index.php?action=$return_action&parenttab=$parenttab&module=$return_module&record=$record&adv_sharing=$adv_sharing");
?>