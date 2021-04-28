<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@49398 - many things */
global $adb;
global $log, $current_user, $metaLogs; // crmv@49398
global $table_prefix;

$cvid = (int) vtlib_purify($_REQUEST["record"]);
if ($_REQUEST["duplicate"] == 'true') {
	$cvid = '';
}
$cvmodule = vtlib_purify($_REQUEST["cvmodule"]);
$parenttab = getParentTab();
$return_action = vtlib_purify($_REQUEST["return_action"]);

// crmv@198469 - avoid warning on counts
$adv_filter_col = array();
$adv_filter_option = array();
$adv_filter_value = array();
$columnslist = array();
// crmv@198469e

if($cvmodule != "") {
	$cv_tabid = getTabid($cvmodule);
	$viewname = vtlib_purify($_REQUEST["viewName"]);
	if(strtolower($default_charset) != 'utf-8')
		$viewname = htmlentities($viewname);

	//setStatus=0(Default);1(Private);2(Pending);3(Public).
	//If status is Private ie. 1, only the user created the customview can see it
	//If status is Pending ie. 2, on approval by the admin, the status will become Public ie. 3 and a user can see the customviews created by him and his sub-ordinates.
	if(isset($_REQUEST['setStatus']) && $_REQUEST['setStatus'] != '' && $_REQUEST['setStatus'] != '1')
		$status = $_REQUEST['setStatus'];
	elseif(isset($_REQUEST['setStatus']) && $_REQUEST['setStatus'] != '' && $_REQUEST['setStatus'] == '1')
		$status = CV_STATUS_PENDING;
	else
		$status = CV_STATUS_PRIVATE;
	$userid = $current_user->id;

	if(isset($_REQUEST["setDefault"]))
	{
	  $setdefault = 1;
	}else
	{
	  $setdefault = 0;
	}

	if(isset($_REQUEST["setMetrics"]))
        {
          $setmetrics = 1;
        }else
        {
          $setmetrics = 0;
        }

    // crmv@49398
	if (isset($_REQUEST["setMobile"]) && empty($_REQUEST['report'])) {
		$setmobile = 1;
	} else {
		$setmobile = 0;
	}
	// crmv@49398e

 	//$allKeys = array_keys($HTTP_POST_VARS);
	//this is  will cause only the chosen fields to be added to the vte_cvcolumnlist table
	$allKeys = array_keys($_REQUEST);

	//<<<<<<<columns>>>>>>>>>>
	for ($i=0;$i<count($allKeys);$i++) {
	   $string = substr($allKeys[$i], 0, 6);
	   if($string == "column") {
		   //the contusion, will cause only the chosen fields to be added to the vte_cvcolumnlist table
		   if($_REQUEST[$allKeys[$i]] != "")
        	   $columnslist[] = $_REQUEST[$allKeys[$i]];
   	   }
	}
	//<<<<<<<columns>>>>>>>>>

	//<<<<<<<standardfilters>>>>>>>>>
	$stdfiltercolumn = $_REQUEST["stdDateFilterField"];
	$std_filter_list["columnname"] = $stdfiltercolumn;
	$stdcriteria = $_REQUEST["stdDateFilter"];
	$std_filter_list["stdfilter"] = $stdcriteria;
	$startdate = $_REQUEST["startdate"];
	$enddate = $_REQUEST["enddate"];

	//crmv@10468
	if (isset($_REQUEST["only_month_and_day"]) && $_REQUEST["only_month_and_day"] == 'on') {
		$only_month_and_day = 1;
	}
	else
		$only_month_and_day = 0;
	//crmv@10468e
	//crmv@7635
	$cv_column_order_by = $_REQUEST["cv_column_order_by"];
	$cv_order_by_type  = $_REQUEST["cv_order_by_type"];
	//crmv@7635e
	if($stdcriteria == "custom")
	{
		$startdate = getDBInsertDateValue($startdate);
		$enddate = getDBInsertDateValue($enddate);
	}
	$std_filter_list["startdate"] = $startdate;
	$std_filter_list["enddate"]=$enddate;
	if(empty($startdate) && empty($enddate))
		unset($std_filter_list);
	//<<<<<<<standardfilters>>>>>>>>>

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
				$adv_filter_value[] = str_replace(", ",",",trim(vtlib_purify($_REQUEST[$allKeys[$i]])));
			}
   	   }
   	   //crmv@29615e
	}
	//crmv@16312 end
	//<<<<<<<advancedfilter>>>>>>>>

	$reportid = $_REQUEST['report'];	//crmv@31775

	if(!$cvid){
		$genCVid = $adb->getUniqueID($table_prefix."_customview");
		if($genCVid != "") {
			//crmv@24727
			if ($setdefault == 1) {
				$adb->pquery("UPDATE ".$table_prefix."_customview SET setdefault = 0 WHERE setdefault = 1 AND entitytype = ? AND userid = ?",array($cvmodule,$userid));
			}
			//crmv@24727e
			$customviewsql = "INSERT INTO ".$table_prefix."_customview(cvid, viewname, setdefault, setmetrics, setmobile, entitytype, status, userid, reportid) VALUES (?,?,?,?,?,?,?,?,?)";
			$customviewparams = array($genCVid, $viewname, $setdefault , $setmetrics, $setmobile, $cvmodule, $status, $userid, $reportid);	//crmv@24727	//crmv@31775
			$customviewresult = $adb->pquery($customviewsql, $customviewparams);
			$log->info("CustomView :: Save :: ".$table_prefix."_customview created successfully");

			//crmv@102334
			$MHW = ModuleHomeView::getInstance($cvmodule, $userid);
			$modhomeid = $MHW->getModHomeId();
			if (!empty($modhomeid)) {
				if($setdefault == 1) {
					$adb->pquery("UPDATE {$MHW->table} SET cvid = ? WHERE modhomeid = ? and userid = ? and tabid = ?",
							array($genCVid, $MHW->getModHomeId(), $current_user->id, $cv_tabid)); //crmv@113953
				}
			}
			//crmv@102334e
			$log->info("CustomView :: Save :: setdefault upated successfully");

			if($customviewresult) {
				if(isset($columnslist)) {
					//crmv@7635
					$columnsql = "INSERT INTO tbl_s_cvorderby (cvid, columnindex, columnname,ordertype)
						VALUES (".$genCVid.", 1, ".$adb->quote($cv_column_order_by).",".$adb->quote($cv_order_by_type).")";
					$columnresult = $adb->query($columnsql);
					//crmv@7635e
					for($i=0;$i<count($columnslist);$i++) {
						$columnsql = "INSERT INTO ".$table_prefix."_cvcolumnlist (cvid, columnindex, columnname) VALUES (?,?,?)";
						$columnparams = array($genCVid, $i, $columnslist[$i]);
						$columnresult = $adb->pquery($columnsql, $columnparams);
					}
					$log->info("CustomView :: Save :: ".$table_prefix."_cvcolumnlist created successfully");
					if($std_filter_list["columnname"] !=""){
						//crmv@10468
						$stdfiltersql = "INSERT INTO ".$table_prefix."_cvstdfilter(cvid,columnname,stdfilter,startdate,enddate,only_month_and_day) VALUES (?,?,?,?,?,?)";
						$stdfilterparams = array($genCVid, $std_filter_list["columnname"], $std_filter_list["stdfilter"], $adb->formatDate($std_filter_list["startdate"], true), $adb->formatDate($std_filter_list["enddate"], true), $only_month_and_day);	//crmv@20986
						//crmv@10468e
						$stdfilterresult = $adb->pquery($stdfiltersql, $stdfilterparams);
						$log->info("CustomView :: Save :: ".$table_prefix."_cvstdfilter created successfully");
					}
					for($i=0;$i<count($adv_filter_col);$i++) {
						$col = explode(":",$adv_filter_col[$i]);
						$temp_val = explode(",",$adv_filter_value[$i]);
						//crmv@122071
						$tabid = $cv_tabid;
						if ($tabid == 9) $tabid = '9,16';
						$result = $adb->pquery("SELECT uitype FROM {$table_prefix}_field WHERE tabid IN ($tabid) AND tablename=? AND columnname=?", array($col[0], $col[1]));//crmv@208173
						$uitype = '';
						if ($result && $adb->num_rows($result) > 0) {
							$uitype = $adb->query_result($result,0,"uitype");
						}
						//crmv@122071e
						//crmv@128159
						if (SDK::isUitype($uitype)) {
							// DO NOTHING
						}
						//crmv@128159e
						elseif($col[4] == 'D' || ($col[4] == 'T' && $uitype != 1) || $col[4] == 'DT') {	//crmv@122071
							$val = Array();
							for($x=0;$x<count($temp_val);$x++) {
								//if date and time given then we have to convert the date and leave the time as it is, if date only given then temp_time value will be empty
								list($temp_date,$temp_time) = explode(" ",$temp_val[$x]);
								$temp_date = getDBInsertDateValue(trim($temp_date));
								if(trim($temp_time) != '')
									$temp_date .= ' '.$temp_time;
								$val[$x] = $temp_date;
							}
							$adv_filter_value[$i] = implode(", ",$val);
						}
						$advfiltersql = "INSERT INTO ".$table_prefix."_cvadvfilter(cvid,columnindex,columnname,comparator,value) VALUES (?,?,?,?,?)";
						$advfilterparams = array($genCVid, $i, $adv_filter_col[$i], $adv_filter_option[$i], htmlspecialchars_decode($adv_filter_value[$i]));  // crmv@171479
						$advfilterresult = $adb->pquery($advfiltersql, $advfilterparams);
					}

					if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_ADDFILTER, $genCVid); // crmv@49398
					$log->info("CustomView :: Save :: ".$table_prefix."_cvadvfilter created successfully");
				}
			}
			$cvid = $genCVid;
		}
	} else {
		if(is_admin($current_user) || $current_user->id) {
			//crmv@24727	//crmv@31775
			if ($setdefault == 1) {
				$adb->pquery("UPDATE ".$table_prefix."_customview SET setdefault = 0 WHERE setdefault = 1 AND entitytype = ? AND userid = ?",array($cvmodule,$userid));
			}
			$updatecvsql = "UPDATE ".$table_prefix."_customview SET viewname = ?, setdefault = ? , setmetrics = ?, setmobile = ?, status = ?, reportid = ? WHERE cvid = ?";
			$updatecvparams = array($viewname,$setdefault, $setmetrics, $setmobile, $status, $reportid, $cvid);
			//crmv@24727e	//crmv@31775e
			$updatecvresult = $adb->pquery($updatecvsql, $updatecvparams);
			$log->info("CustomView :: Save :: ".$table_prefix."_customview upated successfully".$cvid);

			//crmv@102334
			$MHW = ModuleHomeView::getInstance($cvmodule,$userid);
			$modhomecvid = $MHW->getModHomeCvId();	// check if the current tab is a tab with list
			if (!empty($modhomecvid)) {
				if($setdefault == 1) {
					$adb->pquery("UPDATE {$MHW->table} SET cvid = ? WHERE modhomeid = ? and userid = ? and tabid = ?",
						array($cvid, $MHW->getModHomeId(), $current_user->id, $cv_tabid));
				} elseif ($modhomecvid == $cvid) {	// if setdefault 0 of the current view -> set All as default
					$result = $adb->pquery("SELECT cvid FROM {$table_prefix}_customview WHERE entitytype = ? and viewname = ?", array($cvmodule,'All'));
					$adb->pquery("UPDATE {$MHW->table} SET cvid = ? WHERE modhomeid = ? and userid = ? and tabid = ?",
						array($adb->query_result($result,0,'cvid'), $MHW->getModHomeId(), $current_user->id, $cv_tabid));
				}
			}
			//crmv@102334e
			$log->info("CustomView :: Save :: setdefault upated successfully".$cvid);

			$deletesql = "DELETE FROM ".$table_prefix."_cvcolumnlist WHERE cvid = ?";
			$deleteresult = $adb->pquery($deletesql, array($cvid));

			$deletesql = "DELETE FROM ".$table_prefix."_cvstdfilter WHERE cvid = ?";
			$deleteresult = $adb->pquery($deletesql, array($cvid));

			$deletesql = "DELETE FROM ".$table_prefix."_cvadvfilter WHERE cvid = ?";
			$deleteresult = $adb->pquery($deletesql, array($cvid));
			//crmv@7635
			$deletesql = "DELETE FROM tbl_s_cvorderby WHERE cvid = ?";
			$deleteresult = $adb->pquery($deletesql, array($cvid));
			//crmv@7635e
			$log->info("CustomView :: Save :: ".$table_prefix."_cvcolumnlist,cvstdfilter,cvadvfilter deleted successfully before update".$genCVid);

			$genCVid = $cvid;
			if($updatecvresult) {
				if(isset($columnslist)) {
					//crmv@7635
					$columnsql = "INSERT INTO tbl_s_cvorderby (cvid, columnindex, columnname,ordertype)
						VALUES (".$genCVid.", 1, ".$adb->quote($cv_column_order_by).",".$adb->quote($cv_order_by_type).")";
					$columnresult = $adb->query($columnsql);
					//crmv@7635e
					for($i=0;$i<count($columnslist);$i++) {
						$columnsql = "INSERT INTO ".$table_prefix."_cvcolumnlist (cvid, columnindex, columnname) VALUES (?,?,?)";
						$columnparams = array($genCVid, $i, $columnslist[$i]);
						$columnresult = $adb->pquery($columnsql, $columnparams);
					}
					$log->info("CustomView :: Save :: ".$table_prefix."_cvcolumnlist update successfully".$genCVid);
					if($std_filter_list["columnname"] !="") {
						//crmv@10468
						$stdfiltersql = "INSERT INTO ".$table_prefix."_cvstdfilter(cvid,columnname,stdfilter,startdate,enddate,only_month_and_day) VALUES (?,?,?,?,?,?)";
						$stdfilterparams = array($genCVid, $std_filter_list["columnname"], $std_filter_list["stdfilter"], $adb->formatDate($std_filter_list["startdate"], true), $adb->formatDate($std_filter_list["enddate"], true), $only_month_and_day);	//crmv@20986
						//crmv@10468e
						$stdfilterresult = $adb->pquery($stdfiltersql, $stdfilterparams);
						$log->info("CustomView :: Save :: ".$table_prefix."_cvstdfilter update successfully".$genCVid);
					}
					for($i=0;$i<count($adv_filter_col);$i++) {
						$col = explode(":",$adv_filter_col[$i]);
						$temp_val = explode(",",$adv_filter_value[$i]);
						//crmv@122071
						$tabid = $cv_tabid;
						if ($tabid == 9) $tabid = '9,16';
                        $result = $adb->pquery("SELECT uitype FROM {$table_prefix}_field WHERE tabid IN ($tabid) AND tablename=? AND columnname=?", array($col[0], $col[1]));//crmv@208173
                        $uitype = '';
						if ($result && $adb->num_rows($result) > 0) {
							$uitype = $adb->query_result($result,0,"uitype");
						}
						//crmv@122071e
						//crmv@128159
						if (SDK::isUitype($uitype)) {
							$sdk_file = SDK::getUitypeFile('php','formatvalue',$uitype);
							if ($sdk_file != '') {
								// DO NOTHING
							}
						}
						//crmv@128159e
						elseif($col[4] == 'D' || ($col[4] == 'T' && $uitype != 1) || $col[4] == 'DT') {	//crmv@122071
							$val = Array();
							for($x=0;$x<count($temp_val);$x++) {
								//if date and time given then we have to convert the date and leave the time as it is, if date only given then temp_time value will be empty
								list($temp_date,$temp_time) = explode(" ",$temp_val[$x]);
								$temp_date = getDBInsertDateValue(trim($temp_date));
								if(trim($temp_time) != '')
									$temp_date .= ' '.$temp_time;
								$val[$x] = $temp_date;
							}
							$adv_filter_value[$i] = implode(", ",$val);
						}
						$advfiltersql = "INSERT INTO ".$table_prefix."_cvadvfilter (cvid,columnindex,columnname,comparator,value) VALUES (?,?,?,?,?)";
						$advfilterparams = array($genCVid, $i, $adv_filter_col[$i], $adv_filter_option[$i], htmlspecialchars_decode($adv_filter_value[$i]));  // crmv@171479
						$advfilterresult = $adb->pquery($advfiltersql, $advfilterparams);
					}
					if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFILTER, $genCVid); // crmv@49398
					$log->info("CustomView :: Save :: ".$table_prefix."_cvadvfilter update successfully".$cvid);
				}
			}
			//crmv@29617
			$result = $adb->pquery('SELECT * FROM vte_modnot_follow_cv WHERE cvid = ? AND userid = ?',array($cvid,$current_user->id));
			if ($result && $adb->num_rows($result) > 0) {
				$adb->pquery('UPDATE vte_modnot_follow_cv SET count = ? WHERE cvid = ? AND userid = ?',array(-1,$cvid,$current_user->id));
			}
			//crmv@29617e
		}
	}
	//crmv@OPER6288
	require_once('include/utils/KanbanView.php');
	$kanbanLib = KanbanLib::getInstance();
	$kanbanLib->save($cvid,$_REQUEST['kanban_json']);
	//crmv@OPER6288e
}
//crmv@18167
header("Location: index.php?action=$return_action&parenttab=$parenttab&module=$cvmodule&viewname=$cvid&override_orderby=true");
//crmv@18167 end
?>