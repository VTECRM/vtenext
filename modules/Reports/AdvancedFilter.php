<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/Zend/Json.php');
if(isset($_REQUEST["record"]) && $_REQUEST['record']!='')
{
	$reportid = vtlib_purify($_REQUEST["record"]);
	$oReport = new Reports($reportid);
	$oReport->getAdvancedFilterList($reportid);

	$oRep = CRMEntity::getInstance('Reports');
	$secondarymodule = '';
	$secondarymodules =Array();
	
	if(!empty($oRep->related_modules[$oReport->primodule])) {
		foreach($oRep->related_modules[$oReport->primodule] as $key=>$value){
			if(isset($_REQUEST["secondarymodule_".$value]))$secondarymodules []= $_REQUEST["secondarymodule_".$value];
		}
	}
	$secondarymodule = implode(":",$secondarymodules);
	
	if($secondarymodule!='')
		$oReport->secmodule = $secondarymodule;
	
	$COLUMNS_BLOCK = getPrimaryColumns_AdvFilterHTML($oReport->primodule);
	$COLUMNS_BLOCK .= getSecondaryColumns_AdvFilterHTML($oReport->secmodule);
	$report_std_filter->assign("COLUMNS_BLOCK", $COLUMNS_BLOCK);
	
	$FILTER_OPTION = getAdvCriteriaHTML_filter();
	$report_std_filter->assign("FOPTION",$FILTER_OPTION);

	$rel_fields = getRelatedFieldColumns();
	$report_std_filter->assign("REL_FIELDS",Zend_Json::encode($rel_fields));
	
	$report_std_filter->assign("CRITERIA_GROUPS",$oReport->advft_criteria);
} else {
	$primarymodule = $_REQUEST["primarymodule"];
	$COLUMNS_BLOCK = getPrimaryColumns_AdvFilterHTML($primarymodule);
	$ogReport =  CRMEntity::getInstance('Reports');
	if(!empty($ogReport->related_modules[$primarymodule])) {
		foreach($ogReport->related_modules[$primarymodule] as $key=>$value){
			//$BLOCK1 .= getSecondaryColumnsHTML($_REQUEST["secondarymodule_".$value]);
			$COLUMNS_BLOCK .= getSecondaryColumns_AdvFilterHTML($_REQUEST["secondarymodule_".$value]);
		}
	}
	$report_std_filter->assign("COLUMNS_BLOCK", $COLUMNS_BLOCK);
	
	$rel_fields = getRelatedFieldColumns();	
	$report_std_filter->assign("REL_FIELDS",Zend_Json::encode($rel_fields));
}

/** Function to get primary columns for an advanced filter
 *  This function accepts The module as an argument
 *  This generate columns of the primary modules for the advanced filter 
 *  It returns a HTML string of combo values 
 */

function getPrimaryColumns_AdvFilterHTML($module,$selected="")
{
    global $ogReport, $app_list_strings, $current_language;
	$mod_strings = return_module_language($current_language,$module);
	$block_listed = array();
    foreach($ogReport->module_list[$module] as $key=>$value)
    {
    	if(isset($ogReport->pri_module_columnslist[$module][$value]) && !$block_listed[$value])
    	{
			$block_listed[$value] = true;
			//crmv@18164 
			$shtml .= "<optgroup label=\"".$app_list_strings['moduleList'][$module]." ".addslashes(getTranslatedString($value,$module))."\" class=\"select\" style=\"border:none\">";
			foreach($ogReport->pri_module_columnslist[$module][$value] as $field=>$fieldlabel)
			{
				$val_trans = addslashes(getTranslatedString($fieldlabel,$module));
				// crmv@67929 - no filters with taxes
				list($table, $column, $label) = explode(':', $field);
				if (strpos($label, 'TaxTotals') !== false || strpos($label, 'TaxProdTotals') !== false) continue;
				// crmv@67929e
				if($selected == $field)
					$shtml .= "<option selected value=\"".$field."\">".$val_trans."</option>";
				else
					$shtml .= "<option value=\"".$field."\">".$val_trans."</option>";
			}
			//crmv@18164 end
       }
    }
    return $shtml;
}



/** Function to get Secondary columns for an advanced filter
 *  This function accepts The module as an argument
 *  This generate columns of the secondary module for the advanced filter 
 *  It returns a HTML string of combo values
 */

function getSecondaryColumns_AdvFilterHTML($module,$selected="")
{
    global $ogReport;
	global $app_list_strings;
    global $current_language;

    if($module != "")
    {
    	$secmodule = explode(":",$module);
    	for($i=0;$i < count($secmodule) ;$i++)
    	{
            $mod_strings = return_module_language($current_language,$secmodule[$i]);
            if(vtlib_isModuleActive($secmodule[$i])){
				$block_listed = array();
				foreach($ogReport->module_list[$secmodule[$i]] as $key=>$value)
                {
					if(isset($ogReport->sec_module_columnslist[$secmodule[$i]][$value]) && !$block_listed[$value])
					{
						$block_listed[$value] = true;
						//crmv@18164 
	                    $shtml .= "<optgroup label=\"".$app_list_strings['moduleList'][$secmodule[$i]]." ".addslashes(getTranslatedString($value,$secmodule[$i]))."\" class=\"select\" style=\"border:none\">";
					  	foreach($ogReport->sec_module_columnslist[$secmodule[$i]][$value] as $field=>$fieldlabel){
							$val_trans = addslashes(getTranslatedString($fieldlabel,$secmodule[$i]));
							// crmv@67929 - can't do calculations with taxes 
							list($table, $column, $label) = explode(':', $field);
							if (strpos($label, 'TaxTotals') !== false || strpos($label, 'TaxProdTotals') !== false) continue;
							// crmv@67929e
							if($selected == $field)
								$shtml .= "<option selected value=\"".$field."\">".$val_trans."</option>";
							else
								$shtml .= "<option value=\"".$field."\">".$val_trans."</option>";
					  	}
					  	//crmv@18164 end
					}
                }
            }
    	}
    }
    return $shtml;
}

function getRelatedColumns($selected=""){
	global $ogReport;
	$rel_fields = $ogReport->adv_rel_fields;
	if($selected!='All'){
		$selected = explode(":",$selected);
	}
	$related_fields = array();
	foreach($rel_fields as $i=>$index){
		$shtml='';
		foreach($index as $key=>$value){
			$fieldarray = explode("::",$value);
			$shtml .= "<option value=\"".$fieldarray[0]."\">".$fieldarray[1]."</option>";
		}
		$related_fields[$i] = $shtml;
	}
	if(!empty($selected) && $selected[4]!='')
		return $related_fields[$selected[4]];
	else if($selected=='All'){
		return $related_fields;
	}
	else
		return ;	
}

function getRelatedFieldColumns($selected=""){
	global $ogReport;
	$rel_fields = $ogReport->adv_rel_fields;
	return $rel_fields;
}

/** Function to get the  advanced filter criteria for an option
 *  This function accepts The option in the advenced filter as an argument
 *  This generate filter criteria for the advanced filter 
 *  It returns a HTML string of combo values
 */

function getAdvCriteriaHTML_filter($selected="")
{
	//crmv@26161
	global $ogReport;
	$adv_filter_options = $ogReport->getAdvFilterOptions();
	//crmv@26161e
	foreach($adv_filter_options as $key=>$value)
	{
		if($selected == $key)
		{
			$shtml .= "<option selected value=\"".$key."\">".$value."</option>";
		}else
		{
			$shtml .= "<option value=\"".$key."\">".$value."</option>";
		}
	}
	return $shtml;
}
?>