<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/*
 * File containing methods to proceed with the ui validation for all the forms
 *
 */
/**
 * Get field validation information
 */
//crmv@49510 crmv@59245 crmv@83877 crmv@112297
function getDBValidationData($tablearray, $tabid='', &$otherInfo=null, $focus=null) {	//crmv@96450
	if($tabid != '') {
		global $adb, $table_prefix, $current_user;
		
		require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		require_once('modules/Settings/ModuleMaker/ModuleMakerGenerator.php');
		$MMUtils = new ModuleMakerUtils();
		$MMSteps = new ProcessModuleMakerSteps($MMUtils);
		$MMGen = new ModuleMakerGenerator($MMUtils, $MMSteps);
		
		// crmv@142262
		if (vtlib_isModuleActive('Conditionals')) {
			$cache = RCache::getInstance();
			$permissions = $cache->get('conditional_permissions');
			if (empty($permissions) && is_object($focus)) {
				// recreate the cache
				$conditionals_obj = CRMEntity::getInstance('Conditionals');
				$conditionals_obj->Initialize(getTabModuleName($tabid),$tabid,$focus->column_fields);
				$permissions = $cache->get('conditional_permissions');
			}
		}
		// crmv@142262e
		
		$fieldModuleName = getTabModuleName($tabid);
		$profileList = getCurrentUserProfileList();		
		// crmv@165801
		$query = "SELECT {$table_prefix}_field.fieldid, uitype, MIN(mandatory) AS mandatory
				FROM {$table_prefix}_field
				LEFT JOIN {$table_prefix}_profile2field ON ({$table_prefix}_profile2field.fieldid = {$table_prefix}_field.fieldid AND {$table_prefix}_profile2field.profileid IN (".generateQuestionMarks($profileList)."))
				WHERE {$table_prefix}_field.displaytype IN (1,3) AND {$table_prefix}_field.presence in (0,2) AND {$table_prefix}_field.tabid=?
				GROUP BY {$table_prefix}_field.fieldid, uitype";
		$query = "SELECT {$table_prefix}_field.*, tmp.mandatory FROM {$table_prefix}_field INNER JOIN ($query) tmp ON tmp.fieldid = {$table_prefix}_field.fieldid";
		// crmv@165801e
		$params = Array($profileList, $tabid);
		$fieldres = $adb->pquery($query, $params);
		$fieldinfos = Array();
		$fieldRowI = 0;
		while($fieldrow = $adb->fetch_array($fieldres)) {
			$fieldlabel = getTranslatedString($fieldrow['fieldlabel'], $fieldModuleName);	
			$fieldname = $fieldrow['fieldname'];
			/* crmv@190504 moved code below */
			$typeofdata = getFinalTypeOfData($fieldrow['typeofdata'],$fieldrow['mandatory']);
			//crmv@112297 apply conditionals rules
			if (isset($permissions[$fieldname])) {
				$mandatory = $permissions[$fieldname]['mandatory'];
				if ($mandatory) {
					$typeofdata = $MMGen->makeTODMandatory($typeofdata);
				} else {
					$typeofdata = $MMGen->makeTODOptional($typeofdata);					
				}
			}
			//crmv@112297e
			// crmv@190504
			$webservice_field = WebserviceField::fromQueryResult($adb,$fieldres,$fieldRowI);
			if (in_array($webservice_field->getFieldDataType(),array('multipicklist','file')) && $_REQUEST['action'] == 'EditView') {				
				$fieldname = $fieldname.'[]';
			}
			// crmv@190504e
			$fieldinfos[$fieldname] = Array($fieldlabel => $typeofdata);
			$otherInfo['fielduitype'][$fieldname] = intval($fieldrow['uitype']);
			$otherInfo['fieldwstype'][$fieldname] = $webservice_field->getFieldDataType();
			$fieldRowI++;
		}
		//crmv@96450
		if ($fieldModuleName == 'Processes' && !empty($focus)) {
			require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
			$processDynaFormObj = ProcessDynaForm::getInstance();
			$processDynaFormObj->addValidationData($focus,$otherInfo,$fieldinfos);
		}
		//crmv@96450e
		return $fieldinfos;
	} else {
		//  TODO: Call the old API defined below in the file?
		return getDBValidationData_510($tablearray, $tabid);
	}
}
//crmv@49510e crmv@59245e crmv@83877e crmv@112297e
 
/** Function to get the details for fieldlabels for a given table array
  * @param $tablearray -- tablearray:: Type string array (table names in array)
  * @param $tabid -- tabid:: Type integer 
  * @returns $fieldName_array -- fieldName_array:: Type string array (field name details)
  *
 */


function getDBValidationData_510($tablearray,$tabid='')
{
  global $log;
  $log->debug("Entering getDBValidationData(".$tablearray.",".$tabid.") method ...");
  $sql = '';
  $params = array();
  $tab_con = "";
  $numValues = count($tablearray);
  global $adb,$mod_strings,$table_prefix;

  if($tabid!='') $tab_con = ' and tabid='. $adb->sql_escape_string($tabid);
	
  for($i=0;$i<$numValues;$i++)
  {

  	if(in_array("emails",$tablearray))
  	{
		if($numValues > 1 && $i != $numValues-1)
    	{
			$sql .= "select fieldlabel,fieldname,typeofdata from ".$table_prefix."_field where tablename=? and tabid=10 and ".$table_prefix."_field.presence in (0,2) and displaytype <> 2 union ";
			array_push($params, $tablearray[$i]);	
     	}
   		else
    	{
   			$sql  .= "select fieldlabel,fieldname,typeofdata from ".$table_prefix."_field where tablename=? and tabid=10 and ".$table_prefix."_field.presence in (0,2) and displaytype <> 2 ";
    		array_push($params, $tablearray[$i]);	
		}
  	}
  	else
  	{
    		if($numValues > 1 && $i != $numValues-1)
    		{
      			$sql .= "select fieldlabel,fieldname,typeofdata from ".$table_prefix."_field where tablename=? $tab_con and displaytype in (1,3) and ".$table_prefix."_field.presence in (0,2) union ";
    			array_push($params, $tablearray[$i]);	
			}
    		else
    		{
      			$sql  .= "select fieldlabel,fieldname,typeofdata from ".$table_prefix."_field where tablename=? $tab_con and displaytype in (1,3) and ".$table_prefix."_field.presence in (0,2)";
    			array_push($params, $tablearray[$i]);	
			}
  	}
  }
  $result = $adb->pquery($sql, $params);
  $noofrows = $adb->num_rows($result);
  $fieldModuleName = empty($tabid)? false : getTabModuleName($tabid);
  $fieldName_array = Array();
  for($i=0;$i<$noofrows;$i++)
  {
	// Translate label with reference to module language string
    $fieldlabel = getTranslatedString($adb->query_result($result,$i,'fieldlabel'), $fieldModuleName);
    $fieldname = $adb->query_result($result,$i,'fieldname');
    $typeofdata = $adb->query_result($result,$i,'typeofdata');
   //echo '<br> '.$fieldlabel.'....'.$fieldname.'....'.$typeofdata;
    $fldLabel_array = Array();
    $fldLabel_array[$fieldlabel] = $typeofdata;
    $fieldName_array[$fieldname] = $fldLabel_array;

  }

  
  $log->debug("Exiting getDBValidationData method ...");
  return $fieldName_array;
}