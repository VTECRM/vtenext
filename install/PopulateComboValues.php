<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

require_once('include/language/en_us.lang.php');
require_once('install/ComboStrings.php');
require_once('include/ComboUtil.php');

/**
 *  Class which handles the population of the combo values
 * 
 *
 */
class PopulateComboValues
{
	var $app_list_strings;


	/** 
	 * To populate the default combo values for the combo vte_tables
	 * @param $values -- values:: Type string array
	 * @param $tableName -- tablename:: Type string 
	 */
	function insertComboValues($values, $tableName,$picklistid)
	{
		global $log;
		$log->debug("Entering insertComboValues(".$values.", ".$tableName.") method ...");
		global $adb,$table_prefix;
		//inserting the value in the vte_picklistvalues_seq for the getting uniqueID for each picklist values...
		$i=0;
		foreach ($values as $val => $cal)
		{
			$picklist_valueid = getUniquePicklistID();
			$id = $adb->getUniqueID($table_prefix.'_'.$tableName);
			if($val != '')
			{
			//crmv@fix Calendar
				if ($tableName == 'eventstatus'){
					$history = 0;
					if ($val == 'Held')
						$history = 1;
					$params = array($id, $val, 1, $picklist_valueid,$history);
				}
				elseif ($tableName == 'taskstatus'){
					$history = 0;
					if ($val == 'Completed' || $val == 'Deferred')
						$history = 1;
					$params = array($id, $val, 1, $picklist_valueid,$history);					
				}
				else 
					$params = array($id, $val, 1, $picklist_valueid);
				$adb->pquery("insert into ".$table_prefix."_$tableName values(".generateQuestionMarks($params).")", $params);
			}
			//crmv@fix Calendar end
			else
			{
				$params = array($id, '--None--', 1, $picklist_valueid);
				$adb->pquery("insert into ".$table_prefix."_$tableName values(?,?,?,?)", $params);
			}

			//Default entries for role2picklist relation has been inserted..

			$sql="select roleid from ".$table_prefix."_role";
			$role_result = $adb->pquery($sql, array());
			$numrow = $adb->num_rows($role_result);
			for($k=0; $k < $numrow; $k ++)
			{
				$roleid = $adb->query_result($role_result,$k,'roleid');
				$params = array($roleid, $picklist_valueid, $picklistid, $i);
				$adb->pquery("insert into ".$table_prefix."_role2picklist values(?,?,?,?)", $params);
			}

			$i++;
		}
	

		$log->debug("Exiting insertComboValues method ...");
	}


	/** 
	 * To populate the combo vte_tables at startup time
	 */

	function create_tables () 
	{
		global $log;
		$log->debug("Entering create_tables () method ...");
				
		global $app_list_strings,$adb,$table_prefix;
		global $combo_strings;
		$comboRes = $adb->query("SELECT distinct fieldname FROM ".$table_prefix."_field WHERE (uitype IN ('15') OR fieldname = 'salutationtype')");
		$noOfCombos = $adb->num_rows($comboRes);
		for($i=0; $i<$noOfCombos; $i++)
		{
			$comTab = $adb->query_result($comboRes, $i, 'fieldname');
			if (isset($combo_strings[$comTab."_dom"])) {
				$picklistid = $adb->getUniqueID($table_prefix."_picklist");
				$params = array($picklistid, $comTab);
				$picklist_qry = "insert into ".$table_prefix."_picklist values(?,?)";
				$adb->pquery($picklist_qry, $params);
	
				$this->insertComboValues($combo_strings[$comTab."_dom"],$comTab,$picklistid);
			}
		}
		//we have to decide what are all the picklist and picklist values are non editable
		//presence = 0 means you cannot edit the picklist value
		//presence = 1 means you can edit the picklist value
		$noneditable_tables = Array("ticketstatus","taskstatus","eventstatus","faqstatus","quotestage","postatus","sostatus","invoicestatus","activitytype");
		$noneditable_values = Array(
						"Closed Won"=>"sales_stage",
						"Closed Lost"=>"sales_stage",
					   );
		foreach($noneditable_tables as $picklistname)
		{
			$adb->pquery("update ".$table_prefix."_".$picklistname." set PRESENCE=0", array());
		}
		foreach($noneditable_values as $picklistname => $value)
		{
			$adb->pquery("update ".$table_prefix."_$value set PRESENCE=0 where $value=?", array($picklistname));
		}

		$log->debug("Exiting create_tables () method ...");

	}


	function create_nonpicklist_tables ()
	{
		global $log;
		$log->debug("Entering create_nonpicklist_tables () method ...");
				
		global $app_list_strings,$adb,$table_prefix;
		global $combo_strings;
		// uitype -> 16 - Non standard picklist, 115 - User status, 83 - Tax Class
		$comboRes = $adb->query("SELECT distinct fieldname FROM ".$table_prefix."_field WHERE uitype IN ('16','115','83') AND fieldname NOT IN ('hdnTaxType','email_flag') and ".$table_prefix."_field.presence in (0,2)");
		$noOfCombos = $adb->num_rows($comboRes);
		for($i=0; $i<$noOfCombos; $i++)
		{
			$comTab = $adb->query_result($comboRes, $i, 'fieldname');
			$this->insertNonPicklistValues($combo_strings[$comTab."_dom"],$comTab);
		}
		$log->debug("Exiting create_tables () method ...");
	}
	function insertNonPicklistValues($values, $tableName)
	{
		global $log;
		$log->debug("Entering insertNonPicklistValues(".$values.", ".$tableName.") method ...");
		global $adb,$table_prefix;
		$i=0;
		foreach ($values as $val => $cal)
		{
				$id = $adb->getUniqueID($table_prefix.'_'.$tableName);
				if($val != '')
				{
					$params = array($id, $val, $i ,1);
				}
				else
				{
					$params = array($id, '--None--', $i ,1);
				}
				$adb->pquery("insert into ".$table_prefix."_$tableName values(?,?,?,?)", $params);
				$i++;
		}
		$log->debug("Exiting insertNonPicklistValues method ...");
	}

}
?>
