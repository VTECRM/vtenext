<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/RelatedListView.php');
//crmv@203484 removed including file

// Account is used to store vte_account information.
class Quotes extends CRMEntity {
	var $log;
	var $db;

	var $table_name;
	var $table_index= 'quoteid';
	var $tab_name = Array();
	var $tab_name_index = Array();
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();
	var $entity_table;

	var $billadr_table;

	var $object_name = "Quote";

	var $new_schema = true;

	var $column_fields = Array();

	var $sortby_fields = Array('subject','crmid','smownerid','accountname','lastname');

	// This is used to retrieve related vte_fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'smownerid', 'opportunity_id', 'case_id', 'contact_id', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id', 'parent_name', 'member_id' );

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
				//'Quote No'=>Array('crmentity'=>'crmid'),
				// Module Sequence Numbering
				'Quote No'=>Array('quotes'=>'quote_no'),
				// END
				'Subject'=>Array('quotes'=>'subject'),
				'Quote Stage'=>Array('quotes'=>'quotestage'),
				'Potential Name'=>Array('quotes'=>'potentialid'),
				'Account Name'=>Array('account'=> 'accountid'),
				'Total'=>Array('quotes'=> 'total'),
				'Assigned To'=>Array('crmentity'=>'smownerid')
				);

	var $list_fields_name = Array(
				        'Quote No'=>'quote_no',
				        'Subject'=>'subject',
				        'Quote Stage'=>'quotestage',
				        'Potential Name'=>'potential_id',
					'Account Name'=>'account_id',
					'Total'=>'hdnGrandTotal',
				        'Assigned To'=>'assigned_user_id'
				      );
	var $list_link_field= 'subject';

	var $search_fields = Array(
				'Quote No'=>Array('quotes'=>'quote_no'),
				'Subject'=>Array('quotes'=>'subject'),
				'Account Name'=>Array('quotes'=>'accountid'),
				'Quote Stage'=>Array('quotes'=>'quotestage'),
				);

	var $search_fields_name = Array(
					'Quote No'=>'quote_no',
				        'Subject'=>'subject',
				        'Account Name'=>'account_id',
				        'Quote Stage'=>'quotestage',
				      );

	// This is the list of vte_fields that are required.
	var $required_fields =  array("accountname"=>1);

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'crmid';
	var $default_sort_order = 'ASC';
	//var $groupTable = Array('vte_quotegrouprelation','quoteid');

	var $mandatory_fields = Array('subject','createdtime' ,'modifiedtime');
	//crmv@10759
	var $search_base_field = 'subject';
	//crmv@10759 e
	/**	Constructor which will set the column_fields in this object
	 */
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_quotes';
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_quotes',$table_prefix.'_quotesbillads',$table_prefix.'_quotesshipads',$table_prefix.'_quotescf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_quotes'=>'quoteid',$table_prefix.'_quotesbillads'=>'quotebilladdressid',$table_prefix.'_quotesshipads'=>'quoteshipaddressid',$table_prefix.'_quotescf'=>'quoteid');
		$this->customFieldTable = Array($table_prefix.'_quotescf', 'quoteid');
		$this->entity_table = $table_prefix."_crmentity";
		$this->billadr_table = $table_prefix."_quotesbillads";
		$this->log =LoggerManager::getLogger('quote');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Quotes');
	}

	function save_module()
	{
		global $adb,$table_prefix,$iAmAProcess;
		//in ajax save we should not call this function, because this will delete all the existing product values
		if(!empty($_REQUEST) && isset($_REQUEST['totalProductCount']) && $_REQUEST['action'] != 'QuotesAjax' && $_REQUEST['ajxaction'] != 'DETAILVIEW' && $_REQUEST['action'] != 'MassEditSave' && !$iAmAProcess) // crmv@138794 crmv@196424
		{
			$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
			//Based on the total Number of rows we will save the product relationship with this entity
			$InventoryUtils->saveInventoryProductDetails($this, 'Quotes');
		}

		// Update the currency id and the conversion rate for the quotes
		$update_query = "update ".$table_prefix."_quotes set currency_id=?, conversion_rate=? where quoteid=?";
		$update_params = array($this->column_fields['currency_id'], $this->column_fields['conversion_rate'], $this->id);
		$adb->pquery($update_query, $update_params);

		//crmv@44323 crmv@53923
		// review quote
		if ($_REQUEST['convert_from'] == 'reviewquote' && $_REQUEST['duplicate_from'] > 0 && !$iAmAProcess) { // crmv@165159
			// change status of old quote
			$adb->pquery("update {$table_prefix}_quotes set quotestage = ? where quoteid = ?", array('Reviewed', intval($_REQUEST['duplicate_from'])));

			// get old number
			$res = $adb->pquery("select quote_no from {$table_prefix}_quotes inner join {$table_prefix}_crmentity on crmid = quoteid where deleted = 0 and quoteid = ?", array(intval($_REQUEST['duplicate_from'])));
			$old_quote_no = $adb->query_result_no_html($res, 0, 'quote_no');

			// set number for new one
			if ($this->id > 0 && $old_quote_no) {
				$number = 1;
				$old_quote_no = preg_replace('/-REV[0-9]*/', '', $old_quote_no);
				$autoNumber = str_replace("'", '', $old_quote_no.'-REV');
				// check for others reviews
				$res = $adb->query("select quote_no from {$table_prefix}_quotes inner join {$table_prefix}_crmentity on crmid = quoteid where deleted = 0 and quote_no like '$autoNumber%'");
				if ($res && $adb->num_rows($res) > 0) {
					while ($row = $adb->fetchByAssoc($res, -1, false)) {
						$num = intval(str_replace($autoNumber, '', $row['quote_no'])) + 1;
						if ($num > $number) $number = $num;
					}
				}
				$autoNumber .= $number;
				$adb->pquery("update {$table_prefix}_quotes set quote_no = ? where quoteid = ?", array($autoNumber, $this->id));
			}
		}

		// if active, update the potential amount
		if (vtlib_isModuleActive('Potentials') && !empty($this->column_fields['potential_id']) && is_numeric($this->column_fields['potential_id']) && in_array($this->column_fields['quotestage'], array('Created', 'Delivered')) && !$iAmAProcess) { // crmv@161211 crmv@165159
			global $currentModule;
			if (empty($this->column_fields['hdnGrandTotal'])) {
				$this->column_fields['hdnGrandTotal'] = getSingleFieldValue($this->table_name, 'total', $this->table_index, $this->id);
			}
			$oldCurrentModule = $currentModule;
			$potFocus = CRMEntity::getInstance('Potentials');
			$potFocus->retrieve_entity_info($this->column_fields['potential_id'], 'Potentials');
			$potFocus->id = $this->column_fields['potential_id'];
			$potFocus->existingAmount = $potFocus->column_fields['amount'];
			$potFocus->column_fields['amount'] = $this->column_fields['hdnGrandTotal'];
			$adb->pquery("update {$table_prefix}_potential set amount = ? where potentialid = ?", array($potFocus->column_fields['amount'], $potFocus->id));
			$potFocus->postSaveAmount();

			$currentModule = $oldCurrentModule;
		}
		//crmv@44323e crmv@53923e
	}

	/**	function used to get the list of sales orders which are related to the Quotes
	 *	@param int $id - quote id
	 *	@return array - return an array which will be returned from the function GetRelatedList
	 */
	function get_salesorder($id)
	{
		global $log,$table_prefix;//crmv@203484 removed global singlepane
		$log->debug("Entering get_salesorder(".$id.") method ...");
        $focus = CRMEntity::getInstance('SalesOrder');

		$button = '';

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module=Quotes&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module=Quotes&return_action=CallRelatedList&return_id='.$id;

		$query = "select ".$table_prefix."_crmentity.*, ".$table_prefix."_salesorder.*, ".$table_prefix."_quotes.subject as quotename, ".$table_prefix."_account.accountname,case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name
		from ".$table_prefix."_salesorder
		inner join ".$table_prefix."_salesordercf on ".$table_prefix."_salesordercf.salesorderid = ".$table_prefix."_salesorder.salesorderid
		inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_salesorder.salesorderid
		left outer join ".$table_prefix."_quotes on ".$table_prefix."_quotes.quoteid=".$table_prefix."_salesorder.quoteid
		left outer join ".$table_prefix."_account on ".$table_prefix."_account.accountid=".$table_prefix."_salesorder.accountid
		left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid=".$table_prefix."_crmentity.smownerid
		left join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid
		where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_salesorder.quoteid = ".$id;
		$log->debug("Exiting get_salesorder method ...");
		return GetRelatedList('Quotes','SalesOrder',$focus,$query,$button,$returnset);
	}

	// Function to get column name - Overriding function of base class
	function get_column_value($columname, $fldvalue, $fieldname, $uitype, $datatype='') {
		if ($columname == 'potentialid' || $columname == 'contactid') {
			if ($fldvalue == '') return null;
		}
		return parent::get_column_value($columname, $fldvalue, $fieldname, $uitype, $datatype);
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	// crmv@35693 crmv@38798 crmv@73751
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $table_prefix;
		if ($reporttype != 'COLUMNSTOTOTAL' && $useProductJoin) {
			$productjoins = "left join ".$table_prefix."_inventoryproductrel ".substr($table_prefix.'_inventoryproductrelQuotes',0,29)." on ".$table_prefix."_quotes.quoteid = ".substr($table_prefix.'_inventoryproductrelQuotes',0,29).".id
							left join ".$table_prefix."_products ".$table_prefix."_productsQuotes on ".$table_prefix."_productsQuotes.productid = ".substr($table_prefix.'_inventoryproductrelQuotes',0,29).".productid
							left join ".$table_prefix."_service ".$table_prefix."_serviceQuotes on ".$table_prefix."_serviceQuotes.serviceid = ".substr($table_prefix.'_inventoryproductrelQuotes',0,29).".productid ";
		}

		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_quotes","quoteid");
		$query .= " left join ".$table_prefix."_quotescf on ".$table_prefix."_quotes.quoteid = ".$table_prefix."_quotescf.quoteid
					left join ".$table_prefix."_quotesbillads on ".$table_prefix."_quotes.quoteid=".$table_prefix."_quotesbillads.quotebilladdressid
					left join ".$table_prefix."_quotesshipads on ".$table_prefix."_quotes.quoteid=".$table_prefix."_quotesshipads.quoteshipaddressid
					left join ".$table_prefix."_inventorytotals ".substr($table_prefix.'_inventorytotalsQuotes',0,29)." on ".substr($table_prefix.'_inventorytotalsQuotes',0,29).".id = ".$table_prefix."_quotes.quoteid
					$productjoins
					left join ".$table_prefix."_groups ".$table_prefix."_groupsQuotes on ".$table_prefix."_groupsQuotes.groupid = ".$table_prefix."_crmentityQuotes.smownerid
					left join ".$table_prefix."_users ".$table_prefix."_usersQuotes on ".$table_prefix."_usersQuotes.id = ".$table_prefix."_crmentityQuotes.smownerid
					left join ".$table_prefix."_users ".$table_prefix."_usersRel1 on ".$table_prefix."_usersRel1.id = ".$table_prefix."_quotes.inventorymanager
					left join ".$table_prefix."_potential ".$table_prefix."_potentialRelQuotes on ".$table_prefix."_potentialRelQuotes.potentialid = ".$table_prefix."_quotes.potentialid
					left join ".$table_prefix."_contactdetails ".$table_prefix."_contactdetailsQuotes on ".$table_prefix."_contactdetailsQuotes.contactid = ".$table_prefix."_quotes.contactid
					left join ".$table_prefix."_account ".$table_prefix."_accountQuotes on ".$table_prefix."_accountQuotes.accountid = ".$table_prefix."_quotes.accountid ";
		return $query;
	}
	// crmv@35693e crmv@38798e	crmv@73751e

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables = array (
			"SalesOrder" =>array($table_prefix."_salesorder"=>array("quoteid","salesorderid"),$table_prefix."_quotes"=>"quoteid"),
			"Calendar" =>array($table_prefix."_seactivityrel"=>array("crmid","activityid"),$table_prefix."_quotes"=>"quoteid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_quotes"=>"quoteid"),
			"Accounts" => array($table_prefix."_quotes"=>array("quoteid","accountid")),
			"Contacts" => array($table_prefix."_quotes"=>array("quoteid","contactid")),
			"Potentials" => array($table_prefix."_quotes"=>array("quoteid","potentialid")),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log,$table_prefix;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Accounts' ) {
			$this->trash('Quotes',$id);
		} elseif($return_module == 'Potentials') {
			$relation_query = 'UPDATE '.$table_prefix.'_quotes SET potentialid=0 WHERE quoteid=?';
			$this->db->pquery($relation_query, array($id));
		} elseif($return_module == 'Contacts') {
			$relation_query = 'UPDATE '.$table_prefix.'_quotes SET contactid=0 WHERE quoteid=?';
			$this->db->pquery($relation_query, array($id));
		} else {
			$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($sql, $params);
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

}

?>