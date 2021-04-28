<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings;
global $app_list_strings;
global $app_strings;
global $current_user;
$focus = 0;
global $theme;
global $log,$default_charset;

//<<<<<>>>>>>
global $oCustomView;
//<<<<<>>>>>>

$error_msg = '';
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once('modules/CustomView/CustomView.php');

$cv_module = vtlib_purify($_REQUEST['module']);

$recordid = vtlib_purify($_REQUEST['record']);

$smarty = new VteSmarty();
$smarty->assign("MOD", $mod_strings);
$smarty->assign("CATEGORY", getParentTab());
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("MODULE",$cv_module);
$smarty->assign("MODULELABEL",getTranslatedString($cv_module,$cv_module));
$smarty->assign("CVMODULE", $cv_module);
$smarty->assign("CUSTOMVIEWID",$recordid);
$smarty->assign("DATEFORMAT",$current_user->date_format);
$smarty->assign("JS_DATEFORMAT",parse_calendardate($app_strings['NTC_DATE_FORMAT']));
$smarty->assign("DATE_JS", '<script>userDateFormat = "'.$current_user->date_format.'" </script>');
if($recordid == "")
{
	$oCustomView = CRMEntity::getInstance('CustomView'); // crmv@115329
	$modulecollist = $oCustomView->getModuleColumnsList($cv_module);
	//crmv@34627
	$selectedcolumnslist = '';
	if ($_REQUEST['mode'] == 'report_columns') {
		$selectedcolumnslist = Zend_Json::decode($_REQUEST['columns']);
		for($i=1;$i<10;$i++)
		{
			$choosecolhtml = $oCustomView->getByModule_ColumnsHTML($cv_module,$modulecollist,$selectedcolumnslist[$i-1]);
			$smarty->assign("CHOOSECOLUMN".$i,$choosecolhtml);
		}
	} else {
	//crmv@34627e
		$choosecolhtml = $oCustomView->getByModule_ColumnsHTML($cv_module,$modulecollist);
		for($i=1;$i<10;$i++)
		{
			$smarty->assign("CHOOSECOLUMN".$i,$choosecolhtml);
		}
	}	//crmv@34627
	$log->info('CustomView :: Successfully got ColumnsList for the module'.$cv_module);
	//step2
	$stdfilterhtml = $oCustomView->getStdFilterCriteria();
	$log->info('CustomView :: Successfully got StandardFilter for the module'.$cv_module);
	$stdfiltercolhtml = getStdFilterHTML($cv_module);
	$stdfilterjs = $oCustomView->getCriteriaJS();

	//step4
	$advfilterhtml = getAdvCriteriaHTML();

	$smarty->assign("CV_ORDERBY",$choosecolhtml); //crmv@7635s
	$smarty->assign("CV_ORDERBY_TYPE","ASC");

	$log->info('CustomView :: Successfully got AdvancedFilter for the module'.$cv_module);
	for($i=1;$i<6;$i++)
	{
		$smarty->assign("FOPTION".$i,$advfilterhtml);
		$smarty->assign("BLOCK".$i,$choosecolhtml);
	}

	$smarty->assign("STDFILTERCOLUMNS",$stdfiltercolhtml);
	$smarty->assign("STDCOLUMNSCOUNT",count($stdfiltercolhtml));
	$smarty->assign("STDFILTERCRITERIA",$stdfilterhtml);
	$smarty->assign("STDFILTER_JAVASCRIPT",$stdfilterjs);

	if (!empty($oCustomView->mandatoryvalues)) {
		$smarty->assign("MANDATORYCHECK",implode(",",array_unique($oCustomView->mandatoryvalues)));
	}
	if (!empty($oCustomView->showvalues)) {
		$smarty->assign("SHOWVALUES",implode(",",$oCustomView->showvalues));
	}
	$data_type[] = $oCustomView->data_type;
	$smarty->assign("DATATYPE",$data_type);

}
else
{
	$oCustomView = CRMEntity::getInstance('CustomView', $cv_module); // crmv@115329
	$now_action = vtlib_purify($_REQUEST['action']);
	if($_REQUEST['duplicate'] == 'true' || $oCustomView->isPermittedCustomView($recordid,$now_action,$oCustomView->customviewmodule) == 'yes')
	{
		$customviewdtls = $oCustomView->getCustomViewByCvid($recordid);
		//crmv@35052
		if($_REQUEST['duplicate'] == 'true') {
			unset($customviewdtls["setdefault"]);
			unset($customviewdtls["setmetrics"]);
			unset($customviewdtls["status"]);
		}
		//crmv@35052e
		$log->info('CustomView :: Successfully got ViewDetails for the Viewid'.$recordid);
		$modulecollist = $oCustomView->getModuleColumnsList($cv_module);
		$modulecollist_advfilter = $oCustomView->getModuleColumnsList($cv_module,true,'cv_advfilter');	//crmv@103450
		//crmv@34627
		if ($_REQUEST['mode'] == 'report_columns')
			$selectedcolumnslist = Zend_Json::decode($_REQUEST['columns']);
		else
		//crmv@34627
			$selectedcolumnslist = $oCustomView->getColumnsListByCvid($recordid);
		$log->info('CustomView :: Successfully got ColumnsList for the Viewid'.$recordid);

		$smarty->assign("VIEWNAME",$customviewdtls["viewname"]);

		if($customviewdtls["setdefault"] == 1)
		{
			$smarty->assign("CHECKED","checked");
		}
		if($customviewdtls["setmetrics"] == 1)
		{
			$smarty->assign("MCHECKED","checked");
		}
		// crmv@49398
		if($customviewdtls["setmobile"] == 1)
		{
			$smarty->assign("APPCHECKED","checked");
		}
		// crmv@49398e
		$status = $customviewdtls["status"];
		$smarty->assign("STATUS",$status);

		for($i=1;$i<10;$i++)
		{
			$choosecolhtml = $oCustomView->getByModule_ColumnsHTML($cv_module,$modulecollist,$selectedcolumnslist[$i-1]);
			$smarty->assign("CHOOSECOLUMN".$i,$choosecolhtml);
		}

		$stdfilterlist = $oCustomView->getStdFilterByCvid($recordid);
		$log->info('CustomView :: Successfully got Standard Filter for the Viewid'.$recordid);
		$stdfilterlist["stdfilter"] = ($stdfilterlist["stdfilter"] != "") ? ($stdfilterlist["stdfilter"]) : ("custom");
		$stdfilterhtml = $oCustomView->getStdFilterCriteria($stdfilterlist["stdfilter"]);
		$stdfiltercolhtml = getStdFilterHTML($cv_module,$stdfilterlist["columnname"]);
		$stdfilterjs = $oCustomView->getCriteriaJS();

		if(isset($stdfilterlist["startdate"]) && isset($stdfilterlist["enddate"]))
		{
			//crmv@20986
			$smarty->assign("STARTDATE",getDisplayDate(substr($stdfilterlist["startdate"],0,10)));
			$smarty->assign("ENDDATE",getDisplayDate(substr($stdfilterlist["enddate"],0,10)));
			//crmv@20986e
		}
		else{
			$smarty->assign("STARTDATE",$stdfilterlist["startdate"]);
			$smarty->assign("ENDDATE",$stdfilterlist["enddate"]);
		}

		$advfilterlist = $oCustomView->getAdvFilterByCvid($recordid);
		$log->info('CustomView :: Successfully got Advanced Filter for the Viewid'.$recordid,'info');
		for($i=1;$i<6;$i++)
		{
			$advfilterhtml = getAdvCriteriaHTML($advfilterlist[$i-1]["comparator"]);
			$advcolumnhtml = $oCustomView->getByModule_ColumnsHTML($cv_module,$modulecollist_advfilter,$advfilterlist[$i-1]["columnname"],false);	//crmv@34627	//crmv@103450
			$smarty->assign("FOPTION".$i,$advfilterhtml);
			$smarty->assign("BLOCK".$i,$advcolumnhtml);
			$col = explode(":",$advfilterlist[$i-1]["columnname"]);
			$temp_val = explode(",",$advfilterlist[$i-1]["value"]);
			$and_text = "&nbsp;".$mod_strings['LBL_AND'];
			//crmv@122071
			$tabid = getTabid($cv_module);
			if ($tabid == 9) $tabid = '9,16';
			$result = $adb->query("SELECT uitype FROM {$table_prefix}_field WHERE tabid IN ($tabid) AND tablename='".$col[0]."' AND columnname='".$col[1]."'");
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
			//crmv@29615
		    elseif($col[4] == 'D' || $col[4] == 'DT')
		    {
    			$val = Array();
				for($x=0;$x<count($temp_val);$x++)
					if(trim($temp_val[$x] != ""))
						$val[$x] = getDisplayDate(trim($temp_val[$x]));
				$advfilterlist[$i-1]["value"] = implode(", ",$val);
				//$and_text = "<em old='(yyyy-mm-dd)'>(".$current_user->date_format.")</em>&nbsp;".$mod_strings['LBL_AND'];
		    }
		    elseif($col[4] == 'T' && $uitype != 1)	//crmv@122071
		    {
		    	//crmv@fix
		    	$temp_val[0] = getDisplayDate(substr($temp_val[0],0,10))." ".substr($temp_val[0],11);
		    	$advfilterlist[$i-1]["value"] = implode(", ",$temp_val);
		    	//crmv@fix e
		    	//$and_text = "<em old='(yyyy-mm-dd)'>(".$current_user->date_format." hh:mm:ss)</em>&nbsp;".$mod_strings['LBL_AND'];
		    }
		    /*
			elseif($col[1] == 'time_start' ||  $col[1] == 'time_end')
	            $and_text = "hh:mm&nbsp;".$mod_strings['LBL_AND'];
		    elseif($col[4] == 'C')
		    	$and_text = "( Yes / No )&nbsp;".$mod_strings['LBL_AND'];
		    else
			*/
		    //crmv@29615e
	    	$and_text = "&nbsp;".$mod_strings['LBL_AND'];
			$smarty->assign("VALUE".$i,$advfilterlist[$i-1]["value"]);
			$smarty->assign("AND_TEXT".$i,$and_text);
		}
		$smarty->assign("STDFILTERCOLUMNS",$stdfiltercolhtml);
		$smarty->assign("STDCOLUMNSCOUNT",count($stdfiltercolhtml));
		$smarty->assign("STDFILTERCRITERIA",$stdfilterhtml);
		$smarty->assign("STDFILTER_JAVASCRIPT",$stdfilterjs);
		$smarty->assign("MANDATORYCHECK",implode(",",array_unique($oCustomView->mandatoryvalues)));
		$smarty->assign("SHOWVALUES",implode(",",$oCustomView->showvalues));
		$smarty->assign("EXIST","true");
		if ($_REQUEST['duplicate'] == 'true')
			$smarty->assign("DUPLICATE","true");

		//crmv@7635
		$crmv_orderby = $oCustomView->getOrderByFilterByCvid($recordid);
		if($crmv_orderby[0]["columnname"] != "")  {
			$crmv_advcolumnhtml = $oCustomView->getByModule_ColumnsHTML($cv_module,$modulecollist,$crmv_orderby[0]["columnname"],false);	//crmv@34627
			$smarty->assign("CV_ORDERBY",$crmv_advcolumnhtml);
			$smarty->assign("CV_ORDERBY_TYPE",$crmv_orderby[0]["ordertype"]);
		} else  {
			$smarty->assign("CV_ORDERBY",$oCustomView->getByModule_ColumnsHTML($cv_module,$modulecollist,'',false));	//crmv@34627
			$smarty->assign("CV_ORDERBY_TYPE","ASC");
		}
		//crmv@7635s
		//crmv@10468
		if ($stdfilterlist["only_month_and_day"] == 1)
			$smarty->assign("ONLY_MONTH_AND_DAY",'checked');
		else
			$smarty->assign("ONLY_MONTH_AND_DAY",'');
		//crmv@10468e

		$smarty->assign("VIEWNAME",$customviewdtls["viewname"]);

        $data_type[] = $oCustomView->data_type;
        $smarty->assign("DATATYPE",$data_type);

        //crmv@31775
        global $adb, $table_prefix;
		$smarty->assign("REPORT_ID", $customviewdtls['reportid']);
		$res = $adb->pquery("select reportname from {$table_prefix}_report where reportid = ?", array($customviewdtls['reportid']));
		if ($res) {
			$reportname = $adb->query_result($res, 0, 'reportname');
		}
		$smarty->assign("REPORT_NAME", getTranslatedString($reportname,'Reports'));
		//crmv@31775e
		
		//crmv@OPER6288
		require_once('include/utils/KanbanView.php');
		$kanbanView = KanbanView::getInstance($recordid);
		$smarty->assign("KANBAN_JSON", $kanbanView->getJson());
		//crmv@OPER6288e
	}
    else
	{
		echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
		echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;'>
			<table border='0' cellpadding='5' cellspacing='0' width='98%'>
			<tbody><tr>
			<td rowspan='2' width='11%'><img src='". resourcever('denied.gif')."' ></td>
			<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'><span class='genHeaderSmall'>$app_strings[LBL_PERMISSION]</span></td>
			</tr>
			<tr>
			<td class='small' align='right' nowrap='nowrap'>
			<a href='javascript:window.history.back();'>$app_strings[LBL_GO_BACK]</a><br>
			</td>
			</tr>
			</tbody></table>
			</div>";
		echo "</td></tr></table>";
		exit;
	}
}

$smarty->assign("RETURN_MODULE", $cv_module);
if(!empty($_REQUEST['return_action'])) $return_action = $_REQUEST['return_action'];
elseif($cv_module == "Calendar") $return_action = "ListView";
else $return_action = "index";

if($recordid == '')
	$act = $mod_strings['LBL_NEW'];
else
	$act = $mod_strings['LBL_EDIT'];

$smarty->assign("ACT", $act);
$smarty->assign("RETURN_ACTION", $return_action);

// crmv@146032
// if duplicating or editing a report filter, show it in readonly if the user has no access to reports
$reportPerm = (isPermitted('Reports', 'index') == 'yes');
if ($recordid > 0 && $customviewdtls['reportid'] > 0) {
	// edit or duplicate filter with report
	$smarty->assign("REPORT_FILTER_ACCESS", true);
	$smarty->assign("REPORT_FILTER_READONLY", !$reportPerm);
} else {
	$smarty->assign("REPORT_FILTER_ACCESS", $reportPerm);	//crmv@31775
	$smarty->assign("REPORT_FILTER_READONLY", false);
}
// crmv@146032e

//crmv@34627
if ($_REQUEST['mode'] == 'report_columns')
	$smarty->display("CustomViewColumns.tpl");
else
//crmv@34627e
	$smarty->display("CustomView.tpl");

      /** to get the Advanced filter criteria
	* @param $selected :: Type String (optional)
	* @returns  $AdvCriteria Array in the following format
	* $AdvCriteria = Array( 0 => array('value'=>$tablename:$colname:$fieldname:$fieldlabel,'text'=>$mod_strings[$field label],'selected'=>$selected),
	* 		     1 => array('value'=>$$tablename1:$colname1:$fieldname1:$fieldlabel1,'text'=>$mod_strings[$field label1],'selected'=>$selected),
	*		                             		|
	* 		     n => array('value'=>$$tablenamen:$colnamen:$fieldnamen:$fieldlabeln,'text'=>$mod_strings[$field labeln],'selected'=>$selected))
	*/
function getAdvCriteriaHTML($selected="")
{
	global $app_list_strings;
	$customView = CRMEntity::getInstance('CustomView');
	$adv_filter_options = $customView->getAdvFilterOptions();	//crmv@26161
	$AdvCriteria = array();
	foreach($adv_filter_options as $key=>$value)
	{
		if($selected == $key)
		{
			$advfilter_criteria['value'] = $key;
			$advfilter_criteria['text'] = $value;
			$advfilter_criteria['selected'] = "selected";
		}else
		{
			$advfilter_criteria['value'] = $key;
			$advfilter_criteria['text'] = $value;
			$advfilter_criteria['selected'] = "";
		}
		$AdvCriteria[] = $advfilter_criteria;
	}

	return $AdvCriteria;
}
?>