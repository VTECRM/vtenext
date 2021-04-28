<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $calpath;
global $app_strings,$mod_strings;
global $theme;
global $log;
global $table_prefix;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once('include/database/PearDatabase.php');
require_once('data/CRMEntity.php');
require_once("modules/Reports/Reports.php");

class RelBlockRun extends CRMEntity
{

	var $primarymodule;
	var $secondarymodule;
	var $orderbylistsql;
	var $orderbylistcolumns;

	var $selectcolumns;
	var $groupbylist;
	var $reportname;
	var $totallist;

	var $_groupinglist  = false;
	var $_columnslist    = false;
	var	$_stdfilterlist = false;
	var	$_columnstotallist = false;
	var	$_advfiltersql = false;

	var $convert_currency = array('Potentials_Amount', 'Accounts_Annual_Revenue', 'Leads_Annual_Revenue', 'Campaigns_Budget_Cost',
									'Campaigns_Actual_Cost', 'Campaigns_Expected_Revenue', 'Campaigns_Actual_ROI', 'Campaigns_Expected_ROI');
	//var $add_currency_sym_in_headers = array('Amount', 'Unit_Price', 'Total', 'Sub_Total', 'S&H_Amount', 'Discount_Amount', 'Adjustment');
	var $append_currency_symbol_to_value = array('hdnDiscountAmount','txtAdjustment','hdnSubTotal','hdnGrandTotal','hdnTaxType','Products_Unit_Price','Services_Price',
						'Invoice_Total', 'Invoice_Sub_Total', 'Invoice_S&H_Amount', 'Invoice_Discount_Amount', 'Invoice_Adjustment',
						'Quotes_Total', 'Quotes_Sub_Total', 'Quotes_S&H_Amount', 'Quotes_Discount_Amount', 'Quotes_Adjustment',
						'SalesOrder_Total', 'SalesOrder_Sub_Total', 'SalesOrder_S&H_Amount', 'SalesOrder_Discount_Amount', 'SalesOrder_Adjustment',
						'PurchaseOrder_Total', 'PurchaseOrder_Sub_Total', 'PurchaseOrder_S&H_Amount', 'PurchaseOrder_Discount_Amount', 'PurchaseOrder_Adjustment'
						);
	var $ui10_fields = array();


	function __construct($crmid, $relblockid, $sorcemodule, $relatedmodule)
	{
		//$oReport = new Reports($reportid);
		$this->crmid = $crmid;
		$this->relblockid = $relblockid;
		$this->primarymodule = $sorcemodule;
		$this->secondarymodule = $relatedmodule;
	}

	function getQueryColumnsList($relblockid)
	{
		// Have we initialized information already?
		if($this->_columnslist !== false) {
			return $this->_columnslist;
		}
		
		$this->hasInventoryColumns = false;	//crmv@73172

		global $adb;
		global $modules;
		global $log,$current_user,$current_language;
		global $table_prefix, $showfullusername;	//crmv@121616
		$ssql = "select ".$table_prefix."_pdfmaker_relblockcol.* from ".$table_prefix."_pdfmaker_relblocks ";
		$ssql .= " left join ".$table_prefix."_pdfmaker_relblockcol on ".$table_prefix."_pdfmaker_relblockcol.relblockid = ".$table_prefix."_pdfmaker_relblocks.relblockid";
		$ssql .= " where ".$table_prefix."_pdfmaker_relblocks.relblockid = ?";
		$ssql .= " order by ".$table_prefix."_pdfmaker_relblockcol.colid";
		$result = $adb->pquery($ssql, array($relblockid));
		$permitted_fields = Array();

		while($columnslistrow = $adb->fetch_array($result))
		{
			$fieldname ="";
			$fieldcolname = $columnslistrow["columnname"];
			list($tablename,$colname,$module_field,$fieldname,$single) = explode(":",$fieldcolname);
			list($module,$field) = explode("_",$module_field);
			if ($tablename == $table_prefix.'_inventoryproductrel') $this->hasInventoryColumns = true;	//crmv@73172
			$inventory_fields = array('quantity','listprice','serviceid','productid','discount','comment');
			$inventory_modules = getInventoryModules(); // crmv@64542
			require('user_privileges/requireUserPrivileges.php'); // crmv@39110
			if(sizeof($permitted_fields) == 0 && $is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1)
			{
				list($module,$field) = explode("_",$module_field);
				$permitted_fields = $this->getaccesfield($module);
			}
			if(in_array($module,$inventory_modules)){
				$permitted_fields = array_merge($permitted_fields,$inventory_fields);
			}
			$selectedfields = explode(":",$fieldcolname);
			$querycolumns = $this->getEscapedColumns($selectedfields);

			$mod_strings = return_module_language($current_language,$module);
			$fieldlabel = trim(str_replace($module," ",$selectedfields[2]));
			$mod_arr=explode('_',$fieldlabel);
			$mod = ($mod_arr[0] == '')?$module:$mod_arr[0];
			$fieldlabel = trim(str_replace("_"," ",$fieldlabel));
			//modified code to support i18n issue
			$fld_arr = explode(" ",$fieldlabel);
			$mod_lbl = getTranslatedString($fld_arr[0],$module); //module
			array_shift($fld_arr);
			$fld_lbl_str = implode(" ",$fld_arr);
			$fld_lbl = getTranslatedString($fld_lbl_str,$module); //fieldlabel
			$fieldlabel = $mod_lbl." ".$fld_lbl;
			if((CheckFieldPermission($fieldname,$mod) != 'true' && $colname!="crmid" && (!in_array($fieldname,$inventory_fields) && in_array($module,$inventory_modules))) || empty($fieldname))
			{
				continue;
			}
			else
			{
				$header_label = $selectedfields[3]; // Header label to be displayed in the reports table
				// To check if the field in the report is a custom field
				// and if yes, get the label of this custom field freshly from the vte_field as it would have been changed.
				// Asha - Reference ticket : #4906

				if($querycolumns == "")
				{
					if($selectedfields[4] == 'C')
					{
						$field_label_data = explode("_",$selectedfields[2]);
						$module= $field_label_data[0];
						if($module!=$this->primarymodule)
							$columnslist[$fieldcolname] = "case when (".$selectedfields[0].".".$selectedfields[1]."='1')then 'yes' else case when (".$table_prefix."_crmentity$module.crmid !='') then 'no' else '-' end end as '$header_label'";
						else
							$columnslist[$fieldcolname] = "case when (".$selectedfields[0].".".$selectedfields[1]."='1')then 'yes' else case when (".$table_prefix."_crmentity.crmid !='') then 'no' else '-' end end as '$header_label'";
					}
					elseif($selectedfields[0] == $table_prefix.'_activity' && $selectedfields[1] == 'status')
					{
						$columnslist[$fieldcolname] = " case when (".$table_prefix."_activity.status not like '') then ".$table_prefix."_activity.status else ".$table_prefix."_activity.eventstatus end as Status";
					}
					elseif($selectedfields[0] == $table_prefix.'_activity' && $selectedfields[1] == 'date_start')
					{
                                            $columnslist[$fieldcolname] = "cast(concat(".$table_prefix."_activity.date_start,'  ',".$table_prefix."_activity.time_start) as DATETIME) as Start_Date_and_Time";
					}
					elseif(stristr($selectedfields[0],$table_prefix."_users") && ($selectedfields[1] == 'user_name') && $module_field != 'Products_Handler' && $module_field!='Services_Owner')
					{
						/* crmv@126096 : hide this code and do the replacement in InventoryPDF::convertRelatedBlocks()
						$temp_module_from_tablename = str_replace($table_prefix."_users","",$selectedfields[0]);
						if($module!=$this->primarymodule){
							$condition = "and ".$table_prefix."_crmentity".$module.".crmid!=''";
						} else {
							$condition = "and ".$table_prefix."_crmentity.crmid!=''";
						}
						//crmv@121616
						if($temp_module_from_tablename == $module) {
							if ($showfullusername) {
								$columnSqlArr = array($selectedfields[0].'.first_name','" "',$selectedfields[0].'.last_name');
								$columnslist[$fieldcolname] = " case when (".$selectedfields[0].".user_name not like '' $condition) then ".$adb->sql_concat($columnSqlArr)." else ".$table_prefix."_groups".$module.".groupname end as 'assigned_user_id'";
							} else {
								$columnslist[$fieldcolname] = " case when (".$selectedfields[0].".user_name not like '' $condition) then ".$selectedfields[0].".user_name else ".$table_prefix."_groups".$module.".groupname end as 'assigned_user_id'";
							}
						} else { //Some Fields can't assigned to groups so case avoided (fields like inventory manager)
							if ($showfullusername) {
								$columnSqlArr = array($selectedfields[0].'.first_name','" "',$selectedfields[0].'.last_name');
								$columnslist[$fieldcolname] = $adb->sql_concat($columnSqlArr)." as '".$header_label."'";
							} else {
								$columnslist[$fieldcolname] = $selectedfields[0].".user_name as '".$header_label."'";
							}
						}
						//crmv@121616e
						*/
						$columnslist[$fieldcolname] = $selectedfields[0].".id as '".$header_label."'";
					}
					elseif(stristr($selectedfields[0],$table_prefix."_users") && ($selectedfields[1] == 'user_name') && $module_field == 'Products_Handler')//Products cannot be assiged to group only to handler so group is not included
					{
						$columnslist[$fieldcolname] = $selectedfields[0].".user_name as '".$header_label."'";
					}
					elseif($selectedfields[0] == $table_prefix."_crmentity".$this->primarymodule)
					{
						$columnslist[$fieldcolname] = $table_prefix."_crmentity.".$selectedfields[1]." AS '".$header_label."'";
					}
				    elseif($selectedfields[0] == $table_prefix.'_invoice' && $selectedfields[1] == 'salesorderid')//handled for salesorder fields in Invoice Module Reports
					{
						$columnslist[$fieldcolname] = $table_prefix."_salesorderInvoice.subject	AS '".$header_label."'";
					}
					elseif($selectedfields[0] == $table_prefix.'_campaign' && $selectedfields[1] == 'product_id')//handled for product fields in Campaigns Module Reports
					{
						$columnslist[$fieldcolname] = $table_prefix."_productsCampaigns.productname AS '".$header_label."'";
					}
					elseif($selectedfields[0] == $table_prefix.'_products' && $selectedfields[1] == 'unit_price')//handled for product fields in Campaigns Module Reports
					{
					$columnslist[$fieldcolname] = "concat(".$selectedfields[0].".currency_id,'::',innerProduct.actual_unit_price) as '". $header_label ."'";
					}
					elseif(in_array($selectedfields[2], $this->append_currency_symbol_to_value)) {
						$columnslist[$fieldcolname] = "concat(".$selectedfields[0].".currency_id,'::',".$selectedfields[0].".".$selectedfields[1].") as '" . $header_label ."'";
					}
					elseif($selectedfields[0] == $table_prefix.'_notes' && ($selectedfields[1] == 'filelocationtype' || $selectedfields[1] == 'filesize' || $selectedfields[1] == 'folderid' || $selectedfields[1]=='filestatus'))//handled for product fields in Campaigns Module Reports
					{
						if($selectedfields[1] == 'filelocationtype'){
							$columnslist[$fieldcolname] = "case ".$selectedfields[0].".".$selectedfields[1]." when 'I' then 'Internal' when 'E' then 'External' else '-' end as '".$header_label."'";
						} else if($selectedfields[1] == 'folderid'){
							$columnslist[$fieldcolname] = $table_prefix."_crmentityfolder.foldername as '".$header_label."'"; // crmv@30967
						} elseif($selectedfields[1] == 'filestatus'){
							$columnslist[$fieldcolname] = "case ".$selectedfields[0].".".$selectedfields[1]." when '1' then 'yes' when '0' then 'no' else '-' end as '".$header_label."'";
						} elseif($selectedfields[1] == 'filesize'){
							$columnslist[$fieldcolname] = "case ".$selectedfields[0].".".$selectedfields[1]." when '' then '-' else concat(".$selectedfields[0].".".$selectedfields[1]."/1024,'  ','KB') end as '".$header_label."'";
						}
					}
					elseif($selectedfields[0] == $table_prefix.'_inventoryproductrel')//handled for product fields in Campaigns Module Reports
					{
						if($selectedfields[1] == 'discount'){
							$columnslist[$fieldcolname] = " case when (".$table_prefix."_inventoryproductrel{$module}.discount_amount != '') then ".$table_prefix."_inventoryproductrel{$module}.discount_amount else ROUND((".$table_prefix."_inventoryproductrel{$module}.listprice * ".$table_prefix."_inventoryproductrel{$module}.quantity * (".$table_prefix."_inventoryproductrel{$module}.discount_percent/100)),3) end as '" . $header_label ."'";
						} else if($selectedfields[1] == 'productid'){
							$columnslist[$fieldcolname] = $table_prefix."_products{$module}.productname as '" . $header_label ."'";
						} else if($selectedfields[1] == 'serviceid'){
							$columnslist[$fieldcolname] = $table_prefix."_service{$module}.servicename as '" . $header_label ."'";
						} else {
							$columnslist[$fieldcolname] = $selectedfields[0].$module.".".$selectedfields[1]." as '".$header_label."'";
						}
					}
					elseif(stristr($selectedfields[1],'cf_')==true && stripos($selectedfields[1],'cf_')==0)
					{
						$columnslist[$fieldcolname] = $selectedfields[0].".".$selectedfields[1]." AS '".$adb->sql_escape_string(decode_html($header_label))."'";
					}
					else
					{
						$columnslist[$fieldcolname] = $selectedfields[0].".".$selectedfields[1]." AS '".$header_label."'";
					}
				}
				else
				{
					$columnslist[$fieldcolname] = $querycolumns;
				}
			}
		}
		// Save the information
		$this->_columnslist = $columnslist;

		$log->info("ReportRun :: Successfully returned getQueryColumnsList".$relblockid);
		return $columnslist;
	}

	/** Function to get field columns based on profile
	 *  @ param $module : Type string
	 *  returns permitted fields in array format
	 */
	function getaccesfield($module)
	{
		global $current_user;
		global $adb;
		global $table_prefix;
		$access_fields = Array();

		$profileList = getCurrentUserProfileList();
		$query = "select ".$table_prefix."_field.fieldname from ".$table_prefix."_field inner join ".$table_prefix."_profile2field on ".$table_prefix."_profile2field.fieldid=".$table_prefix."_field.fieldid inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid where";
		$params = array();
		if($module == "Calendar")
		{
			if (count($profileList) > 0) {
				$query .= " ".$table_prefix."_field.tabid in (9,16) and ".$table_prefix."_field.displaytype in (1,2,3) and ".$table_prefix."_profile2field.visible=0 and ".$table_prefix."_def_org_field.visible=0 and ".$table_prefix."_profile2field.profileid in (". generateQuestionMarks($profileList) .") group by ".$table_prefix."_field.fieldid order by ".$table_prefix."_field.block,".$table_prefix."_field.sequence";
				array_push($params, $profileList);
			} else {
				$query .= " ".$table_prefix."_field.tabid in (9,16) and ".$table_prefix."_field.displaytype in (1,2,3) and ".$table_prefix."_profile2field.visible=0 and ".$table_prefix."_def_org_field.visible=0 group by ".$table_prefix."_field.fieldid order by ".$table_prefix."_field.block,".$table_prefix."_field.sequence";
			}
		}
		else
		{
			array_push($params, $this->primarymodule, $this->secondarymodule);
			if (count($profileList) > 0) {
				$query .= " ".$table_prefix."_field.tabid in (select tabid from ".$table_prefix."_tab where ".$table_prefix."_tab.name in (?,?)) and ".$table_prefix."_field.displaytype in (1,2,3) and ".$table_prefix."_profile2field.visible=0 and ".$table_prefix."_def_org_field.visible=0 and ".$table_prefix."_profile2field.profileid in (". generateQuestionMarks($profileList) .") group by ".$table_prefix."_field.fieldid order by ".$table_prefix."_field.block,".$table_prefix."_field.sequence";
				array_push($params, $profileList);
			} else {
				$query .= " ".$table_prefix."_field.tabid in (select tabid from ".$table_prefix."_tab where ".$table_prefix."_tab.name in (?,?)) and ".$table_prefix."_field.displaytype in (1,2,3) and ".$table_prefix."_profile2field.visible=0 and ".$table_prefix."_def_org_field.visible=0 group by ".$table_prefix."_field.fieldid order by ".$table_prefix."_field.block,".$table_prefix."_field.sequence";
			}
		}
		$result = $adb->pquery($query, $params);

		while($collistrow = $adb->fetch_array($result))
		{
			$access_fields[] = $collistrow["fieldname"];
		}
		//added to include ticketid for Reports module in select columnlist for all users
		if($module == "HelpDesk")
			$access_fields[] = "ticketid";
		return $access_fields;
	}

	/** Function to get Escapedcolumns for the field in case of multiple parents
	 *  @ param $selectedfields : Type Array
	 *  returns the case query for the escaped columns
	 */
	function getEscapedColumns($selectedfields)
	{
		global $table_prefix;
		global $current_user,$adb;
		$fieldname = $selectedfields[3];
		$tmp = explode("_",$selectedfields[2]);
		$module = $tmp[0];

		if($fieldname == "parent_id" && ($module == "HelpDesk" || $module == "Calendar"))
		{
			if($module == "HelpDesk" && $selectedfields[0] == $table_prefix."_crmentityRelHelpDesk")
			{
				$querycolumn = "case ".$table_prefix."_crmentityRelHelpDesk.setype when 'Accounts' then ".$table_prefix."_accountRelHelpDesk.accountname when 'Contacts' then concat(".$table_prefix."_contactdetailsRelHelpDesk.lastname,' ',".$table_prefix."_contactdetailsRelHelpDesk.firstname) End"." '".$selectedfields[2]."', ".$table_prefix."_crmentityRelHelpDesk.setype 'Entity_type'";
				return $querycolumn;
			}
			if($module == "Calendar") {
				$querycolumn = "case ".$table_prefix."_crmentityRelCalendar.setype when 'Accounts' then ".$table_prefix."_accountRelCalendar.accountname when 'Leads' then concat(".$table_prefix."_leaddetailsRelCalendar.lastname,' ',".$table_prefix."_leaddetailsRelCalendar.firstname) when 'Potentials' then ".$table_prefix."_potentialRelCalendar.potentialname when 'Quotes' then ".$table_prefix."_quotesRelCalendar.subject when 'PurchaseOrder' then ".$table_prefix."_purchaseorderRelCalendar.subject when 'Invoice' then ".$table_prefix."_invoiceRelCalendar.subject when 'SalesOrder' then ".$table_prefix."_salesorderRelCalendar.subject when 'HelpDesk' then ".$table_prefix."_troubleticketsRelCalendar.title when 'Campaigns' then ".$table_prefix."_campaignRelCalendar.campaignname End"." '".$selectedfields[2]."', ".$table_prefix."_crmentityRelCalendar.setype 'Entity_type'";
			}
		} elseif($fieldname == "contact_id" && strpos($selectedfields[2],"Contact_Name")) {
			if(($this->primarymodule == 'PurchaseOrder' || $this->primarymodule == 'SalesOrder' || $this->primarymodule == 'Quotes' || $this->primarymodule == 'Invoice' || $this->primarymodule == 'Calendar') && $module==$this->primarymodule) {
				if (getFieldVisibilityPermission("Contacts", $current_user->id, "firstname") == '0')
					$querycolumn = " case when ".$table_prefix."_crmentity.crmid!='' then concat(".$table_prefix."_contactdetails".$this->primarymodule.".lastname,' ',".$table_prefix."_contactdetails".$this->primarymodule.".firstname) else '-' end as contact_id";
				else
					$querycolumn = " case when ".$table_prefix."_crmentity.crmid!='' then ".$table_prefix."_contactdetails".$this->primarymodule.".lastname else '-' end as contact_id";
			}
			if(stristr($this->secondarymodule,$module) && ($module== 'Quotes' || $module== 'SalesOrder' || $module== 'PurchaseOrder' ||$module== 'Calendar' || $module == 'Invoice')) {
				if (getFieldVisibilityPermission("Contacts", $current_user->id, "firstname") == '0')
					$querycolumn = " case when ".$table_prefix."_crmentity".$module.".crmid!='' then concat(".$table_prefix."_contactdetails".$module.".lastname,' ',".$table_prefix."_contactdetails".$module.".firstname) else '-' end as contact_id";
				else
					$querycolumn = " case when ".$table_prefix."_crmentity".$module.".crmid!='' then ".$table_prefix."_contactdetails".$module.".lastname else '-' end as contact_id";
			}
		}
		else{
 			if(stristr($selectedfields[0],$table_prefix."_crmentityRel")){
 				$module = str_replace($table_prefix."_crmentityRel","",$selectedfields[0]);
				$fields_query = $adb->pquery("SELECT ".$table_prefix."_field.fieldname,".$table_prefix."_field.tablename,".$table_prefix."_field.fieldid from ".$table_prefix."_field INNER JOIN ".$table_prefix."_tab on ".$table_prefix."_tab.name = ? WHERE ".$table_prefix."_tab.tabid=".$table_prefix."_field.tabid and ".$table_prefix."_field.fieldname=?",array($module,$selectedfields[3]));

		        if($adb->num_rows($fields_query)>0){
			        for($i=0;$i<$adb->num_rows($fields_query);$i++){
			        	$field_name = $selectedfields[3];
			        	$field_id = $adb->query_result($fields_query,$i,'fieldid');
				        $tab_name = $selectedfields[1];
				        $ui10_modules_query = $adb->pquery("SELECT relmodule FROM ".$table_prefix."_fieldmodulerel WHERE fieldid=?",array($field_id));

				       if($adb->num_rows($ui10_modules_query)>0){
					        $querycolumn = " case ".$table_prefix."_crmentityRel$module.setype";
					        for($j=0;$j<$adb->num_rows($ui10_modules_query);$j++){
					        	$rel_mod = $adb->query_result($ui10_modules_query,$j,'relmodule');
					        	$rel_obj = CRMEntity::getInstance($rel_mod);
					        	vtlib_setup_modulevars($rel_mod, $rel_obj);

								$rel_tab_name = $rel_obj->table_name;
								$link_field = $rel_tab_name."Rel".$module.".".$rel_obj->list_link_field;

								if($rel_mod=="Contacts" || $rel_mod=="Leads"){
									if(getFieldVisibilityPermission($rel_mod,$current_user->id,'firstname')==0){
										$link_field = "concat($link_field,' ',".$rel_tab_name."Rel$module.firstname)";
									}
								}
								$querycolumn.= " when '$rel_mod' then $link_field ";
					        }
					        $querycolumn .= "end as '".$selectedfields[2]."', ".$table_prefix."_crmentityRel$module.setype as 'Entity_type'" ;
				       }
			        }
		        }

			}
			if($fieldname == 'creator'){
				$querycolumn .= "case when (".$table_prefix."_usersModComments.user_name not like '' and ".$table_prefix."_crmentity.crmid!='') then ".$table_prefix."_usersModComments.user_name end as 'ModComments_Creator'";
			}
		}
		return $querycolumn;
	}

	/** Function to get selectedcolumns for the given relblockid
	 *  @ param $relblockid : Type Integer
	 *  returns the query of columnlist for the selected columns
	 */
	/*
	function getSelectedColumnsList($relblockid)
	{

		global $adb;
		global $modules;
		global $log;

		$ssql = "select vte_pdfmaker_relblockcol.* from vte_report inner join vte_selectquery on vte_selectquery.queryid = vte_report.queryid";
		$ssql .= " left join vte_pdfmaker_relblockcol on vte_pdfmaker_relblockcol.queryid = vte_selectquery.queryid where vte_report.relblockid = ? ";
		$ssql .= " order by vte_pdfmaker_relblockcol.colid";

		$result = $adb->pquery($ssql, array($relblockid));
		$noofrows = $adb->num_rows($result);

		if ($this->orderbylistsql != "")
		{
			$sSQL .= $this->orderbylistsql.", ";
		}

		for($i=0; $i<$noofrows; $i++)
		{
			$fieldcolname = $adb->query_result($result,$i,"columnname");
			$ordercolumnsequal = true;
			if($fieldcolname != "")
			{
				for($j=0;$j<count($this->orderbylistcolumns);$j++)
				{
					if($this->orderbylistcolumns[$j] == $fieldcolname)
					{
						$ordercolumnsequal = false;
						break;
					}else
					{
						$ordercolumnsequal = true;
					}
				}
				if($ordercolumnsequal)
				{
					$selectedfields = explode(":",$fieldcolname);
					if($selectedfields[0] == "vte_crmentity".$this->primarymodule)
						$selectedfields[0] = "vte_crmentity";
					$sSQLList[] = $selectedfields[0].".".$selectedfields[1]." '".$selectedfields[2]."'";
				}
			}
		}
		$sSQL .= implode(",",$sSQLList);

		$log->info("ReportRun :: Successfully returned getSelectedColumnsList".$relblockid);
		return $sSQL;
	}
  */
	/** Function to get advanced comparator in query form for the given Comparator and value
	 *  @ param $comparator : Type String
	 *  @ param $value : Type String
	 *  returns the check query for the comparator
	 */
	function getAdvComparator($comparator,$value,$datatype="")
	{

		global $log,$adb,$default_charset,$ogReport,$table_prefix;
		$value=html_entity_decode(trim($value),ENT_QUOTES,$default_charset);
		$value_len = strlen($value);
		$is_field = false;
		if($value[0]=='$' && $value[$value_len-1]=='$'){
			$temp = str_replace('$','',$value);
			$is_field = true;
		}
		if($datatype=='C'){
			$value = str_replace("yes","1",str_replace("no","0",$value));
		}

		if($is_field==true){
			$value = $this->getFilterComparedField($temp);
		}
		if($comparator == "e")
		{
			if(trim($value) == "NULL")
			{
				$rtvalue = " is NULL";
			}elseif(trim($value) != "")
			{
				$rtvalue = " = ".$adb->quote($value);
			}elseif(trim($value) == "" && $datatype == "V")
			{
				$rtvalue = " = ".$adb->quote($value);
			}else
			{
				$rtvalue = " is NULL";
			}
		}
		if($comparator == "n")
		{
			if(trim($value) == "NULL")
			{
				$rtvalue = " is NOT NULL";
			}elseif(trim($value) != "")
			{
				$rtvalue = " <> ".$adb->quote($value);
			}elseif(trim($value) == "" && $datatype == "V")
			{
				$rtvalue = " <> ".$adb->quote($value);
			}else
			{
				$rtvalue = " is NOT NULL";
			}
		}
		if($comparator == "s")
		{
			$rtvalue = " like '". formatForSqlLike($value, 2,$is_field) ."'";
		}
		if($comparator == "ew")
		{
			$rtvalue = " like '". formatForSqlLike($value, 1,$is_field) ."'";
		}
		if($comparator == "c")
		{
			$rtvalue = " like '". formatForSqlLike($value,0,$is_field) ."'";
		}
		if($comparator == "k")
		{
			$rtvalue = " not like '". formatForSqlLike($value,0,$is_field) ."'";
		}
		if($comparator == "l")
		{
			$rtvalue = " < ".$adb->quote($value);
		}
		if($comparator == "g")
		{
			$rtvalue = " > ".$adb->quote($value);
		}
		if($comparator == "m")
		{
			$rtvalue = " <= ".$adb->quote($value);
		}
		if($comparator == "h")
		{
			$rtvalue = " >= ".$adb->quote($value);
		}
		if($comparator == "b") {
			$rtvalue = " < ".$adb->quote($value);
		}
		if($comparator == "a") {
			$rtvalue = " > ".$adb->quote($value);
		}
		if($is_field==true){
			$rtvalue = str_replace("'","",$rtvalue);
			$rtvalue = str_replace("\\","",$rtvalue);
		}
		$log->info("ReportRun :: Successfully returned getAdvComparator");
		return $rtvalue;
	}

	/** Function to get field that is to be compared in query form for the given Comparator and field
	 *  @ param $field : field
	 *  returns the value for the comparator
	 */
	function getFilterComparedField($field){
		global $adb,$ogReport;
			$field = explode('#',$field);
			$module = $field[0];
			$fieldname = trim($field[1]);
			$tabid = getTabId($module);
			$field_query = $adb->pquery("SELECT tablename,columnname,typeofdata,fieldname,uitype FROM ".$table_prefix."_field WHERE tabid = ? AND fieldname= ?",array($tabid,$fieldname));
			$fieldtablename = $adb->query_result($field_query,0,'tablename');
			$fieldcolname = $adb->query_result($field_query,0,'columnname');
			$typeofdata = $adb->query_result($field_query,0,'typeofdata');
			$fieldtypeofdata=ChangeTypeOfData_Filter($fieldtablename,$fieldcolname,$typeofdata[0]);
			$uitype = $adb->query_result($field_query,0,'uitype');
			/*if($tr[0]==$ogReport->primodule)
				$value = $adb->query_result($field_query,0,'tablename').".".$adb->query_result($field_query,0,'columnname');
			else
				$value = $adb->query_result($field_query,0,'tablename').$tr[0].".".$adb->query_result($field_query,0,'columnname');
			*/
			if($fieldtablename == $table_prefix."_crmentity")
			{
				$fieldtablename = $fieldtablename.$module;
			}
			if($fieldname == "assigned_user_id")
			{
				$fieldtablename = $table_prefix."_users".$module;
				$fieldcolname = "user_name";
			}
			if($fieldname == "account_id")
			{
				$fieldtablename = $table_prefix."_account".$module;
				$fieldcolname = "accountname";
			}
			if($fieldname == "contact_id")
			{
				$fieldtablename = $table_prefix."_contactdetails".$module;
				$fieldcolname = "lastname";
			}
			if($fieldname == "parent_id")
			{
				$fieldtablename = $table_prefix."_crmentityRel".$module;
				$fieldcolname = "setype";
			}
			if($fieldname == "vendor_id")
			{
				$fieldtablename = $table_prefix."_vendorRel".$module;
				$fieldcolname = "vendorname";
			}
			if($fieldname == "potential_id")
			{
				$fieldtablename = $table_prefix."_potentialRel".$module;
				$fieldcolname = "potentialname";
			}
			if($fieldname == "assigned_user_id1")
			{
				$fieldtablename = $table_prefix."_usersRel1";
				$fieldcolname = "user_name";
			}
			if($fieldname == 'quote_id')
			{
				$fieldtablename = $table_prefix."_quotes".$module;
				$fieldcolname = "subject";
			}
			if($fieldname == 'product_id' && $fieldtablename == $table_prefix.'_troubletickets')
			{
				$fieldtablename = $table_prefix."_productsRel";
				$fieldcolname = "productname";
			}
			if($fieldname == 'product_id' && $fieldtablename == $table_prefix.'_campaign')
			{
				$fieldtablename = $table_prefix."_productsCampaigns";
				$fieldcolname = "productname";
			}
			if($fieldname == 'product_id' && $fieldtablename == $table_prefix.'_products')
			{
				$fieldtablename = $table_prefix."_productsProducts";
				$fieldcolname = "productname";
			}
			if($fieldname == 'campaignid' && $module=='Potentials')
			{
				$fieldtablename = $table_prefix."_campaign".$module;
				$fieldcolname = "campaignname";
			}
			$value = $fieldtablename.".".$fieldcolname;
		return $value;
	}
	/** Function to get the advanced filter columns for the relblockid
	 *  This function accepts the $relblockid
	 *  This function returns  $columnslist Array($columnname => $tablename:$columnname:$fieldlabel:$fieldname:$typeofdata=>$tablename.$columnname filtercriteria,
	 *					      $tablename1:$columnname1:$fieldlabel1:$fieldname1:$typeofdata1=>$tablename1.$columnname1 filtercriteria,
	 *					      					|
 	 *					      $tablenamen:$columnnamen:$fieldlabeln:$fieldnamen:$typeofdatan=>$tablenamen.$columnnamen filtercriteria
	 *				      	     )
	 *
	 */


	function getAdvFilterSql($relblockid)
	{
		// Have we initialized information already?
		if($this->_advfiltersql !== false) {
			return $this->_advfiltersql;
		}

		global $adb;
		global $modules;
		global $log;
		global $table_prefix;
		$advfiltersql = "";

		$advfiltergroupssql = "SELECT * FROM ".$table_prefix."_pdfmaker_relblckcri_g WHERE relblockid = ? ORDER BY groupid";
		$advfiltergroups = $adb->pquery($advfiltergroupssql, array($relblockid));
		$numgrouprows = $adb->num_rows($advfiltergroups);
		$groupctr =0;
		while($advfiltergroup = $adb->fetch_array($advfiltergroups)) {
			$groupctr++;
			$groupid = $advfiltergroup["groupid"];
			$groupcondition = $advfiltergroup["group_condition"];

			$advfiltercolumnssql =  "select ".$table_prefix."_pdfmaker_relblckcri.* from ".$table_prefix."_pdfmaker_relblocks";
			$advfiltercolumnssql .= " left join ".$table_prefix."_pdfmaker_relblckcri on ".$table_prefix."_pdfmaker_relblckcri.relblockid = ".$table_prefix."_pdfmaker_relblocks.relblockid";
			$advfiltercolumnssql .= " where ".$table_prefix."_pdfmaker_relblocks.relblockid = ? AND ".$table_prefix."_pdfmaker_relblckcri.groupid = ?";
			$advfiltercolumnssql .= " order by ".$table_prefix."_pdfmaker_relblckcri.colid";

			$result = $adb->pquery($advfiltercolumnssql, array($relblockid, $groupid));
			$noofrows = $adb->num_rows($result);

			if($noofrows > 0) {

				$advfiltergroupsql = "";
				$columnctr = 0;
				while($advfilterrow = $adb->fetch_array($result)) {
					$columnctr++;
					$fieldcolname = $advfilterrow["columnname"];
					$comparator = $advfilterrow["comparator"];
					$value = $advfilterrow["value"];
					$columncondition = $advfilterrow["column_condition"];

					if($fieldcolname != "" && $comparator != "") {
						$selectedfields = explode(":",$fieldcolname);
						//Added to handle yes or no for checkbox  field in reports advance filters. -shahul
						if($selectedfields[4] == 'C') {
							if(strcasecmp(trim($value),"yes")==0)
								$value="1";
							if(strcasecmp(trim($value),"no")==0)
								$value="0";
						}
						$valuearray = explode(",",trim($value));
						$datatype = (isset($selectedfields[4])) ? $selectedfields[4] : "";
						if(isset($valuearray) && count($valuearray) > 1 && $comparator != 'bw') {

							$advcolumnsql = "";
							for($n=0;$n<count($valuearray);$n++) {

		                		if($selectedfields[0] == $table_prefix.'_crmentityRelHelpDesk' && $selectedfields[1]=='setype') {
									$advcolsql[] = "(case ".$table_prefix."_crmentityRelHelpDesk.setype when 'Accounts' then ".$table_prefix."_accountRelHelpDesk.accountname else concat(".$table_prefix."_contactdetailsRelHelpDesk.lastname,' ',".$table_prefix."_contactdetailsRelHelpDesk.firstname) end) ". $this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype);
		                        } elseif($selectedfields[0] == $table_prefix.'_crmentityRelCalendar' && $selectedfields[1]=='setype') {
									$advcolsql[] = "(case ".$table_prefix."_crmentityRelHelpDesk.setype when 'Accounts' then ".$table_prefix."_accountRelHelpDesk.accountname else concat(".$table_prefix."_contactdetailsRelHelpDesk.lastname,' ',".$table_prefix."_contactdetailsRelHelpDesk.firstname) end) ". $this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype);
		                        } elseif(($selectedfields[0] == $table_prefix."_users".$this->primarymodule || $selectedfields[0] == $table_prefix."_users".$this->secondarymodule) && $selectedfields[1] == 'user_name') {
									$module_from_tablename = str_replace($table_prefix."_users","",$selectedfields[0]);
									if($this->primarymodule == 'Products') {
										$advcolsql[] = ($selectedfields[0].".user_name ".$this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype));
									} else {
										$advcolsql[] = " ".$selectedfields[0].".user_name".$this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype)." or ".$table_prefix."_groups".$module_from_tablename.".groupname ".$this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype);
									}
								} elseif($selectedfields[1] == 'status') {//when you use comma seperated values.
									if($selectedfields[2] == 'Calendar_Status')
									$advcolsql[] = "(case when (".$table_prefix."_activity.status not like '') then ".$table_prefix."_activity.status else ".$table_prefix."_activity.eventstatus end)".$this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype);
									elseif($selectedfields[2] == 'HelpDesk_Status')
									$advcolsql[] = $table_prefix."_troubletickets.status".$this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype);
								} elseif($selectedfields[1] == 'description') {//when you use comma seperated values.
									if($selectedfields[0]==$table_prefix.'_crmentity'.$this->primarymodule)
										$advcolsql[] = $table_prefix."_crmentity.description".$this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype);
									else
										$advcolsql[] = $selectedfields[0].".".$selectedfields[1].$this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype);
								} else {
									$advcolsql[] = $selectedfields[0].".".$selectedfields[1].$this->getAdvComparator($comparator,trim($valuearray[$n]),$datatype);
								}
							}
							//If negative logic filter ('not equal to', 'does not contain') is used, 'and' condition should be applied instead of 'or'
							if($comparator == 'n' || $comparator == 'k')
								$advcolumnsql = implode(" and ",$advcolsql);
							else
								$advcolumnsql = implode(" or ",$advcolsql);
							$fieldvalue = " (".$advcolumnsql.") ";
						} elseif(($selectedfields[0] == $table_prefix."_users".$this->primarymodule || $selectedfields[0] == $table_prefix."_users".$this->secondarymodule) && $selectedfields[1] == 'user_name') {
							$module_from_tablename = str_replace($table_prefix."_users","",$selectedfields[0]);
							if($this->primarymodule == 'Products') {
								$fieldvalue = ($selectedfields[0].".user_name ".$this->getAdvComparator($comparator,trim($value),$datatype));
							} else {
								$fieldvalue = " case when (".$selectedfields[0].".user_name not like '') then ".$selectedfields[0].".user_name else ".$table_prefix."_groups".$module_from_tablename.".groupname end ".$this->getAdvComparator($comparator,trim($value),$datatype);
							}
						} elseif($selectedfields[0] == $table_prefix."_crmentity".$this->primarymodule) {
							$fieldvalue = $table_prefix."_crmentity.".$selectedfields[1]." ".$this->getAdvComparator($comparator,trim($value),$datatype);
						} elseif($selectedfields[0] == $table_prefix.'_crmentityRelHelpDesk' && $selectedfields[1]=='setype') {
							$fieldvalue = "(".$table_prefix."_accountRelHelpDesk.accountname ".$this->getAdvComparator($comparator,trim($value),$datatype)." or ".$table_prefix."_contactdetailsRelHelpDesk.lastname ".$this->getAdvComparator($comparator,trim($value),$datatype)." or ".$table_prefix."_contactdetailsRelHelpDesk.firstname ".$this->getAdvComparator($comparator,trim($value),$datatype).")";
						} elseif($selectedfields[0] == $table_prefix.'_crmentityRelCalendar' && $selectedfields[1]=='setype') {
							$fieldvalue = "(".$table_prefix."_accountRelCalendar.accountname ".$this->getAdvComparator($comparator,trim($value),$datatype)." or concat(".$table_prefix."_leaddetailsRelCalendar.lastname,' ',".$table_prefix."_leaddetailsRelCalendar.firstname) ".$this->getAdvComparator($comparator,trim($value),$datatype)." or ".$table_prefix."_potentialRelCalendar.potentialname ".$this->getAdvComparator($comparator,trim($value),$datatype)." or ".$table_prefix."_invoiceRelCalendar.subject ".$this->getAdvComparator($comparator,trim($value),$datatype)." or ".$table_prefix."_quotesRelCalendar.subject ".$this->getAdvComparator($comparator,trim($value),$datatype)." or ".$table_prefix."_purchaseorderRelCalendar.subject ".$this->getAdvComparator($comparator,trim($value),$datatype)." or ".$table_prefix."_salesorderRelCalendar.subject ".$this->getAdvComparator($comparator,trim($value),$datatype)." or ".$table_prefix."_troubleticketsRelCalendar.title ".$this->getAdvComparator($comparator,trim($value),$datatype)." or ".$table_prefix."_campaignRelCalendar.campaignname ".$this->getAdvComparator($comparator,trim($value),$datatype).")";
						} elseif($selectedfields[0] == $table_prefix."_activity" && $selectedfields[1] == 'status') {
							$fieldvalue = "(case when (".$table_prefix."_activity.status not like '') then ".$table_prefix."_activity.status else ".$table_prefix."_activity.eventstatus end)".$this->getAdvComparator($comparator,trim($value),$datatype);
						} elseif($selectedfields[3] == "contact_id" && strpos($selectedfields[2],"Contact_Name")) {
							if($this->primarymodule == 'PurchaseOrder' || $this->primarymodule == 'SalesOrder' || $this->primarymodule == 'Quotes' || $this->primarymodule == 'Invoice' || $this->primarymodule == 'Calendar')
								$fieldvalue = "concat(".$table_prefix."_contactdetails". $this->primarymodule .".lastname,' ',".$table_prefix."_contactdetails". $this->primarymodule .".firstname)".$this->getAdvComparator($comparator,trim($value),$datatype);
							if($this->secondarymodule == 'Quotes' || $this->secondarymodule == 'Invoice')
								$fieldvalue = "concat(".$table_prefix."_contactdetails". $this->secondarymodule .".lastname,' ',".$table_prefix."_contactdetails". $this->secondarymodule .".firstname)".$this->getAdvComparator($comparator,trim($value),$datatype);
						} elseif($comparator == 'e' && (trim($value) == "NULL" || trim($value) == '')) {
							$fieldvalue = "(".$selectedfields[0].".".$selectedfields[1]." IS NULL OR ".$selectedfields[0].".".$selectedfields[1]." = '')";
						} elseif($comparator == 'bw' && count($valuearray) == 2) {
							$fieldvalue = "(".$selectedfields[0].".".$selectedfields[1]." between '".trim($valuearray[0])."' and '".trim($valuearray[1])."')";
						} else {
							$fieldvalue = $selectedfields[0].".".$selectedfields[1].$this->getAdvComparator($comparator,trim($value),$datatype);
						}

						$advfiltergroupsql .= $fieldvalue;
						if($columncondition != NULL && $columncondition != '' && $noofrows > $columnctr ) {
							$advfiltergroupsql .= ' '.$columncondition.' ';
						}
					}

				}

				if (trim($advfiltergroupsql) != "") {
					$advfiltergroupsql =  "( $advfiltergroupsql ) ";
					if($groupcondition != NULL && $groupcondition != '' && $numgrouprows > $groupctr) {
						$advfiltergroupsql .= ' '. $groupcondition . ' ';
					}

					$advfiltersql .= $advfiltergroupsql;
				}
			}
		}
		if (trim($advfiltersql) != "") $advfiltersql = '('.$advfiltersql.')';
		// Save the information
		$this->_advfiltersql = $advfiltersql;

		$log->info("ReportRun :: Successfully returned getAdvFilterSql".$relblockid);
		return $advfiltersql;
	}

	/** Function to get the Standard filter columns for the relblockid
	 *  This function accepts the $relblockid datatype Integer
	 *  This function returns  $stdfilterlist Array($columnname => $tablename:$columnname:$fieldlabel:$fieldname:$typeofdata=>$tablename.$columnname filtercriteria,
	 *					      $tablename1:$columnname1:$fieldlabel1:$fieldname1:$typeofdata1=>$tablename1.$columnname1 filtercriteria,
	 *				      	     )
	 *
	 */
	function getStdFilterList($relblockid)
	{
		// Have we initialized information already?
		if($this->_stdfilterlist !== false) {
			return $this->_stdfilterlist;
		}

		global $adb;
		global $modules;
		global $log;
		global $table_prefix;

		$stdfiltersql = "select ".$table_prefix."_pdfmaker_relblckdfilt.* from ".$table_prefix."_pdfmaker_relblocks";
		$stdfiltersql .= " inner join ".$table_prefix."_pdfmaker_relblckdfilt on ".$table_prefix."_pdfmaker_relblocks.relblockid = ".$table_prefix."_pdfmaker_relblckdfilt.datefilterid";
		$stdfiltersql .= " where ".$table_prefix."_pdfmaker_relblocks.relblockid = ?";

		$result = $adb->pquery($stdfiltersql, array($relblockid));
		$stdfilterrow = $adb->fetch_array($result);
		if(isset($stdfilterrow))
		{
			$fieldcolname = $stdfilterrow["datecolumnname"];
			$datefilter = $stdfilterrow["datefilter"];
			$startdate = $stdfilterrow["startdate"];
			$enddate = $stdfilterrow["enddate"];

			if($fieldcolname != "none")
			{
				$selectedfields = explode(":",$fieldcolname);
				if($selectedfields[0] == $table_prefix."_crmentity".$this->primarymodule)
					$selectedfields[0] = $table_prefix."_crmentity";
				if($datefilter == "custom")
				{
					if($startdate != "0000-00-00" && $enddate != "0000-00-00" && $startdate != "1900-01-01 00:00:00" && $enddate != "1900-01-01 00:00:00" && $selectedfields[0] != "" && $selectedfields[1] != "") // crmv@77567
					{
						$stdfilterlist[$fieldcolname] = $selectedfields[0].".".$selectedfields[1]." between '".$startdate." 00:00:00' and '".$enddate." 23:59:59'";
					}
				}else
				{
					$startenddate = $this->getStandarFiltersStartAndEndDate($datefilter);
					if($startenddate[0] != "" && $startenddate[1] != "" && $selectedfields[0] != "" && $selectedfields[1] != "")
					{
						$stdfilterlist[$fieldcolname] = $selectedfields[0].".".$selectedfields[1]." between '".$startenddate[0]." 00:00:00' and '".$startenddate[1]." 23:59:59'";
					}
				}

			}
		}
		// Save the information
		$this->_stdfilterlist = $stdfilterlist;

		$log->info("ReportRun :: Successfully returned getStdFilterList".$relblockid);
		return $stdfilterlist;
	}

	/** Function to get the RunTime filter columns for the given $filtercolumn,$filter,$startdate,$enddate
	 *  @ param $filtercolumn : Type String
	 *  @ param $filter : Type String
	 *  @ param $startdate: Type String
	 *  @ param $enddate : Type String
	 *  This function returns  $stdfilterlist Array($columnname => $tablename:$columnname:$fieldlabel=>$tablename.$columnname 'between' $startdate 'and' $enddate)
	 *
	 */
	 /*
	function RunTimeFilter($filtercolumn,$filter,$startdate,$enddate)
	{
		if($filtercolumn != "none")
		{
			$selectedfields = explode(":",$filtercolumn);
			if($selectedfields[0] == "vte_crmentity".$this->primarymodule)
				$selectedfields[0] = "vte_crmentity";
			if($filter == "custom")
			{
				if($startdate != "" && $enddate != "" && $selectedfields[0] != "" && $selectedfields[1] != "")
				{
					$stdfilterlist[$filtercolumn] = $selectedfields[0].".".$selectedfields[1]." between '".$startdate." 00:00:00' and '".$enddate." 23:59:00'";
				}
			}else
			{
				if($startdate != "" && $enddate != "")
				{
					$startenddate = $this->getStandarFiltersStartAndEndDate($filter);
					if($startenddate[0] != "" && $startenddate[1] != "" && $selectedfields[0] != "" && $selectedfields[1] != "")
					{
						$stdfilterlist[$filtercolumn] = $selectedfields[0].".".$selectedfields[1]." between '".$startenddate[0]." 00:00:00' and '".$startenddate[1]." 23:59:00'";
					}
				}
			}

		}
		return $stdfilterlist;

	}
*/
	/** Function to get standardfilter for the given relblockid
	 *  @ param $relblockid : Type Integer
	 *  returns the query of columnlist for the selected columns
	 */

	function getStandardCriterialSql($relblockid)
	{
		global $adb;
		global $modules;
		global $log;
		global $table_prefix;

		$sreportstdfiltersql = "select ".$table_prefix."_pdfmaker_relblckdfilt.* from ".$table_prefix."_pdfmaker_relblocks";
		$sreportstdfiltersql .= " inner join ".$table_prefix."_pdfmaker_relblckdfilt on ".$table_prefix."_pdfmaker_relblocks.relblockid = ".$table_prefix."_pdfmaker_relblckdfilt.datefilterid";
		$sreportstdfiltersql .= " where ".$table_prefix."_pdfmaker_relblocks.relblockid = ?";

		$result = $adb->pquery($sreportstdfiltersql, array($relblockid));
		$noofrows = $adb->num_rows($result);

		for($i=0; $i<$noofrows; $i++)
		{
			$fieldcolname = $adb->query_result($result,$i,"datecolumnname");
			$datefilter = $adb->query_result($result,$i,"datefilter");
			$startdate = $adb->query_result($result,$i,"startdate");
			$enddate = $adb->query_result($result,$i,"enddate");

			if($fieldcolname != "none")
			{
				$selectedfields = explode(":",$fieldcolname);
				if($selectedfields[0] == $table_prefix."_crmentity".$this->primarymodule)
					$selectedfields[0] = $table_prefix."_crmentity";
				if($datefilter == "custom")
				{
					if($startdate != "0000-00-00" && $enddate != "0000-00-00" && $selectedfields[0] != "" && $selectedfields[1] != "")
					{
						$sSQL .= $selectedfields[0].".".$selectedfields[1]." between '".$startdate."' and '".$enddate."'";
					}
				}else
				{
					$startenddate = $this->getStandarFiltersStartAndEndDate($datefilter);
					if($startenddate[0] != "" && $startenddate[1] != "" && $selectedfields[0] != "" && $selectedfields[1] != "")
					{
						$sSQL .= $selectedfields[0].".".$selectedfields[1]." between '".$startenddate[0]."' and '".$startenddate[1]."'";
					}
				}
			}
		}
		$log->info("ReportRun :: Successfully returned getStandardCriterialSql".$relblockid);
		return $sSQL;
	}

	/** Function to get standardfilter startdate and enddate for the given type
	 *  @ param $type : Type String
	 *  returns the $datevalue Array in the given format
	 * 		$datevalue = Array(0=>$startdate,1=>$enddate)
	 */


	function getStandarFiltersStartAndEndDate($type)
	{
		$today = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d"), date("Y")));
		$tomorrow  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		$yesterday  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));

		$currentmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m"), "01",   date("Y")));
		$currentmonth1 = date("Y-m-t");
		$lastmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")-1, "01",   date("Y")));
		$lastmonth1 = date("Y-m-t", strtotime("-1 Month"));
		$nextmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")+1, "01",   date("Y")));
		$nextmonth1 = date("Y-m-t", strtotime("+1 Month"));

		$lastweek0 = date("Y-m-d",strtotime("-2 week Sunday"));
		$lastweek1 = date("Y-m-d",strtotime("-1 week Saturday"));

		$thisweek0 = date("Y-m-d",strtotime("-1 week Sunday"));
		$thisweek1 = date("Y-m-d",strtotime("this Saturday"));

		$nextweek0 = date("Y-m-d",strtotime("this Sunday"));
		$nextweek1 = date("Y-m-d",strtotime("+1 week Saturday"));

		$next7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+6, date("Y")));
		$next30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+29, date("Y")));
		$next60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+59, date("Y")));
		$next90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+89, date("Y")));
		$next120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+119, date("Y")));

		$last7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-6, date("Y")));
		$last30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-29, date("Y")));
		$last60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-59, date("Y")));
		$last90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-89, date("Y")));
		$last120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-119, date("Y")));

		$currentFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")));
		$currentFY1 = date("Y-m-t",mktime(0, 0, 0, "12", date("d"),   date("Y")));
		$lastFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")-1));
		$lastFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")-1));
		$nextFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")+1));
		$nextFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")+1));

		if(date("m") <= 3)
		{
			$cFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")-1));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")-1));
		}else if(date("m") > 3 and date("m") <= 6)
		{
			$pFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));

		}else if(date("m") > 6 and date("m") <= 9)
		{
			$nFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
		}
		else if(date("m") > 9 and date("m") <= 12)
		{
			$nFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")+1));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")+1));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));

		}

		if($type == "today" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $today;
		}
		elseif($type == "yesterday" )
		{

			$datevalue[0] = $yesterday;
			$datevalue[1] = $yesterday;
		}
		elseif($type == "tomorrow" )
		{

			$datevalue[0] = $tomorrow;
			$datevalue[1] = $tomorrow;
		}
		elseif($type == "thisweek" )
		{

			$datevalue[0] = $thisweek0;
			$datevalue[1] = $thisweek1;
		}
		elseif($type == "lastweek" )
		{

			$datevalue[0] = $lastweek0;
			$datevalue[1] = $lastweek1;
		}
		elseif($type == "nextweek" )
		{

			$datevalue[0] = $nextweek0;
			$datevalue[1] = $nextweek1;
		}
		elseif($type == "thismonth" )
		{

			$datevalue[0] =$currentmonth0;
			$datevalue[1] = $currentmonth1;
		}

		elseif($type == "lastmonth" )
		{

			$datevalue[0] = $lastmonth0;
			$datevalue[1] = $lastmonth1;
		}
		elseif($type == "nextmonth" )
		{

			$datevalue[0] = $nextmonth0;
			$datevalue[1] = $nextmonth1;
		}
		elseif($type == "next7days" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $next7days;
		}
		elseif($type == "next30days" )
		{

			$datevalue[0] =$today;
			$datevalue[1] =$next30days;
		}
		elseif($type == "next60days" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $next60days;
		}
		elseif($type == "next90days" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $next90days;
		}
		elseif($type == "next120days" )
		{

			$datevalue[0] = $today;
			$datevalue[1] = $next120days;
		}
		elseif($type == "last7days" )
		{

			$datevalue[0] = $last7days;
			$datevalue[1] = $today;
		}
		elseif($type == "last30days" )
		{

			$datevalue[0] = $last30days;
			$datevalue[1] =  $today;
		}
		elseif($type == "last60days" )
		{

			$datevalue[0] = $last60days;
			$datevalue[1] = $today;
		}
		else if($type == "last90days" )
		{

			$datevalue[0] = $last90days;
			$datevalue[1] = $today;
		}
		elseif($type == "last120days" )
		{

			$datevalue[0] = $last120days;
			$datevalue[1] = $today;
		}
		elseif($type == "thisfy" )
		{

			$datevalue[0] = $currentFY0;
			$datevalue[1] = $currentFY1;
		}
		elseif($type == "prevfy" )
		{

			$datevalue[0] = $lastFY0;
			$datevalue[1] = $lastFY1;
		}
		elseif($type == "nextfy" )
		{

			$datevalue[0] = $nextFY0;
			$datevalue[1] = $nextFY1;
		}
		elseif($type == "nextfq" )
		{

			$datevalue[0] = $nFq;
			$datevalue[1] = $nFq1;
		}
		elseif($type == "prevfq" )
		{

			$datevalue[0] = $pFq;
			$datevalue[1] = $pFq1;
		}
		elseif($type == "thisfq" )
		{
			$datevalue[0] = $cFq;
			$datevalue[1] = $cFq1;
		}
		else
		{
			$datevalue[0] = "";
			$datevalue[1] = "";
		}
		return $datevalue;
	}

	/** Function to get getGroupingList for the given relblockid
	 *  @ param $relblockid : Type Integer
	 *  returns the $grouplist Array in the following format
	 *  		$grouplist = Array($tablename:$columnname:$fieldlabel:fieldname:typeofdata=>$tablename:$columnname $sorder,
	 *				   $tablename1:$columnname1:$fieldlabel1:fieldname1:typeofdata1=>$tablename1:$columnname1 $sorder,
	 *				   $tablename2:$columnname2:$fieldlabel2:fieldname2:typeofdata2=>$tablename2:$columnname2 $sorder)
	 * This function also sets the return value in the class variable $this->groupbylist
	 */

/*
	function getGroupingList($relblockid)
	{
		global $adb;
		global $modules;
		global $log;

		// Have we initialized information already?
		if($this->_groupinglist !== false) {
			return $this->_groupinglist;
		}

		$sreportsortsql = "select vte_reportsortcol.* from vte_report";
		$sreportsortsql .= " inner join vte_reportsortcol on vte_report.relblockid = vte_reportsortcol.relblockid";
		$sreportsortsql .= " where vte_report.relblockid =? AND vte_reportsortcol.columnname IN (SELECT columnname from vte_selectcolumn WHERE queryid=?) order by vte_reportsortcol.sortcolid";

		$result = $adb->pquery($sreportsortsql, array($relblockid,$relblockid));

		while($reportsortrow = $adb->fetch_array($result))
		{
			$fieldcolname = $reportsortrow["columnname"];
			list($tablename,$colname,$module_field,$fieldname,$single) = explode(":",$fieldcolname);
			$sortorder = $reportsortrow["sortorder"];

			if($sortorder == "Ascending")
			{
				$sortorder = "ASC";

			}elseif($sortorder == "Descending")
			{
				$sortorder = "DESC";
			}

			if($fieldcolname != "none")
			{
				$selectedfields = explode(":",$fieldcolname);
				if($selectedfields[0] == "vte_crmentity".$this->primarymodule)
					$selectedfields[0] = "vte_crmentity";
				if(stripos($selectedfields[1],'cf_')==0 && stristr($selectedfields[1],'cf_')==true){
					$sqlvalue = "".$adb->sql_escape_string(decode_html($selectedfields[2]))." ".$sortorder;
				} else {
					$sqlvalue = "".self::replaceSpecialChar($selectedfields[2])." ".$sortorder;
				}
				$grouplist[$fieldcolname] = $sqlvalue;
				$temp = explode("_",$selectedfields[2],2);
				$module = $temp[0];
				if(CheckFieldPermission($fieldname,$module) == 'true')
				{
					$this->groupbylist[$fieldcolname] = $selectedfields[0].".".$selectedfields[1]." ".$selectedfields[2];
				}
			}
		}

		if(in_array($this->primarymodule, array('Invoice', 'Quotes', 'SalesOrder', 'PurchaseOrder')) ) {
			$instance = CRMEntity::getInstance($this->primarymodule);
			$grouplist[$instance->table_index] = $instance->table_name.'.'.$instance->table_index;
			$grouplist['subject'] = $instance->table_name.'.subject';
			$this->groupbylist[$fieldcolname] = $instance->table_name.'.'.$instance->table_index;
			$this->groupbylist['subject'] = $instance->table_name.'.subject';
		}

		// Save the information
		$this->_groupinglist = $grouplist;

		$log->info("ReportRun :: Successfully returned getGroupingList".$relblockid);
		return $grouplist;
	}
  */
	/** function to replace special characters
	 *  @ param $selectedfield : type string
	 *  this returns the string for grouplist
	 */

	function replaceSpecialChar($selectedfield){
		$selectedfield = decode_html(decode_html($selectedfield));
		preg_match('/&/', $selectedfield, $matches);
		if(!empty($matches)){
			$selectedfield = str_replace('&', 'and',($selectedfield));
		}
		return $selectedfield;
		}

	/** function to get the selectedorderbylist for the given relblockid
	 *  @ param $relblockid : type integer
	 *  this returns the columns query for the sortorder columns
	 *  this function also sets the return value in the class variable $this->orderbylistsql
	 */

/*
	function getSelectedOrderbyList($relblockid)
	{

		global $adb;
		global $modules;
		global $log;

		$sreportsortsql = "select vte_reportsortcol.* from vte_report";
		$sreportsortsql .= " inner join vte_reportsortcol on vte_report.relblockid = vte_reportsortcol.relblockid";
		$sreportsortsql .= " where vte_report.relblockid =? order by vte_reportsortcol.sortcolid";

		$result = $adb->pquery($sreportsortsql, array($relblockid));
		$noofrows = $adb->num_rows($result);

		for($i=0; $i<$noofrows; $i++)
		{
			$fieldcolname = $adb->query_result($result,$i,"columnname");
			$sortorder = $adb->query_result($result,$i,"sortorder");

			if($sortorder == "Ascending")
			{
				$sortorder = "ASC";
			}
			elseif($sortorder == "Descending")
			{
				$sortorder = "DESC";
			}

			if($fieldcolname != "none")
			{
				$this->orderbylistcolumns[] = $fieldcolname;
				$n = $n + 1;
				$selectedfields = explode(":",$fieldcolname);
				if($n > 1)
				{
					$sSQL .= ", ";
					$this->orderbylistsql .= ", ";
				}
				if($selectedfields[0] == "vte_crmentity".$this->primarymodule)
					$selectedfields[0] = "vte_crmentity";
				$sSQL .= $selectedfields[0].".".$selectedfields[1]." ".$sortorder;
				$this->orderbylistsql .= $selectedfields[0].".".$selectedfields[1]." ".$selectedfields[2];
			}
		}
		$log->info("ReportRun :: Successfully returned getSelectedOrderbyList".$relblockid);
		return $sSQL;
	}
  */
	/** function to get secondary Module for the given Primary module and secondary module
	 *  @ param $module : type String
	 *  @ param $secmodule : type String
	 *  this returns join query for the given secondary module
	 */

	function getRelatedModulesQuery($module,$secmodule)
	{
		global $log,$current_user;
		$query = '';
		if($secmodule!=''){
			$secondarymodule = explode(":",$secmodule);
			foreach($secondarymodule as $key=>$value) {
					$foc = CRMEntity::getInstance($value);
					$query .= $foc->generateReportsSecQuery($module,$value,'',$this->hasInventoryColumns);	//crmv@73172
					$query .= getNonAdminAccessControlQuery($value,$current_user,$value);
			}
		}
		$log->info("ReportRun :: Successfully returned getRelatedModulesQuery".$secmodule);
		return $query;
	}
	/** function to get report query for the given module
	 *  @ param $module : type String
	 *  this returns join query for the given module
	 */

	function getReportsQuery($module)
	{
		global $log, $current_user,$table_prefix;
		$secondary_module ="'";
		$secondary_module .= str_replace(":","','",$this->secondarymodule);
		$secondary_module .="'";

		if($module == "Leads")
		{
			$query = "from ".$table_prefix."_leaddetails
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_leaddetails.leadid
				inner join ".$table_prefix."_leadsubdetails on ".$table_prefix."_leadsubdetails.leadsubscriptionid=".$table_prefix."_leaddetails.leadid
				inner join ".$table_prefix."_leadaddress on ".$table_prefix."_leadaddress.leadaddressid=".$table_prefix."_leadsubdetails.leadsubscriptionid
				inner join ".$table_prefix."_leadscf on ".$table_prefix."_leaddetails.leadid = ".$table_prefix."_leadscf.leadid
				left join ".$table_prefix."_groups as ".$table_prefix."_groupsLeads on ".$table_prefix."_groupsLeads.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users as ".$table_prefix."_usersLeads on ".$table_prefix."_usersLeads.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_leaddetails.converted=0";
		}
		else if($module == "Accounts")
		{
			$query = "from ".$table_prefix."_account
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_account.accountid
				inner join ".$table_prefix."_accountbillads on ".$table_prefix."_account.accountid=".$table_prefix."_accountbillads.accountaddressid
				inner join ".$table_prefix."_accountshipads on ".$table_prefix."_account.accountid=".$table_prefix."_accountshipads.accountaddressid
				inner join ".$table_prefix."_accountscf on ".$table_prefix."_account.accountid = ".$table_prefix."_accountscf.accountid
				left join ".$table_prefix."_groups as ".$table_prefix."_groupsAccounts on ".$table_prefix."_groupsAccounts.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_account as ".$table_prefix."_accountAccounts on ".$table_prefix."_accountAccounts.accountid = ".$table_prefix."_account.parentid
				left join ".$table_prefix."_users as ".$table_prefix."_usersAccounts on ".$table_prefix."_usersAccounts.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0 ";
		}

		else if($module == "Contacts")
		{
			$query = "from ".$table_prefix."_contactdetails
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_contactdetails.contactid
				inner join ".$table_prefix."_contactaddress on ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactaddress.contactaddressid
				inner join ".$table_prefix."_customerdetails on ".$table_prefix."_customerdetails.customerid = ".$table_prefix."_contactdetails.contactid
				inner join ".$table_prefix."_contactsubdetails on ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactsubdetails.contactsubscriptionid
				inner join ".$table_prefix."_contactscf on ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactscf.contactid
				left join ".$table_prefix."_groups ".$table_prefix."_groupsContacts on ".$table_prefix."_groupsContacts.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_contactdetails as ".$table_prefix."_contactdetailsContacts on ".$table_prefix."_contactdetailsContacts.contactid = ".$table_prefix."_contactdetails.reportsto
				left join ".$table_prefix."_account as ".$table_prefix."_accountContacts on ".$table_prefix."_accountContacts.accountid = ".$table_prefix."_contactdetails.accountid
				left join ".$table_prefix."_users as ".$table_prefix."_usersContacts on ".$table_prefix."_usersContacts.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0";
		}

		else if($module == "Potentials")
		{
			$query = "from ".$table_prefix."_potential
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_potential.potentialid
				inner join ".$table_prefix."_potentialscf on ".$table_prefix."_potentialscf.potentialid = ".$table_prefix."_potential.potentialid
				left join ".$table_prefix."_account as ".$table_prefix."_accountPotentials on ".$table_prefix."_potential.related_to = ".$table_prefix."_accountPotentials.accountid
				left join ".$table_prefix."_contactdetails as ".$table_prefix."_contactdetailsPotentials on ".$table_prefix."_potential.related_to = ".$table_prefix."_contactdetailsPotentials.contactid
				left join ".$table_prefix."_campaign as ".$table_prefix."_campaignPotentials on ".$table_prefix."_potential.campaignid = ".$table_prefix."_campaignPotentials.campaignid
				left join ".$table_prefix."_groups ".$table_prefix."_groupsPotentials on ".$table_prefix."_groupsPotentials.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users as ".$table_prefix."_usersPotentials on ".$table_prefix."_usersPotentials.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0 ";
		}

		//For this Product - we can related Accounts, Contacts (Also Leads, Potentials)
		else if($module == "Products")
		{
			$query = "from ".$table_prefix."_products
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_products.productid
				left join ".$table_prefix."_productcf on ".$table_prefix."_products.productid = ".$table_prefix."_productcf.productid
				left join ".$table_prefix."_users as ".$table_prefix."_usersProducts on ".$table_prefix."_usersProducts.id = ".$table_prefix."_products.handler
				left join ".$table_prefix."_vendor as ".$table_prefix."_vendorRelProducts on ".$table_prefix."_vendorRelProducts.vendorid = ".$table_prefix."_products.vendor_id
				LEFT JOIN (
						SELECT ".$table_prefix."_products.productid,
								(CASE WHEN (".$table_prefix."_products.currency_id = 1 ) THEN ".$table_prefix."_products.unit_price
									ELSE (".$table_prefix."_products.unit_price / ".$table_prefix."_currency_info.conversion_rate) END
								) AS actual_unit_price
						FROM ".$table_prefix."_products
						LEFT JOIN ".$table_prefix."_currency_info ON ".$table_prefix."_products.currency_id = ".$table_prefix."_currency_info.id
						LEFT JOIN ".$table_prefix."_productcurrencyrel ON ".$table_prefix."_products.productid = ".$table_prefix."_productcurrencyrel.productid
						AND ".$table_prefix."_productcurrencyrel.currencyid = ". $current_user->currency_id . "
				) AS innerProduct ON innerProduct.productid = ".$table_prefix."_products.productid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0";
		}

		else if($module == "HelpDesk")
		{
			$query = "from ".$table_prefix."_troubletickets
				inner join ".$table_prefix."_crmentity
				on ".$table_prefix."_crmentity.crmid=".$table_prefix."_troubletickets.ticketid
				inner join ".$table_prefix."_ticketcf on ".$table_prefix."_ticketcf.ticketid = ".$table_prefix."_troubletickets.ticketid
				left join ".$table_prefix."_crmentity as ".$table_prefix."_crmentityRelHelpDesk on ".$table_prefix."_crmentityRelHelpDesk.crmid = ".$table_prefix."_troubletickets.parent_id
				left join ".$table_prefix."_account as ".$table_prefix."_accountRelHelpDesk on ".$table_prefix."_accountRelHelpDesk.accountid=".$table_prefix."_crmentityRelHelpDesk.crmid
				left join ".$table_prefix."_contactdetails as ".$table_prefix."_contactdetailsRelHelpDesk on ".$table_prefix."_contactdetailsRelHelpDesk.contactid= ".$table_prefix."_crmentityRelHelpDesk.crmid
				left join ".$table_prefix."_products as ".$table_prefix."_productsRel on ".$table_prefix."_productsRel.productid = ".$table_prefix."_troubletickets.product_id
				left join ".$table_prefix."_groups as ".$table_prefix."_groupsHelpDesk on ".$table_prefix."_groupsHelpDesk.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users as ".$table_prefix."_usersHelpDesk on ".$table_prefix."_crmentity.smownerid=".$table_prefix."_usersHelpDesk.id
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_crmentity.smownerid=".$table_prefix."_users.id
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0 ";
		}

		else if($module == "Calendar")
		{
			$query = "from ".$table_prefix."_activity
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_activity.activityid
				left join ".$table_prefix."_activitycf on ".$table_prefix."_activitycf.activityid = ".$table_prefix."_crmentity.crmid
				left join ".$table_prefix."_cntactivityrel on ".$table_prefix."_cntactivityrel.activityid= ".$table_prefix."_activity.activityid
				left join ".$table_prefix."_contactdetails as ".$table_prefix."_contactdetailsCalendar on ".$table_prefix."_contactdetailsCalendar.contactid= ".$table_prefix."_cntactivityrel.contactid
				left join ".$table_prefix."_groups as ".$table_prefix."_groupsCalendar on ".$table_prefix."_groupsCalendar.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users as ".$table_prefix."_usersCalendar on ".$table_prefix."_usersCalendar.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_seactivityrel on ".$table_prefix."_seactivityrel.activityid = ".$table_prefix."_activity.activityid
				left join ".$table_prefix."_activity_reminder on ".$table_prefix."_activity_reminder.activity_id = ".$table_prefix."_activity.activityid
				left join ".$table_prefix."_recurringevents on ".$table_prefix."_recurringevents.activityid = ".$table_prefix."_activity.activityid
				left join ".$table_prefix."_crmentity as ".$table_prefix."_crmentityRelCalendar on ".$table_prefix."_crmentityRelCalendar.crmid = ".$table_prefix."_seactivityrel.crmid
				left join ".$table_prefix."_account as ".$table_prefix."_accountRelCalendar on ".$table_prefix."_accountRelCalendar.accountid=".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_leaddetails as ".$table_prefix."_leaddetailsRelCalendar on ".$table_prefix."_leaddetailsRelCalendar.leadid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_potential as ".$table_prefix."_potentialRelCalendar on ".$table_prefix."_potentialRelCalendar.potentialid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_quotes as ".$table_prefix."_quotesRelCalendar on ".$table_prefix."_quotesRelCalendar.quoteid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_purchaseorder as ".$table_prefix."_purchaseorderRelCalendar on ".$table_prefix."_purchaseorderRelCalendar.purchaseorderid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_invoice as ".$table_prefix."_invoiceRelCalendar on ".$table_prefix."_invoiceRelCalendar.invoiceid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_salesorder as ".$table_prefix."_salesorderRelCalendar on ".$table_prefix."_salesorderRelCalendar.salesorderid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_troubletickets as ".$table_prefix."_troubleticketsRelCalendar on ".$table_prefix."_troubleticketsRelCalendar.ticketid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_campaign as ".$table_prefix."_campaignRelCalendar on ".$table_prefix."_campaignRelCalendar.campaignid = ".$table_prefix."_crmentityRelCalendar.crmid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				WHERE ".$table_prefix."_crmentity.deleted=0 and (".$table_prefix."_activity.activitytype != 'Emails')";
		}

		else if($module == "Quotes")
		{
			$query = "from ".$table_prefix."_quotes
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_quotes.quoteid
				inner join ".$table_prefix."_quotesbillads on ".$table_prefix."_quotes.quoteid=".$table_prefix."_quotesbillads.quotebilladdressid
				inner join ".$table_prefix."_quotesshipads on ".$table_prefix."_quotes.quoteid=".$table_prefix."_quotesshipads.quoteshipaddressid
        left join ".$table_prefix."_inventoryproductrel as ".$table_prefix."_inventoryproductrelQuotes on ".$table_prefix."_quotes.quoteid = ".$table_prefix."_inventoryproductrelQuotes.id
				left join ".$table_prefix."_products as ".$table_prefix."_productsQuotes on ".$table_prefix."_productsQuotes.productid = ".$table_prefix."_inventoryproductrelQuotes.productid
				left join ".$table_prefix."_service as ".$table_prefix."_serviceQuotes on ".$table_prefix."_serviceQuotes.serviceid = ".$table_prefix."_inventoryproductrelQuotes.productid
        left join ".$table_prefix."_quotescf on ".$table_prefix."_quotes.quoteid = ".$table_prefix."_quotescf.quoteid
				left join ".$table_prefix."_groups as ".$table_prefix."_groupsQuotes on ".$table_prefix."_groupsQuotes.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users as ".$table_prefix."_usersQuotes on ".$table_prefix."_usersQuotes.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users as ".$table_prefix."_usersRel1 on ".$table_prefix."_usersRel1.id = ".$table_prefix."_quotes.inventorymanager
				left join ".$table_prefix."_potential as ".$table_prefix."_potentialRelQuotes on ".$table_prefix."_potentialRelQuotes.potentialid = ".$table_prefix."_quotes.potentialid
				left join ".$table_prefix."_contactdetails as ".$table_prefix."_contactdetailsQuotes on ".$table_prefix."_contactdetailsQuotes.contactid = ".$table_prefix."_quotes.contactid
				left join ".$table_prefix."_account as ".$table_prefix."_accountQuotes on ".$table_prefix."_accountQuotes.accountid = ".$table_prefix."_quotes.accountid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0";
		}

		else if($module == "PurchaseOrder")
		{
			$query = "from ".$table_prefix."_purchaseorder
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_purchaseorder.purchaseorderid
				inner join ".$table_prefix."_pobillads on ".$table_prefix."_purchaseorder.purchaseorderid=".$table_prefix."_pobillads.pobilladdressid
				inner join ".$table_prefix."_poshipads on ".$table_prefix."_purchaseorder.purchaseorderid=".$table_prefix."_poshipads.poshipaddressid
        left join ".$table_prefix."_inventoryproductrel as ".$table_prefix."_inventoryproductrelPurchaseOrder on ".$table_prefix."_purchaseorder.purchaseorderid = ".$table_prefix."_inventoryproductrelPurchaseOrder.id
				left join ".$table_prefix."_products as ".$table_prefix."_productsPurchaseOrder on ".$table_prefix."_productsPurchaseOrder.productid = ".$table_prefix."_inventoryproductrelPurchaseOrder.productid
				left join ".$table_prefix."_service as ".$table_prefix."_servicePurchaseOrder on ".$table_prefix."_servicePurchaseOrder.serviceid = ".$table_prefix."_inventoryproductrelPurchaseOrder.productid
        left join ".$table_prefix."_purchaseordercf on ".$table_prefix."_purchaseorder.purchaseorderid = ".$table_prefix."_purchaseordercf.purchaseorderid
				left join ".$table_prefix."_groups as ".$table_prefix."_groupsPurchaseOrder on ".$table_prefix."_groupsPurchaseOrder.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users as ".$table_prefix."_usersPurchaseOrder on ".$table_prefix."_usersPurchaseOrder.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_vendor as ".$table_prefix."_vendorRelPurchaseOrder on ".$table_prefix."_vendorRelPurchaseOrder.vendorid = ".$table_prefix."_purchaseorder.vendorid
				left join ".$table_prefix."_contactdetails as ".$table_prefix."_contactdetailsPurchaseOrder on ".$table_prefix."_contactdetailsPurchaseOrder.contactid = ".$table_prefix."_purchaseorder.contactid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0";
		}

		else if($module == "Invoice")
		{
			$query = "from ".$table_prefix."_invoice
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_invoice.invoiceid
				inner join ".$table_prefix."_invoicebillads on ".$table_prefix."_invoice.invoiceid=".$table_prefix."_invoicebillads.invoicebilladdressid
				inner join ".$table_prefix."_invoiceshipads on ".$table_prefix."_invoice.invoiceid=".$table_prefix."_invoiceshipads.invoiceshipaddressid
        left join ".$table_prefix."_inventoryproductrel as ".$table_prefix."_inventoryproductrelInvoice on ".$table_prefix."_invoice.invoiceid = ".$table_prefix."_inventoryproductrelInvoice.id
				left join ".$table_prefix."_products as ".$table_prefix."_productsInvoice on ".$table_prefix."_productsInvoice.productid = ".$table_prefix."_inventoryproductrelInvoice.productid
				left join ".$table_prefix."_service as ".$table_prefix."_serviceInvoice on ".$table_prefix."_serviceInvoice.serviceid = ".$table_prefix."_inventoryproductrelInvoice.productid
        left join ".$table_prefix."_salesorder as ".$table_prefix."_salesorderInvoice on ".$table_prefix."_salesorderInvoice.salesorderid=".$table_prefix."_invoice.salesorderid
				left join ".$table_prefix."_invoicecf on ".$table_prefix."_invoice.invoiceid = ".$table_prefix."_invoicecf.invoiceid
				left join ".$table_prefix."_groups as ".$table_prefix."_groupsInvoice on ".$table_prefix."_groupsInvoice.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users as ".$table_prefix."_usersInvoice on ".$table_prefix."_usersInvoice.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_account as ".$table_prefix."_accountInvoice on ".$table_prefix."_accountInvoice.accountid = ".$table_prefix."_invoice.accountid
				left join ".$table_prefix."_contactdetails as ".$table_prefix."_contactdetailsInvoice on ".$table_prefix."_contactdetailsInvoice.contactid = ".$table_prefix."_invoice.contactid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0";
		}
		else if($module == "SalesOrder")
		{
			$query = "from ".$table_prefix."_salesorder
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_salesorder.salesorderid
				inner join ".$table_prefix."_sobillads on ".$table_prefix."_salesorder.salesorderid=".$table_prefix."_sobillads.sobilladdressid
				inner join ".$table_prefix."_soshipads on ".$table_prefix."_salesorder.salesorderid=".$table_prefix."_soshipads.soshipaddressid
        left join ".$table_prefix."_inventoryproductrel as ".$table_prefix."_inventoryproductrelSalesOrder on ".$table_prefix."_salesorder.salesorderid = ".$table_prefix."_inventoryproductrelSalesOrder.id
				left join ".$table_prefix."_products as ".$table_prefix."_productsSalesOrder on ".$table_prefix."_productsSalesOrder.productid = ".$table_prefix."_inventoryproductrelSalesOrder.productid
				left join ".$table_prefix."_service as ".$table_prefix."_serviceSalesOrder on ".$table_prefix."_serviceSalesOrder.serviceid = ".$table_prefix."_inventoryproductrelSalesOrder.productid
        left join ".$table_prefix."_contactdetails as ".$table_prefix."_contactdetailsSalesOrder on ".$table_prefix."_contactdetailsSalesOrder.contactid = ".$table_prefix."_salesorder.contactid
				left join ".$table_prefix."_quotes as ".$table_prefix."_quotesSalesOrder on ".$table_prefix."_quotesSalesOrder.quoteid = ".$table_prefix."_salesorder.quoteid
				left join ".$table_prefix."_account as ".$table_prefix."_accountSalesOrder on ".$table_prefix."_accountSalesOrder.accountid = ".$table_prefix."_salesorder.accountid
				left join ".$table_prefix."_potential as ".$table_prefix."_potentialRelSalesOrder on ".$table_prefix."_potentialRelSalesOrder.potentialid = ".$table_prefix."_salesorder.potentialid
				left join ".$table_prefix."_invoice_recurring_info on ".$table_prefix."_invoice_recurring_info.salesorderid = ".$table_prefix."_salesorder.salesorderid
				left join ".$table_prefix."_groups as ".$table_prefix."_groupsSalesOrder on ".$table_prefix."_groupsSalesOrder.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users as ".$table_prefix."_usersSalesOrder on ".$table_prefix."_usersSalesOrder.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0";


		}
		else if($module == "Campaigns")
		{
		 $query = "from ".$table_prefix."_campaign
			        inner join ".$table_prefix."_campaignscf as ".$table_prefix."_campaignscf on ".$table_prefix."_campaignscf.campaignid=".$table_prefix."_campaign.campaignid
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_campaign.campaignid
				left join ".$table_prefix."_products as ".$table_prefix."_productsCampaigns on ".$table_prefix."_productsCampaigns.productid = ".$table_prefix."_campaign.product_id
				left join ".$table_prefix."_groups as ".$table_prefix."_groupsCampaigns on ".$table_prefix."_groupsCampaigns.groupid = ".$table_prefix."_crmentity.smownerid
		                left join ".$table_prefix."_users as ".$table_prefix."_usersCampaigns on ".$table_prefix."_usersCampaigns.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
		                left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
                                ".$this->getRelatedModulesQuery($module,$this->secondarymodule).
						getNonAdminAccessControlQuery($this->primarymodule,$current_user)."
				where ".$table_prefix."_crmentity.deleted=0";
		}

		else {
	 			if($module!=''){
	 				$focus = CRMEntity::getInstance($module);
					$query = $focus->generateReportsQuery($module)
								.$this->getRelatedModulesQuery($module,$this->secondarymodule)
								.getNonAdminAccessControlQuery($this->primarymodule,$current_user).
							" WHERE ".$table_prefix."_crmentity.deleted=0";
	 			}
			}
		$log->info("ReportRun :: Successfully returned getReportsQuery".$module);

		// crmv@48520
		if ($this->secondarymodule == 'Calendar') {
			$query .= " AND {$table_prefix}_crmentityCalendar.deleted=0";
		}
		// crmv@48520e
		
		// crmv@48520 crmv@146187
		if ($this->secondarymodule == 'Calendar') {
			$query .= " AND {$table_prefix}_crmentityCalendar.deleted=0";
		} else {
			$query .= " AND ".substr($table_prefix."_crmentity{$this->secondarymodule}",0,29).".deleted=0";
		}
		// crmv@48520e crmv@146187e
		
		$query .= " AND ".$table_prefix."_crmentity.crmid= '".$this->crmid."'";
		//echo $query;
		return $query;
	}


	/** function to get query for the given relblockid,filterlist,type
	 *  @ param $relblockid : Type integer
	 *  @ param $filterlist : Type Array
	 *  @ param $module : Type String
	 *  this returns join query for the report
	 */

	function sGetSQLforReport($relblockid)
	{
		global $log;

		$columnlist = $this->getQueryColumnsList($relblockid);
		//$groupslist = $this->getGroupingList($relblockid);
		$stdfilterlist = $this->getStdFilterList($relblockid);
		//$columnstotallist = $this->getColumnsTotal($relblockid);
		$advfiltersql = $this->getAdvFilterSql($relblockid);

		$this->totallist = $columnstotallist;
		global $current_user;
		$tab_id = getTabid($this->primarymodule);
		//Fix for ticket #4915.
		$selectlist = $columnlist;
		//columns list
		if(isset($selectlist))
		{
			$selectedcolumns =  implode(", ",$selectlist);
		}
		//groups list
		/*
		if(isset($groupslist))
		{
			$groupsquery = implode(", ",$groupslist);
		}
    */
		//standard list
		if(isset($stdfilterlist))
		{
			$stdfiltersql = implode(", ",$stdfilterlist);
		}
		/*
		if(isset($filterlist))
		{
			$stdfiltersql = implode(", ",$filterlist);
		}
		*/
		//columns to total list
		if(isset($columnstotallist))
		{
			$columnstotalsql = implode(", ",$columnstotallist);
		}
		if($stdfiltersql != "")
		{
			$wheresql = " and ".$stdfiltersql;
		}
		if($advfiltersql != "")
		{
			$wheresql .= " and ".$advfiltersql;
		}

		$reportquery = $this->getReportsQuery($this->primarymodule);

		// If we don't have access to any columns, let us select one column and limit result to shown we have not results

		$allColumnsRestricted = false;

		if($selectedcolumns == '') {


				$selectedcolumns = "''"; // "''" to get blank column name
                                $allColumnsRestricted = true;
                        }
			if (isInventoryModule($this->primarymodule) || isInventoryModule($this->secondarymodule)) { // crmv@64542
				$selectedcolumns = ' distinct '. $selectedcolumns;
			}
			$reportquery = "select ".$selectedcolumns." ".$reportquery." ".$wheresql;

		$reportquery = listQueryNonAdminChange($reportquery, $this->primarymodule);

		// Prasad: No columns selected so limit the number of rows directly.
		if($allColumnsRestricted) {
			$reportquery .= " limit 0";
		}

		$log->info("ReportRun :: Successfully returned sGetSQLforReport".$relblockid);
		return $reportquery;

	}

	// Performance Optimization: Added parameter directOutput to avoid building big-string!
	function GenerateReport()
	{
		global $table_prefix;
		global $adb,$current_user,$php_max_execution_time;
		global $modules,$app_strings;
		global $mod_strings,$current_language;
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		$modules_selected = array();
		$modules_selected[] = $this->primarymodule;
		if(!empty($this->secondarymodule)){
			$sec_modules = explode(":",$this->secondarymodule);
			for($i=0;$i<count($sec_modules);$i++){
				$modules_selected[] = $sec_modules[$i];
			}
		}

  		// Update Currency Field list
  		$currencyfieldres = $adb->pquery("SELECT tabid, fieldlabel, uitype from ".$table_prefix."_field WHERE uitype in (71,72,10)", array());
  		if($currencyfieldres) {
  			foreach($currencyfieldres as $currencyfieldrow) {
  				$modprefixedlabel = getTabModuleName($currencyfieldrow['tabid']).' '.$currencyfieldrow['fieldlabel'];
  				$modprefixedlabel = str_replace(' ','_',$modprefixedlabel);
  				if($currencyfieldrow['uitype']!=10){
  					if(!in_array($modprefixedlabel, $this->convert_currency) && !in_array($modprefixedlabel, $this->append_currency_symbol_to_value)) {
  						$this->convert_currency[] = $modprefixedlabel;
  					}
  				} else {
  					if(!in_array($modprefixedlabel, $this->ui10_fields)) {
  						$this->ui10_fields[] = $modprefixedlabel;
  					}
  				}
  			}
  		}

			$sSQL = $this->sGetSQLforReport($this->relblockid);
			//echo $sSQL;
            $result = $adb->query($sSQL);
			if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1)
			$picklistarray = $this->getAccessPickListValues();

			if($result)
			{
				$y=$adb->num_fields($result);
				$custom_field_values = $adb->fetch_array($result);
				//$groupslist = $this->getGroupingList($this->relblockid);

				$column_definitions = $adb->getFieldsDefinition($result);

				do
				{
					for ($i=0; $i<$y; $i++)
					{
  						$fld = $adb->field_name($result, $i);
  						$fld_type = $column_definitions[$i]->type;

                        if (in_array($fld->name, $this->convert_currency)) {
  							$fieldvalue = convertFromMasterCurrency($custom_field_values[$i],$current_user->conv_rate);
  						} elseif(in_array($fld->name, $this->append_currency_symbol_to_value)) {
                            $curid_value = explode("::", $custom_field_values[$i]);
  							$currency_id = $curid_value[0];
  							$currency_value = $curid_value[1];
  							$cur_sym_rate = getCurrencySymbolandCRate($currency_id);
  							$fieldvalue = $cur_sym_rate['symbol']." ".$currency_value;
  						}elseif ($fld->name == "PurchaseOrder_Currency" || $fld->name == "SalesOrder_Currency"
  									|| $fld->name == "Invoice_Currency" || $fld->name == "Quotes_Currency") {
  							$fieldvalue = getCurrencyName($custom_field_values[$i]);
  						}elseif (in_array($fld->name,$this->ui10_fields) && !empty($custom_field_values[$i])) {
  								$type = getSalesEntityType($custom_field_values[$i]);
  								$tmp =getEntityName($type,$custom_field_values[$i]);
  								foreach($tmp as $key=>$val){
  									$fieldvalue = $val;
  									break;
  								}
						//crmv@62185
						} elseif ($fld->name == 'notecontent'){
							global $default_charset;
							$temp_val = preg_replace("/(<\/?)(\w+)([^>]*>)/i","",$custom_field_values[$i]);
							$fieldvalue = trim(html_entity_decode($temp_val, ENT_QUOTES, $default_charset));
						//crmv@62185e
  						}else {
  							$fieldvalue = getTranslatedString($custom_field_values[$i]);
  						}

						if ($fld->name != 'notecontent'){ //crmv@62185
							$fieldvalue = str_replace("<", "&lt;", $fieldvalue);
							$fieldvalue = str_replace(">", "&gt;", $fieldvalue);
						} //crmv@62185

  						//Check For Role based pick list
  						$temp_val= $fld->name;
  						if(is_array($picklistarray))
  							if(array_key_exists($temp_val,$picklistarray))
  							{
  								if(!in_array($custom_field_values[$i],$picklistarray[$fld->name]) && $custom_field_values[$i] != '')
  								{
  									$fieldvalue =$app_strings['LBL_NOT_ACCESSIBLE'];
  								}
  							}
  						if(is_array($picklistarray[1]))
  							if(array_key_exists($temp_val,$picklistarray[1]))
  							{

  								$temp =explode(",",str_ireplace(' |##| ',',',$fieldvalue));
  								$temp_val = Array();
  								foreach($temp as $key =>$val)
  								{
  										if(!in_array(trim($val),$picklistarray[1][$fld->name]) && trim($val) != '')
  										{
  											$temp_val[]=$app_strings['LBL_NOT_ACCESSIBLE'];
  										}
  										else
  											$temp_val[]=$val;
  								}
  								$fieldvalue =(is_array($temp_val))?implode(", ",$temp_val):'';
  							}


  						if($fieldvalue == "" )
  						{
  							$fieldvalue = "-";
  						}
  						else if(stristr($fieldvalue,"|##|"))
  						{
  							$fieldvalue = str_ireplace(' |##| ',', ',$fieldvalue);
  						}
  						else if($fld_type == "date" || $fld_type == "datetime") {
  							//$fieldvalue = getValidDisplayDate($fieldvalue); // crmv@167795 - commented out because date is formatted later in InventoryPDF
  						}

  						$row_data[$fld->name] = $fieldvalue;
					}

					set_time_limit($php_max_execution_time);

					$return_data[] = $row_data;

				}while($custom_field_values = $adb->fetch_array($result));

                //print_r($return_data);

				return $return_data;
			}
	}

	/** Function to convert the Report Header Names into i18n
	 *  @param $fldname: Type Varchar
	 *  Returns Language Converted Header Strings
	 **/
	function getLstringforReportHeaders($fldname)
	{
		global $modules,$current_language,$current_user,$app_strings;
		$rep_header = ltrim(str_replace($modules," ",$fldname));
		$rep_header_temp = preg_replace("/\s+/","_",$rep_header);
		$rep_module = preg_replace("/_$rep_header_temp/","",$fldname);
		$temp_mod_strings = return_module_language($current_language,$rep_module);
		// htmlentities should be decoded in field names (eg. &). Noticed for fields like 'Terms & Conditions', 'S&H Amount'
		$rep_header = decode_html($rep_header);
		$curr_symb = "";
		if(in_array($fldname, $this->convert_currency)) {
        	$curr_symb = " (".$app_strings['LBL_IN']." ".$current_user->currency_symbol.")";
		}
        if($temp_mod_strings[$rep_header] != '')
        {
            $rep_header = $temp_mod_strings[$rep_header];
        }
        $rep_header .=$curr_symb;

		return $rep_header;
	}

	/** Function to get picklist value array based on profile
	 *          *  returns permitted fields in array format
	 */
	function getAccessPickListValues()
	{
		global $adb;
		global $current_user;
		global $table_prefix;
		$id = array(getTabid($this->primarymodule));
		if($this->secondarymodule != '')
			array_push($id,  getTabid($this->secondarymodule));

		$query = 'select fieldname,columnname,fieldid,fieldlabel,tabid,uitype from '.$table_prefix.'_field where tabid in('. generateQuestionMarks($id) .') and uitype in (15,33,55,300)'; //and columnname in (?)'; // crmv@30528
		$result = $adb->pquery($query, $id);//,$select_column));
		$roleid=$current_user->roleid;
		$subrole = getRoleSubordinates($roleid);
		if(count($subrole)> 0)
		{
			$roleids = $subrole;
			array_push($roleids, $roleid);
		}
		else
		{
			$roleids = $roleid;
		}

		$temp_status = Array();
		for($i=0;$i < $adb->num_rows($result);$i++)
		{
			$fieldname = $adb->query_result($result,$i,"fieldname");
			$fieldlabel = $adb->query_result($result,$i,"fieldlabel");
			$tabid = $adb->query_result($result,$i,"tabid");
			$uitype = $adb->query_result($result,$i,"uitype");
			$fieldlabel1 = str_replace(" ","_",$fieldlabel);
			$keyvalue = getTabModuleName($tabid)."_".$fieldlabel1;
			$fieldvalues = Array();
			//se la picklist supporta il nuovo metodo
			$columns = array($adb->database->MetaColumnNames($table_prefix."_$fieldname"));
			if ($columns && in_array('picklist_valueid',$columns) && $fieldname != 'product_lines'){
				$order_by = "sortid,$fieldname";
				$pick_query="select $fieldname from ".$table_prefix."_$fieldname where exists (select * from ".$table_prefix."_role2picklist where ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$fieldname.picklist_valueid and roleid in (". generateQuestionMarks($roleids) ."))";
				$params = array($roleids);
			}
			//altrimenti uso il vecchio
			else {
				if (in_array('sortorderid',$columns))
					$order_by = "sortorderid,$fieldname";
				else
					$order_by = $fieldname;
				$pick_query="select $fieldname from ".$table_prefix."_$fieldname";
				$params = array();
			}
			if ($fieldname != 'firstname')
				$mulselresult = $adb->pquery($pick_query,$params);
			if ($mulselresult){
				for($j=0;$j < $adb->num_rows($mulselresult);$j++)
				{
					$fldvalue = $adb->query_result($mulselresult,$j,$fieldname);
					if(in_array($fldvalue,$fieldvalues)) continue;
					$fieldvalues[] = $fldvalue;
				}
			}
			$field_count = count($fieldvalues);
			if( $uitype == 15 && $field_count > 0 && ($fieldname == 'taskstatus' || $fieldname == 'eventstatus'))
			{
				$temp_count =count($temp_status[$keyvalue]);
				if($temp_count > 0)
				{
					for($t=0;$t < $field_count;$t++)
					{
						$temp_status[$keyvalue][($temp_count+$t)] = $fieldvalues[$t];
					}
					$fieldvalues = $temp_status[$keyvalue];
				}
				else
					$temp_status[$keyvalue] = $fieldvalues;
			}

			if($uitype == 33){
				$fieldlists[1][$keyvalue] = $fieldvalues;
				$fieldlists[1][$fieldname] = $fieldvalues;
			}
			else if($uitype == 55 && $fieldname == 'salutationtype'){
				$fieldlists[$keyvalue] = $fieldvalues;
				$fieldlists[$fieldname] = $fieldvalues;
			}
	        else if($uitype == 15){
		        $fieldlists[$keyvalue] = $fieldvalues;
		        $fieldlists[$fieldname] = $fieldvalues;
	        }
		}
		return $fieldlists;
	}


}